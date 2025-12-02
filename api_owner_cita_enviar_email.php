<?php
// Elaborado por GEMINI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update: Nov-29-2025.

// --- VERSIÓN SIMPLIFICADA ---

// --- SECUENCIA DE CARGA ESTÁNDAR (IGUAL A OTROS SCRIPTS FUNCIONALES) ---
session_start();
require_once 'api_owner_session_check.php';
header('Content-Type: application/json');
// require_once 'config.php'; // ELIMINADO: Ahora lo carga api_owner_session_check.php

// --- SOLUCIÓN: Restaurar la carga de dependencias de PHPMailer ---
// 1. Incluir el autoloader de Composer para que PHP sepa dónde encontrar las clases.
require_once __DIR__ . '/vendor/autoload.php';
// 2. Declarar las clases que se van a usar en este script.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id_cita']) || !isset($data['accion'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan parámetros requeridos: id_cita y accion.']);
    exit;
}

$id_cita = $data['id_cita'];
$accion = $data['accion'];
$id_negocio_session = $_SESSION['owner_id_negocio'];

// 1. Obtener detalles de la cita y del negocio
$sql = "SELECT 
        c.fecha_hora_inicio,
        c.fecha_hora_fin,
        cl.nombre_completo AS nombre_cliente,
        cl.correo_electronico AS email_cliente,
        IF(c.tipo_cita = 'Reunion', c.descripcion_trabajo, s.nombre_servicio) AS titulo_evento,
        c.tipo_cita,
        n.nombre_negocio,
        n.email AS email_negocio,
        n.telefono AS telefono_negocio,
        n.direccion1,
        n.ciudad
    FROM j108_citas c
    JOIN j106_clientes cl ON c.id_cliente = cl.id_cliente
    LEFT JOIN j104_servicios s ON c.id_servicio = s.id_servicio
    JOIN j102_negocios n ON c.id_negocio = n.id_negocio
    WHERE c.id_cita = ? AND c.id_negocio = ?";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al preparar la consulta de la cita.']);
    exit;
}
$stmt->bind_param("ii", $id_cita, $id_negocio_session);
$stmt->execute();
$result = $stmt->get_result();
$cita_details = $result->fetch_assoc();
$stmt->close();

if (!$cita_details) {
    http_response_code(404);
    echo json_encode(['error' => 'Cita no encontrada o no pertenece a este negocio.']);
    exit;
}

if (empty($cita_details['email_cliente'])) {
    http_response_code(400);
    echo json_encode(['error' => 'El cliente no tiene una dirección de correo electrónico registrada.']);
    exit;
}

// 2. Configurar y enviar el correo con PHPMailer
$mail = new PHPMailer(true);

try {
    // --- SOLUCIÓN: Opciones para entorno de desarrollo (XAMPP) ---
    // Esto soluciona el error "certificate verify failed" en local.
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    // Configuración del servidor SMTP (usando constantes de config.php)
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;
    
    // --- SOLUCIÓN AL ERROR "SENDER ADDRESS REJECTED" ---
    // El remitente (From) DEBE ser el mismo que el usuario de autenticación SMTP.
    $mail->setFrom(SMTP_USERNAME, $cita_details['nombre_negocio']);
    $mail->addAddress($cita_details['email_cliente'], $cita_details['nombre_cliente']);
    // Se usa Reply-To para que las respuestas del cliente lleguen al correo del negocio.
    $mail->addReplyTo($cita_details['email_negocio'], $cita_details['nombre_negocio']);
    // --- SOLUCIÓN: Añadir copia oculta al negocio ---
    if (!empty($cita_details['email_negocio'])) {
        $mail->addBCC($cita_details['email_negocio']);
    }
    
    // Contenido del correo
    $mail->isHTML(true);
    $fecha_cita = new DateTime($cita_details['fecha_hora_inicio']);
    // --- SOLUCIÓN: Usar un título de evento dinámico y seguro ---
    $titulo_evento = htmlspecialchars($cita_details['titulo_evento'] ?? 'Cita');
    $label_evento = ($cita_details['tipo_cita'] === 'Reunion') ? 'la reunión de' : 'el servicio de';
    
    // --- INICIO: Generar y adjuntar archivo iCalendar (.ics) ---
    if (in_array(strtoupper($accion), ['NUEVA', 'CONFIRMADA', 'MODIFICADA'])) {
        // --- SOLUCIÓN: Corrección de Zona Horaria para .ics ---
        // 1. Establecer la zona horaria del servidor para interpretar correctamente la hora guardada.
        $server_timezone = new DateTimeZone(date_default_timezone_get());
        // 2. Crear objetos DateTime con la zona horaria correcta y luego convertirlos a UTC para el .ics
        $fecha_inicio_utc = (new DateTime($cita_details['fecha_hora_inicio'], $server_timezone))->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
        $fecha_fin_utc = (new DateTime($cita_details['fecha_hora_fin'], $server_timezone))->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
        $uid = $id_cita . '@' . parse_url(BASE_URL, PHP_URL_HOST);
        $location = "{$cita_details['direccion1']}, {$cita_details['ciudad']}";
        $description = "Cita para {$titulo_evento}. Negocio: {$cita_details['nombre_negocio']}.";

        $icsContent = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//acticven.com//ZAppCitas//ES\r\nBEGIN:VEVENT\r\n";
        $icsContent .= "UID:{$uid}\r\nDTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\nDTSTART:{$fecha_inicio_utc}\r\nDTEND:{$fecha_fin_utc}\r\n";
        $icsContent .= "SUMMARY:" . addslashes($titulo_evento) . "\r\nDESCRIPTION:" . addslashes($description) . "\r\nLOCATION:" . addslashes($location) . "\r\n";
        $icsContent .= "ORGANIZER;CN=\"{$cita_details['nombre_negocio']}\":MAILTO:{$cita_details['email_negocio']}\r\n";
        $icsContent .= "ATTENDEE;CN=\"{$cita_details['nombre_cliente']}\";ROLE=REQ-PARTICIPANT:MAILTO:{$cita_details['email_cliente']}\r\n";
        $icsContent .= "BEGIN:VALARM\r\nTRIGGER:-PT30M\r\nACTION:DISPLAY\r\nDESCRIPTION:Recordatorio\r\nEND:VALARM\r\n";
        $icsContent .= "END:VEVENT\r\nEND:VCALENDAR\r\n";
        $mail->addStringAttachment($icsContent, 'invitacion.ics', 'base64', 'text/calendar; charset=utf-8; method=REQUEST');
    }
    // --- FIN: Generar y adjuntar archivo iCalendar (.ics) ---

    // Personalizar asunto y cuerpo según la acción
    switch ($accion) {
        case 'NUEVA':
            $mail->Subject = 'Confirmación de tu cita en ' . $cita_details['nombre_negocio'];
            $mail->Body    = "Hola {$cita_details['nombre_cliente']},<br><br>Tu cita para {$label_evento} <b>{$titulo_evento}</b> ha sido confirmada para el día <b>" . $fecha_cita->format('d/m/Y') . "</b> a las <b>" . $fecha_cita->format('h:i A') . "</b>.<br><br>Te esperamos en {$cita_details['direccion1']}, {$cita_details['ciudad']}.<br>Atentamente,<br>El equipo de {$cita_details['nombre_negocio']}.";
            break;
        case 'CANCELADA':
            $mail->Subject = 'Notificación de cancelación de cita';
            $mail->Body    = "Hola {$cita_details['nombre_cliente']},<br><br>Lamentamos informarte que tu cita para {$label_evento} <b>{$titulo_evento}</b> del día <b>" . $fecha_cita->format('d/m/Y') . "</b> a las <b>" . $fecha_cita->format('h:i A') . "</b> ha sido cancelada.<br><br>Por favor, contáctanos si deseas reagendar.<br>Atentamente,<br>El equipo de {$cita_details['nombre_negocio']}.";
            break;
        case 'MODIFICADA':
            $mail->Subject = 'Actualización de tu cita en ' . $cita_details['nombre_negocio'];
            $mail->Body    = "Hola {$cita_details['nombre_cliente']},<br><br>Te informamos que tu cita para {$label_evento} <b>{$titulo_evento}</b> ha sido actualizada. Los detalles son: <b>" . $fecha_cita->format('d/m/Y') . "</b> a las <b>" . $fecha_cita->format('h:i A') . "</b>.<br><br>Atentamente,<br>El equipo de {$cita_details['nombre_negocio']}.";
            break;
        case 'COMPLETADA':
            $mail->Subject = 'Resumen de tu servicio en ' . $cita_details['nombre_negocio'];
            $mail->Body    = "Hola {$cita_details['nombre_cliente']},<br><br>¡Gracias por tu visita! Esperamos que hayas quedado satisfecho/a con tu cita para {$label_evento} <b>{$titulo_evento}</b>.<br><br>¡Esperamos verte de nuevo pronto!<br>Atentamente,<br>El equipo de {$cita_details['nombre_negocio']}.";
            break;
        default:
            $mail->Subject = 'Notificación sobre tu cita en ' . $cita_details['nombre_negocio'];
            $mail->Body    = "Hola {$cita_details['nombre_cliente']},<br><br>Este es un correo informativo sobre tu cita para {$label_evento} <b>{$titulo_evento}</b> del día <b>" . $fecha_cita->format('d/m/Y') . "</b> a las <b>" . $fecha_cita->format('h:i A') . "</b>.<br><br>Atentamente,<br>El equipo de {$cita_details['nombre_negocio']}.";
            break;
    }
    
    $mail->send();
    
    // Actualizar el contador de emails enviados en la base de datos
    $update_sql = "UPDATE j108_citas SET IN_EMAIL = IN_EMAIL + 1 WHERE id_cita = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $id_cita);
    $update_stmt->execute();
    $update_stmt->close();
    
    echo json_encode(['success' => true, 'message' => 'Correo enviado exitosamente.']);

} catch (Exception $e) {
    // SOLUCIÓN: Manejo de error SMTP robusto.
    // Se registra el error técnico completo en el log del servidor para depuración.
    // Se muestra un mensaje genérico y amigable al usuario.
    http_response_code(500);
    error_log("Fallo al enviar correo manual para cita ID {$id_cita}: " . $mail->ErrorInfo);
    echo json_encode(['error' => "No se pudo enviar la notificación por correo. Revise la configuración SMTP o los logs del servidor."]);
}
?>
