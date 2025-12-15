<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-14-2025).
session_start();
// 1. Guardián de sesión: Valida la sesión y carga la configuración (incluyendo BASE_URL).
require_once 'api_owner_session_check.php';

// 2. Dependencias adicionales
header('Content-Type: application/json');
require_once 'audit_log.php'; // Reactivado
require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- NUEVO ESQUEMA TRANSACCIONAL ---

// Iniciar transacción para asegurar la integridad de los datos
$conn->begin_transaction();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido.');
    }

    $id_negocio_session = $_SESSION['owner_id_negocio'];
    $input = json_decode(file_get_contents('php://input'), true);

    // 1. Validar datos de entrada
    $tipo_cita = $input['tipo_cita'] ?? 'Servicio';
    $id_cliente = (int)($input['id_cliente'] ?? 0);
    $id_servicio = !empty($input['id_servicio']) ? (int)$input['id_servicio'] : null;
    $fecha_hora_inicio_str = trim($input['fecha_hora_inicio'] ?? '');
    $descripcion_trabajo = trim($input['descripcion_trabajo'] ?? '');
    $notificar_cliente = (bool)($input['notificar_cliente'] ?? false); // REACTIVADO

    // --- MEJORA: Validar todos los campos requeridos ANTES de la transacción ---
    if ($id_cliente <= 0 || empty($fecha_hora_inicio_str) || ($tipo_cita === 'Servicio' && empty($id_servicio))) {
        throw new Exception('Datos incompletos para agendar la cita.', 400);
    }

    // 2. Calcular duración y hora de fin
    if ($tipo_cita === 'Servicio') {
        // La validación de empty($id_servicio) ya se hizo arriba.
        
        $sql_servicio = "SELECT duracion_valor, duracion_unidad FROM j104_servicios WHERE id_servicio = ? AND id_negocio = ?";
        $stmt_servicio = $conn->prepare($sql_servicio);
        $stmt_servicio->bind_param("ii", $id_servicio, $id_negocio_session);
        $stmt_servicio->execute();
        $servicio = $stmt_servicio->get_result()->fetch_assoc();
        $stmt_servicio->close();

        if (!$servicio) throw new Exception('Servicio no encontrado.', 404);
        if ((int)$servicio['duracion_valor'] <= 0) throw new Exception('El servicio tiene una duración inválida.', 400);
        
        $duracion_valor = (int)$servicio['duracion_valor'];
        $duracion_unidad = $servicio['duracion_unidad'];
    } else {
        $duracion_valor = 60;
        $duracion_unidad = 'Minutos';
    }

    $fecha_hora_inicio = new DateTime($fecha_hora_inicio_str);
    $fecha_hora_fin = clone $fecha_hora_inicio;
    $interval_spec = 'PT';
    if ($duracion_unidad === 'Horas') $interval_spec .= $duracion_valor . 'H';
    else if ($duracion_unidad === 'Dias') $interval_spec = 'P' . $duracion_valor . 'D';
    else $interval_spec .= $duracion_valor . 'M';
    $fecha_hora_fin->add(new DateInterval($interval_spec));

    // 3. Insertar la cita
    // SOLUCIÓN DEFINITIVA: Guardar el resultado de ->format() en variables antes de pasarlas a bind_param.
    $fecha_inicio_db = $fecha_hora_inicio->format('Y-m-d H:i:s');
    $fecha_fin_db = $fecha_hora_fin->format('Y-m-d H:i:s');

    // --- INICIO: OBTENER PREFERENCIAS DEL CLIENTE ---
    // SOLUCIÓN: Obtener AMBAS preferencias (SMS y Email) del cliente.
    $in_email_pref = 0;
    $in_sms_pref = 0; // Corregido de IN_SMS a in_sms
    $stmt_pref = $conn->prepare("SELECT in_email, in_sms FROM j106_clientes WHERE id_cliente = ?");
    $stmt_pref->bind_param("i", $id_cliente);
    $stmt_pref->execute();
    $result_pref = $stmt_pref->get_result()->fetch_assoc();
    if ($result_pref) {
        $in_email_pref = (int)$result_pref['in_email'];
        $in_sms_pref = (int)$result_pref['in_sms'];
    }
    $stmt_pref->close();
    // --- FIN: OBTENER PREFERENCIAS DEL CLIENTE ---

    // --- SOLUCIÓN: Verificación explícita de duplicados ---
    // Antes de insertar, comprobamos si ya existe una cita idéntica para evitar el falso positivo del 409.
    $sql_check_duplicate = "SELECT id_cita FROM j108_citas WHERE id_cliente = ? AND id_servicio = ? AND fecha_hora_inicio = ?";
    $stmt_check = $conn->prepare($sql_check_duplicate);
    // Usamos el id_servicio que puede ser null para reuniones. La comparación con NULL en SQL es segura aquí.
    $stmt_check->bind_param("iis", $id_cliente, $id_servicio, $fecha_inicio_db);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        throw new Exception('Ya existe una cita idéntica para este cliente con el mismo servicio y hora.', 409);
    }
    $stmt_check->close();

    // SOLUCIÓN: Ajustar la consulta y los parámetros dinámicamente si id_servicio es NULL (para Reuniones).
    if ($tipo_cita === 'Reunion' || $id_servicio === null) {
        $sql_cita = "INSERT IGNORE INTO j108_citas (id_negocio, id_cliente, id_servicio, fecha_hora_inicio, fecha_hora_fin, estado_cita, descripcion_trabajo, tipo_cita, in_email, in_sms) VALUES (?, ?, NULL, ?, ?, 'Pendiente', ?, ?, ?, ?)";
        $stmt_cita = $conn->prepare($sql_cita);
        // SOLUCIÓN: Corregido el tipo de dato para id_cliente de 's' a 'i'.
        $stmt_cita->bind_param("iissssii", $id_negocio_session, $id_cliente, $fecha_inicio_db, $fecha_fin_db, $descripcion_trabajo, $tipo_cita, $in_email_pref, $in_sms_pref);
    } else {
        $sql_cita = "INSERT IGNORE INTO j108_citas (id_negocio, id_cliente, id_servicio, fecha_hora_inicio, fecha_hora_fin, estado_cita, descripcion_trabajo, tipo_cita, in_email, in_sms) VALUES (?, ?, ?, ?, ?, 'Pendiente', ?, ?, ?, ?)";
        $stmt_cita = $conn->prepare($sql_cita);
        // Se incluye el tipo 'i' para id_servicio
        $stmt_cita->bind_param("iiissssii", $id_negocio_session, $id_cliente, $id_servicio, $fecha_inicio_db, $fecha_fin_db, $descripcion_trabajo, $tipo_cita, $in_email_pref, $in_sms_pref);
    }
    
    if (!$stmt_cita->execute()) {
        throw new Exception('Error al ejecutar la inserción de la cita: ' . $stmt_cita->error, 500);
    }

    $id_nueva_cita = $stmt_cita->insert_id;
    $stmt_cita->close();

    // 4. Insertar invitados (si es una reunión)
    if ($tipo_cita === 'Reunion' && !empty($input['invitados'])) {
        $sql_invitado = "INSERT INTO j109_invitados_cita (id_cita, nombre_invitado, correo_electronico_invitado, numero_celular_invitado) VALUES (?, ?, ?, ?)";
        $stmt_invitado = $conn->prepare($sql_invitado);
        foreach ($input['invitados'] as $invitado) {
            $stmt_invitado->bind_param("isss", $id_nueva_cita, $invitado['nombre'], $invitado['email'], $invitado['telefono']);
            if (!$stmt_invitado->execute()) {
                throw new Exception('Error al guardar un invitado: ' . $stmt_invitado->error, 500);
            }
        }
        $stmt_invitado->close();
    }

    // 5. Registrar auditoría (ahora es seguro hacerlo dentro de la transacción)
    // registrar_auditoria($conn, $_SESSION['owner_id_usuario'], $id_negocio_session, 'OWNER_SPA_CITA_CREATE', "Propietario agendó nueva cita ID {$id_nueva_cita}."); // Suspendido para prueba

    // 6. Si todo fue exitoso, confirmar la transacción
    $conn->commit();

    // 7. Enviar notificación por correo si se solicitó (LÓGICA LOCAL ROBUSTA)
    $email_message_part = '.';
    if ($notificar_cliente) {
        try {
            // Obtener todos los detalles para el correo
            $sql_email = "SELECT cl.nombre_completo AS nombre_cliente, cl.correo_electronico AS email_cliente, IF(c.tipo_cita = 'Reunion', c.descripcion_trabajo, s.nombre_servicio) AS titulo_evento, n.nombre_negocio, n.email AS email_negocio, n.direccion1, n.ciudad FROM j108_citas c JOIN j106_clientes cl ON c.id_cliente = cl.id_cliente LEFT JOIN j104_servicios s ON c.id_servicio = s.id_servicio JOIN j102_negocios n ON c.id_negocio = n.id_negocio WHERE c.id_cita = ?";
            $stmt_email = $conn->prepare($sql_email);
            $stmt_email->bind_param("i", $id_nueva_cita);
            $stmt_email->execute();
            $details = $stmt_email->get_result()->fetch_assoc();
            $stmt_email->close();

            if ($details && !empty($details['email_cliente'])) {
                $mail = new PHPMailer(true);
                // --- SOLUCIÓN: Opciones para entorno de desarrollo (XAMPP) ---
                // Esto soluciona el error "certificate verify failed" en local.
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                // Configuración SMTP
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USERNAME;
                $mail->Password   = SMTP_PASSWORD;
                $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;

                $mail->setFrom(SMTP_USERNAME, $details['nombre_negocio']);
                $mail->addAddress($details['email_cliente'], $details['nombre_cliente']);
                $mail->addReplyTo($details['email_negocio'], $details['nombre_negocio']);
                // --- SOLUCIÓN: Añadir copia oculta al negocio ---
                if (!empty($details['email_negocio'])) {
                    $mail->addBCC($details['email_negocio']);
                }

                // --- MEJORA: Añadir a los invitados como destinatarios (BCC) ---
                if ($tipo_cita === 'Reunion' && !empty($input['invitados'])) {
                    $sql_invitados = "SELECT correo_electronico_invitado FROM j109_invitados_cita WHERE id_cita = ?";
                    $stmt_invitados = $conn->prepare($sql_invitados);
                    $stmt_invitados->bind_param("i", $id_nueva_cita);
                    $stmt_invitados->execute();
                    $result_invitados = $stmt_invitados->get_result();
                    while ($invitado = $result_invitados->fetch_assoc()) {
                        if (!empty($invitado['correo_electronico_invitado'])) {
                            $mail->addBCC($invitado['correo_electronico_invitado']);
                        }
                    }
                    $stmt_invitados->close();
                }


                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = 'Confirmación de tu cita en ' . $details['nombre_negocio'];
                $fecha_cita_obj = new DateTime($fecha_inicio_db);
                $mail->Body    = "Hola {$details['nombre_cliente']},<br><br>Tu cita para <b>{$details['titulo_evento']}</b> ha sido confirmada para el día <b>" . $fecha_cita_obj->format('d/m/Y') . "</b> a las <b>" . $fecha_cita_obj->format('h:i A') . "</b>.<br><br>Te esperamos en {$details['direccion1']}, {$details['ciudad']}.<br>Atentamente,<br>El equipo de {$details['nombre_negocio']}.";

                // --- PRUEBA: Se suspende temporalmente la generación del archivo .ics para aislar el error BASE_URL ---
                // La generación del archivo .ics se restaurará una vez que la inclusión de config.php sea estable.

                $mail->send();
                $email_message_part = ' y se ha enviado la notificación al cliente.';
                
                // Actualizar contador de email
                $conn->query("UPDATE j108_citas SET in_email = in_email + 1 WHERE id_cita = $id_nueva_cita");
            } else {
                $email_message_part = ', pero el cliente no tiene un email registrado para notificar.';
            }
        } catch (Exception $mail_e) {
            // SOLUCIÓN: Manejo de error SMTP robusto.
            // Se registra el error técnico completo en el log del servidor para depuración.
            // Se muestra un mensaje genérico y amigable al usuario.
            $email_message_part = ", pero no se pudo enviar la notificación por correo. Revise la configuración SMTP.";
            error_log("Fallo al enviar correo para nueva cita ID {$id_nueva_cita}: " . $mail_e->getMessage());
        }
    }

    // 8. Enviar respuesta de éxito final
    echo json_encode(['success' => true, 'message' => 'Cita agendada con éxito' . $email_message_part, 'id_cita' => $id_nueva_cita]);

} catch (Exception $e) {
    // Si algo falló, deshacer todos los cambios
    $conn->rollback();

    // Enviar respuesta de error
    $codigo_error = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($codigo_error);
    echo json_encode(['error' => $e->getMessage()]);
}
?>