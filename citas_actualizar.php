<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-20-2025).
require_once 'auth_check.php';
require_once 'audit_log.php';
require_once 'config.php';

// CORRECCIÓN: Añadir las declaraciones 'use' para que PHPMailer sea reconocido.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Obtener la configuración del negocio para validaciones
$stmt_config = $conn->prepare("SELECT * FROM j102_negocios WHERE id_negocio = ?");
$stmt_config->bind_param("i", $id_negocio_session);
$stmt_config->execute();
$config = $stmt_config->get_result()->fetch_assoc();
$stmt_config->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Recoger los datos del formulario
    $id_cita = (int)$_POST['id_cita'];
    $tipo_cita = $_POST['tipo_cita'];
    $id_cliente = (int)$_POST['id_cliente'];
    $id_servicio = (int)$_POST['id_servicio'];
    $fecha_cita = $_POST['fecha_cita'];
    $hora_cita = $_POST['hora_cita'];
    $fecha_hora_inicio_str = $fecha_cita . ' ' . $hora_cita;
    $descripcion = trim($_POST['descripcion_trabajo']);

    if ($id_cita <= 0) {
        header("Location: citas_lista.php?status=error&message=" . urlencode("ID de cita inválido."));
        exit();
    }

    // 2. Obtener la duración del servicio
    if ($tipo_cita === 'Servicio') {
        $stmt_duracion = $conn->prepare("SELECT duracion_valor, duracion_unidad FROM j104_servicios WHERE id_servicio = ?");
        $stmt_duracion->bind_param("i", $id_servicio);
        $stmt_duracion->execute();
        $servicio = $stmt_duracion->get_result()->fetch_assoc();
        $duracion_valor = (int)$servicio['duracion_valor'];
        $duracion_unidad = $servicio['duracion_unidad'];
        $stmt_duracion->close();
    } else { // Reunión
        // Para reuniones, asumimos una duración por defecto, por ejemplo 60 minutos.
        $duracion_valor = 60;
        $duracion_unidad = 'Minutos';
    }

    // 3. Calcular fechas y realizar validaciones
    $app_timezone = new DateTimeZone(date_default_timezone_get());
    $fecha_hora_inicio = new DateTime($fecha_hora_inicio_str, $app_timezone);
    $fecha_hora_fin = clone $fecha_hora_inicio;

    // Lógica de duración flexible
    if ($duracion_unidad == 'Minutos') {
        $fecha_hora_fin->add(new DateInterval('PT' . $duracion_valor . 'M'));
    } elseif ($duracion_unidad == 'Horas') {
        $fecha_hora_fin->add(new DateInterval('PT' . $duracion_valor . 'H'));
    } elseif ($duracion_unidad == 'Dias') {
        $fecha_hora_fin->add(new DateInterval('P' . $duracion_valor . 'D'));
    }

    $fecha_inicio_db = $fecha_hora_inicio->format('Y-m-d H:i:s');
    $fecha_fin_db = $fecha_hora_fin->format('Y-m-d H:i:s');

    // a) Validar horario de trabajo (usando la tabla de configuración)
    $dia_semana = $fecha_hora_inicio->format('N'); // 1 (para Lunes) hasta 7 (para Domingo)
    $dias_trabajo = explode(',', $config['dias_trabajo']);
    if (!in_array($dia_semana, $dias_trabajo) && $duracion_unidad != 'Dias') {
        header("Location: citas_editar.php?id=" . $id_cita . "&status=error&message=" . urlencode("El día seleccionado no es un día laboral."));
        exit();
    }

    if (($fecha_hora_inicio->format('H:i:s') < $config['hora_inicio'] || $fecha_hora_fin->format('H:i:s') > $config['hora_cierre']) && $duracion_unidad != 'Dias' && $fecha_hora_fin->format('H:i:s') != '00:00:00') {
        header("Location: citas_editar.php?id=" . $id_cita . "&status=error&message=" . urlencode("Horario fuera de trabajo."));
        exit();
    }

    // b) Validar colisiones (excluyendo la cita actual)
    $sql_check = "SELECT id_cita FROM j108_citas WHERE id_negocio = ? AND id_cita != ? AND ? < fecha_hora_fin AND ? > fecha_hora_inicio";
    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("iiss", $id_negocio_session, $id_cita, $fecha_inicio_db, $fecha_fin_db);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            header("Location: citas_editar.php?id=" . $id_cita . "&status=error&message=" . urlencode("El horario seleccionado ya está ocupado."));
            exit();
        }
        $stmt_check->close();
    }

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // 4. Preparar la consulta SQL de actualización para la cita principal
        $sql = "UPDATE j108_citas SET id_cliente = ?, id_servicio = ?, fecha_hora_inicio = ?, fecha_hora_fin = ?, descripcion_trabajo = ? WHERE id_cita = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssi", $id_cliente, $id_servicio, $fecha_inicio_db, $fecha_fin_db, $descripcion, $id_cita);
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar la cita principal: " . $stmt->error);
        }
        $stmt->close();

        // 5. Si es una reunión, procesar los invitados
        if ($tipo_cita === 'Reunion') {
            $ids_invitados_enviados = [];
            if (!empty($_POST['invitado_id'])) {
                $ids_invitados = $_POST['invitado_id'];
                $nombres_invitados = $_POST['invitado_nombre'];
                $emails_invitados = $_POST['invitado_email'];
                $telefonos_invitados = $_POST['invitado_telefono'];

                $stmt_update_invitado = $conn->prepare("UPDATE j109_invitados_cita SET nombre_invitado = ?, correo_electronico_invitado = ?, numero_celular_invitado = ? WHERE id_invitado = ? AND id_cita = ?");
                $stmt_insert_invitado = $conn->prepare("INSERT INTO j109_invitados_cita (id_cita, nombre_invitado, correo_electronico_invitado, numero_celular_invitado) VALUES (?, ?, ?, ?)");

                for ($i = 0; $i < count($ids_invitados); $i++) {
                    $id_invitado = (int)$ids_invitados[$i];
                    $nombre = trim($nombres_invitados[$i]);
                    $email = trim($emails_invitados[$i]);
                    $telefono = trim($telefonos_invitados[$i]);

                    if (empty($nombre) || empty($email)) continue;

                    if ($id_invitado > 0) { // Es un invitado existente, actualizar
                        $stmt_update_invitado->bind_param("sssii", $nombre, $email, $telefono, $id_invitado, $id_cita);
                        $stmt_update_invitado->execute();
                        $ids_invitados_enviados[] = $id_invitado;
                    } else { // Es un nuevo invitado, insertar
                        $stmt_insert_invitado->bind_param("isss", $id_cita, $nombre, $email, $telefono);
                        $stmt_insert_invitado->execute();
                        $ids_invitados_enviados[] = $stmt_insert_invitado->insert_id;
                    }
                }
                $stmt_update_invitado->close();
                $stmt_insert_invitado->close();
            }

            // Eliminar invitados que ya no están en el formulario
            $stmt_get_old_ids = $conn->prepare("SELECT id_invitado FROM j109_invitados_cita WHERE id_cita = ?");
            $stmt_get_old_ids->bind_param("i", $id_cita);
            $stmt_get_old_ids->execute();
            $result_old_ids = $stmt_get_old_ids->get_result();
            $stmt_delete_invitado = $conn->prepare("DELETE FROM j109_invitados_cita WHERE id_invitado = ?");
            while ($row = $result_old_ids->fetch_assoc()) {
                if (!in_array($row['id_invitado'], $ids_invitados_enviados)) {
                    $stmt_delete_invitado->bind_param("i", $row['id_invitado']);
                    $stmt_delete_invitado->execute();
                }
            }
            $stmt_get_old_ids->close();
            $stmt_delete_invitado->close();
        }

        // Si todo va bien, confirmar transacción
        $conn->commit();

        $descripcion_audit = "Se actualizó la cita (ID: {$id_cita}) para el cliente ID: {$id_cliente} a la fecha {$fecha_inicio_db}.";
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'UPDATE_APPOINTMENT', $descripcion_audit);

        // Simular un POST para enviar el correo automáticamente
        $_POST['id_cita'] = $id_cita;
        $_POST['accion'] = 'MODIFICADA';
        // include 'enviar_email.php'; // SUSPENDIDO TEMPORALMENTE
        // La redirección se manejará dentro de enviar_email.php (ahora desactivada)
        // header("Location: citas_confirmar_envio.php?id_cita=" . $id_cita . "&accion=MODIFICADA");
        header("Location: citas_lista.php?status=success_edit&message=" . urlencode("Cita actualizada. El envío de correo está suspendido."));
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: citas_editar.php?id=" . $id_cita . "&status=error&message=" . urlencode($e->getMessage()));
        exit();
    }
}
?>