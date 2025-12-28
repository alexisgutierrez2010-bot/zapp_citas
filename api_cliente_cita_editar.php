<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).

require_once 'api_cliente_session_check.php';
header('Content-Type: application/json');
require_once 'audit_log.php';
require_once 'ical_generator.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$input = json_decode(file_get_contents('php://input'), true);

// Validar datos de entrada
$id_cita = (int)($input['id_cita'] ?? 0);
$id_cliente = (int)($input['id_cliente'] ?? 0);
$id_negocio = (int)($input['id_negocio'] ?? 0);
$id_servicio = (int)($input['id_servicio'] ?? 0);
$fecha_hora_inicio_str = trim($input['fecha_hora_inicio'] ?? '');
$descripcion_trabajo = trim($input['descripcion_trabajo'] ?? '');

if ($id_cita <= 0 || $id_cliente <= 0 || $id_negocio <= 0 || $id_servicio <= 0 || empty($fecha_hora_inicio_str)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos para editar la cita.']);
    exit;
}

// Verificación de seguridad: el ID del cliente en la sesión debe coincidir
if ($id_cliente !== (int)$_SESSION['client_id']) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado.']);
    exit;
}

try {
    // 1. Verificar que la cita existe, pertenece al cliente y está Pendiente
    $sql_check = "SELECT id_cita FROM j108_citas WHERE id_cita = ? AND id_cliente = ? AND estado_cita = 'Pendiente'";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $id_cita, $id_cliente);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'La cita no existe, no te pertenece o ya no se puede editar (no está Pendiente).']);
        exit;
    }
    $stmt_check->close();

    // 2. Obtener duración del servicio para recalcular fecha fin
    $sql_servicio = "SELECT duracion_valor, duracion_unidad FROM j104_servicios WHERE id_servicio = ? AND id_negocio = ?";
    $stmt_servicio = $conn->prepare($sql_servicio);
    $stmt_servicio->bind_param("ii", $id_servicio, $id_negocio);
    $stmt_servicio->execute();
    $servicio = $stmt_servicio->get_result()->fetch_assoc();
    $stmt_servicio->close();

    if (!$servicio) {
        throw new Exception('El servicio seleccionado no es válido.');
    }

    // 3. Calcular nuevas fechas
    $fecha_hora_inicio = new DateTime($fecha_hora_inicio_str);
    $fecha_hora_fin = clone $fecha_hora_inicio;

    $interval_spec = 'PT' . $servicio['duracion_valor'];
    if ($servicio['duracion_unidad'] === 'Horas') $interval_spec .= 'H';
    else if ($servicio['duracion_unidad'] === 'Dias') $interval_spec .= 'D';
    else $interval_spec .= 'M';
    $fecha_hora_fin->add(new DateInterval($interval_spec));

    // 4. Actualizar la cita
    $descripcion_final = !empty($descripcion_trabajo) ? $descripcion_trabajo : 'Cita reagendada por el cliente.';
    
    $sql_update = "UPDATE j108_citas SET 
                    id_servicio = ?, 
                    fecha_hora_inicio = ?, 
                    fecha_hora_fin = ?, 
                    descripcion_trabajo = ? 
                   WHERE id_cita = ?";
    
    $stmt = $conn->prepare($sql_update);
    
    $fecha_inicio_sql = $fecha_hora_inicio->format('Y-m-d H:i:s');
    $fecha_fin_sql = $fecha_hora_fin->format('Y-m-d H:i:s');
    
    $stmt->bind_param("isssi", $id_servicio, $fecha_inicio_sql, $fecha_fin_sql, $descripcion_final, $id_cita);

    if ($stmt->execute()) {
        registrar_auditoria($conn, $id_cliente, $id_negocio, 'CLIENT_RESCHEDULE', "Cliente ID {$id_cliente} reagendó cita ID {$id_cita}.");
        
        // --- INICIO: ENVÍO DE CORREO DE REAGENDAMIENTO ---
        try {
            // Obtener datos frescos para el correo
            $sql_datos = "SELECT
                            cl.nombre_completo AS nombre_cliente, cl.correo_electronico AS email_cliente,
                            n.nombre_negocio, n.email AS email_negocio,
                            u.correo_electronico AS email_propietario,
                            s.nombre_servicio
                         FROM j108_citas ci
                         JOIN j106_clientes cl ON ci.id_cliente = cl.id_cliente
                         JOIN j102_negocios n ON ci.id_negocio = n.id_negocio
                         JOIN j104_servicios s ON ci.id_servicio = s.id_servicio
                         LEFT JOIN j100_usuarios u ON n.id_negocio = u.id_negocio AND u.rol = 'Propietario'
                         WHERE ci.id_cita = ? LIMIT 1";
            $stmt_datos = $conn->prepare($sql_datos);
            $stmt_datos->bind_param("i", $id_cita);
            $stmt_datos->execute();
            $datos_correo = $stmt_datos->get_result()->fetch_assoc();
            $stmt_datos->close();

            if ($datos_correo) {
                $fecha_formateada = $fecha_hora_inicio->format('d/m/Y');
                $hora_formateada = $fecha_hora_inicio->format('h:i A');

                // Generar iCal (Calendario)
                $ical_details = [
                    'start_time' => $fecha_hora_inicio->format('Y-m-d H:i:s'),
                    'end_time' => $fecha_hora_fin->format('Y-m-d H:i:s'),
                    'summary' => "REAGENDADA: " . $datos_correo['nombre_servicio'],
                    'description' => "La cita ha sido reagendada. Nuevo horario: $fecha_formateada a las $hora_formateada.",
                    'location' => $datos_correo['nombre_negocio'], // Simplificado
                    'organizer_email' => $datos_correo['email_negocio'],
                    'organizer_name' => $datos_correo['nombre_negocio'],
                    'attendee_email' => $datos_correo['email_cliente'],
                    'attendee_name' => $datos_correo['nombre_cliente'],
                    'uid' => "ZAPPCITAS-{$id_cita}-MOD-" . time() . "@acticven.com",
                    'guests' => []
                ];
                $ical_content = generate_ical_content($ical_details);

                // Configuración común de PHPMailer
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
                $mail->CharSet = 'UTF-8';
                $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];

                // 1. Correo al Propietario
                if (!empty($datos_correo['email_propietario'])) {
                    $mail->setFrom(SMTP_USERNAME, 'ZApp Citas - Alertas');
                    $mail->clearAddresses();
                    $mail->addAddress($datos_correo['email_propietario']);
                    $mail->addReplyTo($datos_correo['email_cliente'], $datos_correo['nombre_cliente']);
                    $mail->isHTML(true);
                    $mail->Subject = "Cita Reagendada: {$datos_correo['nombre_servicio']} - {$datos_correo['nombre_cliente']}";
                    $mail->addStringAttachment($ical_content, 'cita_modificada.ics', 'base64', 'text/calendar');
                    $mail->Body = "<h2>Cita Modificada</h2>
                                   <p>El cliente <strong>{$datos_correo['nombre_cliente']}</strong> ha cambiado la fecha de su cita.</p>
                                   <ul>
                                       <li><strong>Servicio:</strong> {$datos_correo['nombre_servicio']}</li>
                                       <li><strong>Nueva Fecha:</strong> {$fecha_formateada}</li>
                                       <li><strong>Nueva Hora:</strong> {$hora_formateada}</li>
                                       <li><strong>Nota:</strong> " . htmlspecialchars($descripcion_final) . "</li>
                                   </ul>";
                    $mail->send();
                }

                // 2. Correo al Cliente
                if (!empty($datos_correo['email_cliente'])) {
                    $mail->setFrom(SMTP_USERNAME, $datos_correo['nombre_negocio']);
                    $mail->clearAddresses();
                    $mail->clearAttachments(); // Limpiar adjuntos anteriores
                    $mail->addAddress($datos_correo['email_cliente'], $datos_correo['nombre_cliente']);
                    $mail->addReplyTo($datos_correo['email_negocio'], $datos_correo['nombre_negocio']);
                    $mail->Subject = "Cita Actualizada: {$datos_correo['nombre_servicio']}";
                    $mail->addStringAttachment($ical_content, 'cita_modificada.ics', 'base64', 'text/calendar');
                    $mail->Body = "<h2>Tu cita ha sido actualizada</h2>
                                   <p>Hola {$datos_correo['nombre_cliente']}, confirmamos el cambio de tu cita.</p>
                                   <ul>
                                       <li><strong>Servicio:</strong> {$datos_correo['nombre_servicio']}</li>
                                       <li><strong>Nueva Fecha:</strong> {$fecha_formateada}</li>
                                       <li><strong>Nueva Hora:</strong> {$hora_formateada}</li>
                                   </ul>";
                    $mail->send();
                }
            }
        } catch (Exception $e) {
            registrar_auditoria($conn, null, $id_negocio, 'EMAIL_FAIL', "Fallo envío correo edición cita {$id_cita}: " . $e->getMessage());
        }
        // --- FIN ENVÍO CORREO ---

        echo json_encode(['success' => true, 'message' => '¡Tu cita ha sido reagendada con éxito!']);
    } else {
        throw new Exception('Error al actualizar la cita en base de datos.');
    }
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al procesar la solicitud: ' . $e->getMessage()]);
}
?>
