<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).

// SOLUCIÓN: Aplicar el guardián de sesión del cliente para unificar la seguridad y la conexión a BD.
require_once 'api_cliente_session_check.php';

header('Content-Type: application/json');
// config.php es cargado por el guardián. $conn ya está disponible.
require_once 'audit_log.php';
require_once 'ical_generator.php';
require 'vendor/autoload.php'; // Para PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$input = json_decode(file_get_contents('php://input'), true);

// Validar datos de entrada
$id_cliente = (int)($input['id_cliente'] ?? 0);
$id_negocio = (int)($input['id_negocio'] ?? 0);
$id_servicio = (int)($input['id_servicio'] ?? 0);
$fecha_hora_inicio_str = trim($input['fecha_hora_inicio'] ?? '');
$descripcion_trabajo = trim($input['descripcion_trabajo'] ?? '');
 
if ($id_cliente <= 0 || $id_negocio <= 0 || $id_servicio <= 0 || empty($fecha_hora_inicio_str)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos para agendar la cita.']);
    exit;
}

// Verificación de seguridad: el ID del cliente en la sesión debe coincidir con el que se envía.
if ($id_cliente !== (int)$_SESSION['client_id']) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Intento de agendar cita para otro cliente.']);
    exit;
}

try {
// Obtener datos del servicio, cliente y negocio para la cita y el correo
$sql_servicio = "SELECT duracion_valor, duracion_unidad FROM j104_servicios WHERE id_servicio = ? AND id_negocio = ? AND activo = 1";
$stmt_servicio = $conn->prepare($sql_servicio);
$stmt_servicio->bind_param("ii", $id_servicio, $id_negocio);
$stmt_servicio->execute();
$result_servicio = $stmt_servicio->get_result();
$servicio = $result_servicio->fetch_assoc();
$stmt_servicio->close();

if (!$servicio) {
    http_response_code(404);
    echo json_encode(['error' => 'El servicio seleccionado no está disponible o no pertenece a este negocio.']);
    exit;
}

$fecha_hora_inicio = new DateTime($fecha_hora_inicio_str);
$fecha_hora_fin = clone $fecha_hora_inicio;

// Calcular fecha_hora_fin basado en la duración del servicio
$interval_spec = 'PT' . $servicio['duracion_valor'];
if ($servicio['duracion_unidad'] === 'Horas') $interval_spec .= 'H';
else if ($servicio['duracion_unidad'] === 'Dias') $interval_spec .= 'D';
else $interval_spec .= 'M'; // Minutos por defecto
$fecha_hora_fin->add(new DateInterval($interval_spec));

// Si la descripción viene vacía, ponemos un texto por defecto.
$descripcion_final = !empty($descripcion_trabajo) ? $descripcion_trabajo : 'Cita agendada por el cliente.';

$sql = "INSERT INTO j108_citas (id_negocio, id_cliente, id_servicio, fecha_hora_inicio, fecha_hora_fin, estado_cita, descripcion_trabajo, tipo_cita) VALUES (?, ?, ?, ?, ?, 'Pendiente', ?, 'Servicio')";
$stmt = $conn->prepare($sql);
// SOLUCIÓN: Asignar el resultado de ->format() a variables antes de pasarlas a bind_param
// para evitar errores de "pass-by-reference" en algunas versiones de PHP.
$fecha_inicio_sql = $fecha_hora_inicio->format('Y-m-d H:i:s');
$fecha_fin_sql = $fecha_hora_fin->format('Y-m-d H:i:s');
$stmt->bind_param("iiisss", $id_negocio, $id_cliente, $id_servicio, $fecha_inicio_sql, $fecha_fin_sql, $descripcion_final);

if ($stmt->execute()) {
    $id_nueva_cita = $stmt->insert_id;
    registrar_auditoria($conn, $id_cliente, $id_negocio, 'CLIENT_SELF_BOOKING', "Cliente ID {$id_cliente} agendó nueva cita ID {$id_nueva_cita}.");

    // --- INICIO: LÓGICA DE ENVÍO DE CORREO REACTIVADA Y MEJORADA ---
    try {
        // Obtener datos para el correo
        $sql_datos_correo = "SELECT
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
        $stmt_datos = $conn->prepare($sql_datos_correo);
        $stmt_datos->bind_param("i", $id_nueva_cita);
        $stmt_datos->execute();
        $datos_correo = $stmt_datos->get_result()->fetch_assoc();
        $stmt_datos->close();

        if ($datos_correo) {
            $fecha_formateada = $fecha_hora_inicio->format('d/m/Y');
            $hora_formateada = $fecha_hora_inicio->format('h:i A');

            // Generar contenido iCal
            $ical_details = [
                'start_time' => $fecha_hora_inicio->format('Y-m-d H:i:s'),
                'end_time' => $fecha_hora_fin->format('Y-m-d H:i:s'),
                'summary' => $datos_correo['nombre_servicio'],
                'description' => "Servicio: {$datos_correo['nombre_servicio']}. Notas: " . $descripcion_final,
                'location' => $datos_correo['direccion_negocio'] ?? '',
                'organizer_email' => $datos_correo['email_negocio'],
                'organizer_name' => $datos_correo['nombre_negocio'],
                'attendee_email' => $datos_correo['email_cliente'],
                'attendee_name' => $datos_correo['nombre_cliente'],
                'uid' => "ZAPPCITAS-{$id_nueva_cita}-" . time() . "@acticven.com",
                'guests' => []
            ];
            $ical_content = generate_ical_content($ical_details);

            // Enviar al propietario del negocio
            if (!empty($datos_correo['email_propietario'])) {
                $mail_owner = new PHPMailer(true);
                $mail_owner->isSMTP();
                $mail_owner->Host = SMTP_HOST;
                $mail_owner->SMTPAuth = true;
                $mail_owner->Username = SMTP_USERNAME;
                $mail_owner->Password = SMTP_PASSWORD;
                $mail_owner->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : PHPMailer::ENCRYPTION_STARTTLS;
                $mail_owner->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
                $mail_owner->CharSet = 'UTF-8';

                $mail_owner->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                $mail_owner->setFrom(SMTP_USERNAME, 'ZApp Citas - Notificaciones');
                $mail_owner->addAddress($datos_correo['email_propietario']);
                $mail_owner->addReplyTo($datos_correo['email_cliente'], $datos_correo['nombre_cliente']);
                $mail_owner->isHTML(true);
                $mail_owner->Subject = "Nueva Cita Agendada: {$datos_correo['nombre_servicio']} para {$datos_correo['nombre_cliente']}";
                $mail_owner->addStringAttachment($ical_content, 'cita.ics', 'base64', 'text/calendar');
                $mail_owner->Body = "<h2>Nueva Cita Agendada</h2><p>Se ha registrado una nueva cita a través del portal de clientes.</p><ul><li><strong>Cliente:</strong> {$datos_correo['nombre_cliente']}</li><li><strong>Servicio:</strong> {$datos_correo['nombre_servicio']}</li><li><strong>Fecha:</strong> {$fecha_formateada}</li><li><strong>Hora:</strong> {$hora_formateada}</li><li><strong>Notas del cliente:</strong> " . htmlspecialchars($descripcion_final) . "</li></ul>";
                $mail_owner->send();
            }

            // Enviar al cliente
            if (!empty($datos_correo['email_cliente'])) {
                $mail_client = new PHPMailer(true);
                $mail_client->isSMTP();
                $mail_client->Host = SMTP_HOST;
                $mail_client->SMTPAuth = true;
                $mail_client->Username = SMTP_USERNAME;
                $mail_client->Password = SMTP_PASSWORD;
                $mail_client->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : PHPMailer::ENCRYPTION_STARTTLS;
                $mail_client->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
                $mail_client->CharSet = 'UTF-8';

                $mail_client->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                $mail_client->setFrom(SMTP_USERNAME, $datos_correo['nombre_negocio']); // From: admin_appcitas@acticven.com (Nombre del Negocio)
                $mail_client->addAddress($datos_correo['email_cliente'], $datos_correo['nombre_cliente']);
                $mail_client->addReplyTo($datos_correo['email_negocio'], $datos_correo['nombre_negocio']); // Reply-To: el_negocio@email.com
                $mail_client->isHTML(true);
                $mail_client->Subject = "Confirmación de Cita: {$datos_correo['nombre_servicio']}";
                $mail_client->addStringAttachment($ical_content, 'cita.ics', 'base64', 'text/calendar');
                $mail_client->Body = "<h2>Confirmación de Cita</h2><p>Hola {$datos_correo['nombre_cliente']}, tu cita ha sido agendada con éxito.</p><ul><li><strong>Negocio:</strong> {$datos_correo['nombre_negocio']}</li><li><strong>Servicio:</strong> {$datos_correo['nombre_servicio']}</li><li><strong>Fecha:</strong> {$fecha_formateada}</li><li><strong>Hora:</strong> {$hora_formateada}</li><li><strong>Notas:</strong> " . htmlspecialchars($descripcion_final) . "</li></ul><p>¡Te esperamos!</p>";
                $mail_client->send();
            }
        }
    } catch (Exception $e) {
        // No detener la ejecución si el correo falla, pero registrarlo.
        registrar_auditoria($conn, null, $id_negocio, 'EMAIL_FAIL', "Fallo al enviar correo para cita ID {$id_nueva_cita}. Error: {$e->getMessage()}");
    }
    // --- FIN: LÓGICA DE ENVÍO DE CORREO ---

    echo json_encode(['success' => true, 'message' => '¡Tu cita ha sido agendada con éxito!', 'id_cita' => $id_nueva_cita]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al agendar la cita: ' . $stmt->error]);
}
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al procesar la cita: ' . $e->getMessage()]);
}