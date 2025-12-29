<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
require_once 'auth_check.php';
require_once 'audit_log.php';
require_once 'config.php';
require_once 'vendor/autoload.php'; // SOLUCIÓN: Cargar las dependencias de Composer.

// CORRECCIÓN: Añadir las declaraciones 'use' para que PHPMailer sea reconocido.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Recoger los datos del formulario
    $tipo_cita = $_POST['tipo_cita'];
    $id_cliente = (int)$_POST['id_cliente'];
    $id_servicio = !empty($_POST['id_servicio']) ? (int)$_POST['id_servicio'] : null;
    // Unimos la fecha y la hora que vienen de campos separados
    $fecha_cita = $_POST['fecha_cita'];
    $hora_cita = $_POST['hora_cita'];
    $fecha_hora_inicio_str = $fecha_cita . ' ' . $hora_cita;
    $descripcion = trim($_POST['descripcion_trabajo']);
    
    // Lógica de Negocio para Admin
    $id_negocio = $id_negocio_session;
    if (isset($_POST['id_negocio']) && (strcasecmp(trim($rol_session ?? ''), 'Administrador') == 0)) {
        $id_negocio = (int)$_POST['id_negocio'];
    }

    // Obtener configuración del negocio seleccionado
    $stmt_config = $conn->prepare("SELECT * FROM j102_negocios WHERE id_negocio = ?");
    $stmt_config->bind_param("i", $id_negocio);
    $stmt_config->execute();
    $config = $stmt_config->get_result()->fetch_assoc();
    $stmt_config->close();

    // 2. Obtener la duración del servicio para calcular la hora de fin
    $stmt_duracion = $conn->prepare("SELECT nombre_servicio, duracion_valor, duracion_unidad FROM j104_servicios WHERE id_servicio = ?");
    
    if ($tipo_cita === 'Servicio') {
        if (empty($id_servicio)) {
            header("Location: citas_lista.php?status=error&message=" . urlencode("Debe seleccionar un servicio para este tipo de cita."));
            exit();
        }
        $stmt_duracion->bind_param("i", $id_servicio);
        $stmt_duracion->execute();
        $result_duracion = $stmt_duracion->get_result();
        if ($result_duracion->num_rows === 1) {
            $servicio = $result_duracion->fetch_assoc();
            $duracion_valor = (int)$servicio['duracion_valor'];
            $duracion_unidad = $servicio['duracion_unidad'];
        } else {
            header("Location: citas_lista.php?status=error&message=" . urlencode("Servicio no válido."));
            exit();
        }
        $stmt_duracion->close();
    } else {
        // Para reuniones, asumimos una duración por defecto, por ejemplo 60 minutos.
        $duracion_valor = 60;
        $duracion_unidad = 'Minutos';
    }

    // 3. Calcular la fecha y hora de finalización
    $app_timezone = new DateTimeZone(date_default_timezone_get());
    $fecha_hora_inicio = new DateTime($fecha_hora_inicio_str, $app_timezone);
    $fecha_hora_fin = clone $fecha_hora_inicio; // Clonar el objeto
    
    // Lógica de duración flexible
    if ($duracion_unidad == 'Minutos') {
        $fecha_hora_fin->add(new DateInterval('PT' . $duracion_valor . 'M'));
    } elseif ($duracion_unidad == 'Horas') {
        $fecha_hora_fin->add(new DateInterval('PT' . $duracion_valor . 'H'));
    } elseif ($duracion_unidad == 'Dias') {
        $fecha_hora_fin->add(new DateInterval('P' . $duracion_valor . 'D'));
    }

    // Formatear las fechas para MySQL
    $fecha_inicio_db = $fecha_hora_inicio->format('Y-m-d H:i:s');
    $fecha_fin_db = $fecha_hora_fin->format('Y-m-d H:i:s');

    // 4. VALIDACIÓN DE REGLAS DE NEGOCIO (usando la tabla de configuración)
    $dia_semana = $fecha_hora_inicio->format('N'); // 1 (para Lunes) hasta 7 (para Domingo)
    $dias_trabajo = explode(',', $config['dias_trabajo']);
    if (!in_array($dia_semana, $dias_trabajo) && $duracion_unidad != 'Dias') { // No validar día de la semana para citas de varios días
        header("Location: citas_lista.php?status=error&message=" . urlencode("El día seleccionado no es un día laboral."));
        exit();
    }

    if (($fecha_hora_inicio->format('H:i:s') < $config['hora_inicio'] || $fecha_hora_fin->format('H:i:s') > $config['hora_cierre']) && $duracion_unidad != 'Dias' && $fecha_hora_fin->format('H:i:s') != '00:00:00') {
        header("Location: citas_lista.php?status=error&message=" . urlencode("El horario seleccionado está fuera del horario de trabajo."));
        exit();
    }

    // b) Validar colisiones de horario
    $sql_check = "SELECT id_cita FROM j108_citas WHERE id_negocio = ? AND ? < fecha_hora_fin AND ? > fecha_hora_inicio";
    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("iss", $id_negocio, $fecha_inicio_db, $fecha_fin_db);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            header("Location: citas_lista.php?status=error&message=" . urlencode("El horario seleccionado ya está ocupado."));
            exit();
        }
        $stmt_check->close();
    }

    // --- INICIO: OBTENER PREFERENCIAS DEL CLIENTE ---
    // Leemos las preferencias de notificación del cliente para la nueva cita.
    // SOLUCIÓN: Obtener AMBAS preferencias (SMS y Email) del cliente.
    $in_email_pref = 0;
    $in_sms_pref = 0;
    $stmt_pref = $conn->prepare("SELECT IN_EMAIL, IN_SMS FROM j106_clientes WHERE id_cliente = ?");
    $stmt_pref->bind_param("i", $id_cliente);
    $stmt_pref->execute();
    $result_pref = $stmt_pref->get_result()->fetch_assoc();
    if ($result_pref) {
        $in_email_pref = (int)$result_pref['IN_EMAIL'];
        $in_sms_pref = (int)$result_pref['IN_SMS'];
    }
    $stmt_pref->close();
    // --- FIN: OBTENER PREFERENCIAS DEL CLIENTE ---

    // 5. Preparar la consulta SQL para insertar la cita
    $sql = "INSERT INTO j108_citas (id_cliente, id_servicio, fecha_hora_inicio, fecha_hora_fin, descripcion_trabajo, id_negocio, tipo_cita, IN_EMAIL, IN_SMS) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iisssisii", $id_cliente, $id_servicio, $fecha_inicio_db, $fecha_fin_db, $descripcion, $id_negocio, $tipo_cita, $in_email_pref, $in_sms_pref);

        if ($stmt->execute()) {
            $id_nueva_cita = $stmt->insert_id;
            $descripcion_audit = "Se ha creado una nueva cita tipo '{$tipo_cita}' (ID: {$id_nueva_cita}) para el cliente ID: {$id_cliente} en la fecha {$fecha_inicio_db}.";
            registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio, 'CREATE_APPOINTMENT', $descripcion_audit);

            // Si es una reunión, guardar los invitados
            if ($tipo_cita === 'Reunion' && !empty($_POST['invitado_nombre'])) {
                $nombres_invitados = $_POST['invitado_nombre'];
                $emails_invitados = $_POST['invitado_email'];
                $telefonos_invitados = $_POST['invitado_telefono'];

                $sql_invitado = "INSERT INTO j109_invitados_cita (id_cita, nombre_invitado, correo_electronico_invitado, numero_celular_invitado) VALUES (?, ?, ?, ?)";
                $stmt_invitado = $conn->prepare($sql_invitado);

                for ($i = 0; $i < count($nombres_invitados); $i++) {
                    if (!empty($nombres_invitados[$i]) && !empty($emails_invitados[$i])) {
                        $stmt_invitado->bind_param("isss", $id_nueva_cita, $nombres_invitados[$i], $emails_invitados[$i], $telefonos_invitados[$i]);
                        $stmt_invitado->execute();
                    }
                }
                $stmt_invitado->close();
            }
            
            // Cita guardada con éxito.
            $stmt->close();

            // Simular un POST para enviar el correo automáticamente
            // include 'enviar_email.php'; // SUSPENDIDO TEMPORALMENTE
            header("Location: citas_lista.php?status=success&message=" . urlencode("Cita creada con éxito. El envío de correo está suspendido."));
            exit();
        } else {
            header("Location: citas_lista.php?status=error&message=" . urlencode($stmt->error));
        }
        $stmt->close();
    } else {
        header("Location: citas_lista.php?status=error&message=" . urlencode($conn->error));
    }

    exit();
}