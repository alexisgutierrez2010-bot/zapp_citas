<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).

session_start();
require_once 'api_owner_session_check.php';
require_once 'config.php';
require_once 'audit_log.php';
require_once 'ical_generator.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

// --- 1. Recoger y Validar Datos ---
$id_negocio = $_SESSION['owner_id_negocio'];
$id_usuario = $_SESSION['owner_id_usuario'];

$tipo_cita = $input['tipo_cita'] ?? '';
$id_cliente = (int)($input['id_cliente'] ?? 0);
$id_servicio = ($tipo_cita === 'Servicio') ? (int)($input['id_servicio'] ?? 0) : null;
$asunto = ($tipo_cita === 'Reunion') ? trim($input['asunto'] ?? '') : null;
$fecha_hora_inicio_str = trim($input['fecha_hora_inicio'] ?? '');
$descripcion = trim($input['descripcion'] ?? '');
$invitados = ($tipo_cita === 'Reunion') ? ($input['invitados'] ?? []) : [];
$notificar_cliente = (bool)($input['notificar_cliente'] ?? false);

if ($id_cliente <= 0 || empty($fecha_hora_inicio_str) || ($tipo_cita === 'Servicio' && $id_servicio <= 0) || ($tipo_cita === 'Reunion' && empty($asunto))) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos para agendar la cita.']);
    exit;
}

$conn->begin_transaction();

try {
    // --- 2. Calcular Fecha de Fin ---
    $duracion_valor = 60; // Default 60 min para reuniones
    $duracion_unidad = 'Minutos';

    if ($tipo_cita === 'Servicio') {
        $stmt_duracion = $conn->prepare("SELECT duracion_valor, duracion_unidad FROM j104_servicios WHERE id_servicio = ? AND id_negocio = ?");
        $stmt_duracion->bind_param("ii", $id_servicio, $id_negocio);
        $stmt_duracion->execute();
        $servicio_dur = $stmt_duracion->get_result()->fetch_assoc();
        if (!$servicio_dur) throw new Exception("Servicio no válido.", 404);
        $duracion_valor = (int)$servicio_dur['duracion_valor'];
        $duracion_unidad = $servicio_dur['duracion_unidad'];
        $stmt_duracion->close();
    }

    $fecha_hora_inicio = new DateTime($fecha_hora_inicio_str);
    $fecha_hora_fin = clone $fecha_hora_inicio;
    
    // MEJORA: Lógica de intervalo robusta para manejar Minutos, Horas y Días.
    $interval_unit = 'M'; // Default a Minutos
    if ($duracion_unidad === 'Horas') $interval_unit = 'H';
    else if ($duracion_unidad === 'Dias') $interval_unit = 'D';
    $interval_spec = 'PT' . $duracion_valor . $interval_unit;

    $fecha_hora_fin->add(new DateInterval($interval_spec));

    // --- 3. Insertar Cita Principal ---
    $descripcion_final = $tipo_cita === 'Reunion' ? $asunto : $descripcion;
    $sql_cita = "INSERT INTO j108_citas (id_negocio, id_cliente, id_servicio, fecha_hora_inicio, fecha_hora_fin, estado_cita, descripcion_trabajo, tipo_cita) VALUES (?, ?, ?, ?, ?, 'Confirmada', ?, ?)";
    $stmt_cita = $conn->prepare($sql_cita);
    // SOLUCIÓN: Asignar los resultados de las funciones a variables antes de pasarlas a bind_param.
    $fecha_inicio_sql = $fecha_hora_inicio->format('Y-m-d H:i:s');
    $fecha_fin_sql = $fecha_hora_fin->format('Y-m-d H:i:s');
    $stmt_cita->bind_param("iiissss", $id_negocio, $id_cliente, $id_servicio, $fecha_inicio_sql, $fecha_fin_sql, $descripcion_final, $tipo_cita);
    if (!$stmt_cita->execute()) throw new Exception("Error al crear la cita: " . $stmt_cita->error);
    $id_nueva_cita = $stmt_cita->insert_id;

    // --- 4. Insertar Invitados (si es reunión) y confirmar transacción ---
    if ($tipo_cita === 'Reunion') {
        if (!empty($invitados)) {
            $sql_invitado = "INSERT INTO j109_invitados_cita (id_cita, nombre_invitado, correo_electronico_invitado, numero_celular_invitado) VALUES (?, ?, ?, ?)";
            $stmt_invitado = $conn->prepare($sql_invitado);
            foreach ($invitados as $invitado) {
                if (!empty($invitado['nombre']) && !empty($invitado['email'])) {
                    $stmt_invitado->bind_param("isss", $id_nueva_cita, $invitado['nombre'], $invitado['email'], $invitado['telefono']);
                    $stmt_invitado->execute();
                }
            }
            $stmt_invitado->close();
        }
    }

    // --- 5. Confirmar la transacción ANTES de enviar correos ---
    $conn->commit();
    registrar_auditoria($conn, $id_usuario, $id_negocio, 'OWNER_CREATE_APPOINTMENT', "Propietario creó cita ID {$id_nueva_cita}.");

} catch (Exception $e) {
    $conn->rollback();
    $http_code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
    http_response_code($http_code);
    echo json_encode(['error' => $e->getMessage()]);
    exit; // Salir para no continuar con el envío de correo
}

// --- 6. Enviar Notificaciones (Fuera de la transacción) ---
$notification_payload = null;
if ($notificar_cliente) {
    try {
        // Obtener todos los datos para el correo y el iCal
        $sql_datos = "SELECT 
                        cl.nombre_completo AS nombre_cliente, cl.correo_electronico AS email_cliente, cl.numero_celular AS telefono_cliente,
                        n.nombre_negocio, n.email AS email_negocio, n.direccion1 AS direccion_negocio,
                        u.correo_electronico AS email_propietario,
                        s.nombre_servicio
                      FROM j108_citas ci
                      JOIN j106_clientes cl ON ci.id_cliente = cl.id_cliente
                      JOIN j102_negocios n ON ci.id_negocio = n.id_negocio
                      LEFT JOIN j100_usuarios u ON n.id_negocio = u.id_negocio AND u.rol = 'Propietario'
                      LEFT JOIN j104_servicios s ON ci.id_servicio = s.id_servicio
                      WHERE ci.id_cita = ? LIMIT 1";
        $stmt_datos = $conn->prepare($sql_datos);
        $stmt_datos->bind_param("i", $id_nueva_cita);
        $stmt_datos->execute();
        $datos_correo = $stmt_datos->get_result()->fetch_assoc();
        $stmt_datos->close();

        if ($datos_correo) {
            $asunto_evento = ($tipo_cita === 'Reunion') ? $asunto : ($datos_correo['nombre_servicio'] ?? 'Servicio');

            // Enviar correo si el cliente tiene uno
            if (!empty($datos_correo['email_cliente'])) {
                $ical_details = [
                    'start_time' => $fecha_hora_inicio->format('Y-m-d H:i:s'),
                    'end_time' => $fecha_hora_fin->format('Y-m-d H:i:s'),
                    'summary' => $asunto_evento,
                    'description' => $descripcion,
                    'location' => $datos_correo['direccion_negocio'] ?? '',
                    'organizer_email' => $datos_correo['email_negocio'] ?? SMTP_USERNAME,
                    'organizer_name' => $datos_correo['nombre_negocio'] ?? 'ZApp Citas',
                    'attendee_email' => $datos_correo['email_cliente'],
                    'attendee_name' => $datos_correo['nombre_cliente'],
                    'uid' => "ZAPPCITAS-{$id_nueva_cita}-" . time() . "@acticven.com",
                    'guests' => $invitados
                ];
                $ical_content = generate_ical_content($ical_details);

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

                $mail->setFrom(SMTP_USERNAME, $datos_correo['nombre_negocio']);
                $mail->addReplyTo($datos_correo['email_negocio'], $datos_correo['nombre_negocio']);
                if (!empty($datos_correo['email_propietario'])) $mail->addBCC($datos_correo['email_propietario']);
                $mail->isHTML(true);
                $mail->Subject = "Cita Confirmada: {$asunto_evento}";
                $mail->Body = "<h2>Cita Confirmada</h2><p>Hola {$datos_correo['nombre_cliente']}, tu cita ha sido agendada con éxito.</p><ul><li><strong>Asunto:</strong> {$asunto_evento}</li><li><strong>Fecha:</strong> {$fecha_hora_inicio->format('d/m/Y')}</li><li><strong>Hora:</strong> {$fecha_hora_inicio->format('h:i A')}</li></ul><p>¡Te esperamos!</p>";
                $mail->AltBody = "Cita confirmada: {$asunto_evento} el {$fecha_hora_inicio->format('d/m/Y')} a las {$fecha_hora_inicio->format('h:i A')}.";
                $mail->addStringAttachment($ical_content, 'cita.ics', 'base64', 'text/calendar');

                $mail->addAddress($datos_correo['email_cliente'], $datos_correo['nombre_cliente']);
                if ($tipo_cita === 'Reunion') {
                    foreach ($invitados as $invitado) {
                        if (!empty($invitado['email'])) $mail->addAddress($invitado['email'], $invitado['nombre']);
                    }
                }
                $mail->send();
            }

            // Preparar payload para notificaciones adicionales (SMS/WhatsApp)
            if (!empty($datos_correo['telefono_cliente'])) {
                $notification_payload = [
                    'telefono_cliente' => $datos_correo['telefono_cliente'],
                    'nombre_cliente' => $datos_correo['nombre_cliente'],
                    'nombre_negocio' => $datos_correo['nombre_negocio'],
                    'asunto_evento' => $asunto_evento,
                    'fecha_hora_inicio' => $fecha_hora_inicio->format('d/m/Y \a \l\a\s h:i A'),
                ];
            }
        }
    } catch (Exception $e) {
        // Si el correo falla, no detenemos la ejecución ni revertimos la cita.
        // Simplemente registramos el error para depuración.
        registrar_auditoria($conn, $id_usuario, $id_negocio, 'EMAIL_FAIL', "Fallo al enviar correo para nueva cita ID {$id_nueva_cita}. Error: " . $e->getMessage());
    }
}

// --- 7. Finalizar y enviar respuesta ---
echo json_encode([
    'success' => true, 
    'message' => 'Cita creada con éxito.',
    'notification_payload' => $notification_payload
]);
$conn->close();
?>