<?php
// Elaborado por GEMINI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
// LIBRERÍA CENTRALIZADA PARA EL ENVÍO DE CORREOS DE CITAS

// El autoloader de Composer ahora es cargado por los scripts que llaman a esta función
// (ej. auth_check.php o los scripts de la API del owner), evitando cargas duplicadas.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envía una notificación por correo electrónico para una cita específica.
 *
 * @param mysqli $conn La conexión a la base de datos.
 * @param int $id_cita El ID de la cita a notificar.
 * @param string $accion El tipo de notificación ('NUEVA', 'CANCELADA', 'MODIFICADA', 'COMPLETADA').
 * @param int $id_negocio El ID del negocio para validación de seguridad.
 * @return bool Devuelve true si el correo se envió (o no era necesario), false si hubo un error.
 */
function enviarNotificacionCita(mysqli $conn, int $id_cita, string $accion, int $id_negocio): bool
{
    // 1. Obtener todos los detalles necesarios en una sola consulta
    $sql = "SELECT 
                c.fecha_hora_inicio,
                c.fecha_hora_fin,
                cl.nombre_completo AS nombre_cliente,
                cl.correo_electronico AS email_cliente,
                IF(c.tipo_cita = 'Reunion', c.descripcion_trabajo, s.nombre_servicio) AS titulo_evento,
                n.nombre_negocio,
                n.email AS email_negocio,
                n.direccion1,
                n.ciudad
            FROM j108_citas c
            JOIN j106_clientes cl ON c.id_cliente = cl.id_cliente
            LEFT JOIN j104_servicios s ON c.id_servicio = s.id_servicio
            JOIN j102_negocios n ON c.id_negocio = n.id_negocio
            WHERE c.id_cita = ? AND c.id_negocio = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_cita, $id_negocio);
    $stmt->execute();
    $details = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$details || empty($details['email_cliente'])) {
        // No hay detalles, o el cliente no tiene email. No es un error, simplemente no se puede enviar.
        return true;
    }

    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP desde config.php
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;

        // Remitente y Destinatario
        $mail->setFrom(SMTP_USERNAME, $details['nombre_negocio']);
        $mail->addAddress($details['email_cliente'], $details['nombre_cliente']);
        $mail->addReplyTo($details['email_negocio'], $details['nombre_negocio']);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $fecha_cita = new DateTime($details['fecha_hora_inicio']);
        $titulo_evento = htmlspecialchars($details['titulo_evento'] ?? 'Cita');

        // --- INICIO: Generar y adjuntar archivo iCalendar (.ics) ---
        if (in_array(strtoupper($accion), ['NUEVA', 'CONFIRMADA', 'MODIFICADA'])) {
            $fecha_inicio_utc = (new DateTime($details['fecha_hora_inicio']))->format('Ymd\THis\Z');
            $fecha_fin_utc = (new DateTime($details['fecha_hora_fin']))->format('Ymd\THis\Z');
            $fecha_stamp_utc = gmdate('Ymd\THis\Z');
            $uid = $id_cita . '@' . parse_url(BASE_URL, PHP_URL_HOST); // UID único para el evento
            $location = "{$details['direccion1']}, {$details['ciudad']}";
            $description = "Cita para {$titulo_evento}. Negocio: {$details['nombre_negocio']}.";

            $icsContent = "BEGIN:VCALENDAR\r\n";
            $icsContent .= "VERSION:2.0\r\n";
            $icsContent .= "PRODID:-//acticven.com//ZAppCitas//ES\r\n";
            $icsContent .= "BEGIN:VEVENT\r\n";
            $icsContent .= "UID:{$uid}\r\n";
            $icsContent .= "DTSTAMP:{$fecha_stamp_utc}\r\n";
            $icsContent .= "DTSTART:{$fecha_inicio_utc}\r\n";
            $icsContent .= "DTEND:{$fecha_fin_utc}\r\n";
            $icsContent .= "SUMMARY:" . addslashes($titulo_evento) . "\r\n";
            $icsContent .= "DESCRIPTION:" . addslashes($description) . "\r\n";
            $icsContent .= "LOCATION:" . addslashes($location) . "\r\n";
            $icsContent .= "ORGANIZER;CN=\"{$details['nombre_negocio']}\":MAILTO:{$details['email_negocio']}\r\n";
            $icsContent .= "ATTENDEE;CN=\"{$details['nombre_cliente']}\";ROLE=REQ-PARTICIPANT:MAILTO:{$details['email_cliente']}\r\n";
            // Alarma 30 minutos antes
            $icsContent .= "BEGIN:VALARM\r\n";
            $icsContent .= "TRIGGER:-PT30M\r\n";
            $icsContent .= "ACTION:DISPLAY\r\n";
            $icsContent .= "DESCRIPTION:Recordatorio: {$titulo_evento}\r\n";
            $icsContent .= "END:VALARM\r\n";
            $icsContent .= "END:VEVENT\r\n";
            $icsContent .= "END:VCALENDAR\r\n";

            $mail->addStringAttachment($icsContent, 'invitacion.ics', 'base64', 'text/calendar; charset=utf-8; method=REQUEST');
        }
        // --- FIN: Generar y adjuntar archivo iCalendar (.ics) ---

        switch (strtoupper($accion)) {
            case 'NUEVA':
            case 'CONFIRMADA':
                $mail->Subject = 'Confirmación de tu cita en ' . $details['nombre_negocio'];
                $mail->Body    = "Hola {$details['nombre_cliente']},<br><br>Tu cita para <b>{$titulo_evento}</b> ha sido confirmada para el día <b>" . $fecha_cita->format('d/m/Y') . "</b> a las <b>" . $fecha_cita->format('h:i A') . "</b>.<br><br>Te esperamos en {$details['direccion1']}, {$details['ciudad']}.<br>Atentamente,<br>El equipo de {$details['nombre_negocio']}.";
                break;
            case 'CANCELADA':
                $mail->Subject = 'Notificación de cancelación de cita';
                $mail->Body    = "Hola {$details['nombre_cliente']},<br><br>Lamentamos informarte que tu cita para <b>{$titulo_evento}</b> del día <b>" . $fecha_cita->format('d/m/Y') . "</b> a las <b>" . $fecha_cita->format('h:i A') . "</b> ha sido cancelada.<br><br>Por favor, contáctanos si deseas reagendar.<br>Atentamente,<br>El equipo de {$details['nombre_negocio']}.";
                break;
            case 'COMPLETADA':
                $mail->Subject = 'Resumen de tu servicio en ' . $details['nombre_negocio'];
                $mail->Body    = "Hola {$details['nombre_cliente']},<br><br>¡Gracias por tu visita! Esperamos que hayas quedado satisfecho/a con tu servicio de <b>{$titulo_evento}</b>.<br><br>¡Esperamos verte de nuevo pronto!";
                break;
            default: // 'MODIFICADA' o cualquier otro caso
                $mail->Subject = 'Notificación sobre tu cita en ' . $details['nombre_negocio'];
                $mail->Body    = "Hola {$details['nombre_cliente']},<br><br>Este es un correo informativo sobre tu cita para <b>{$titulo_evento}</b> del día <b>" . $fecha_cita->format('d/m/Y') . "</b> a las <b>" . $fecha_cita->format('h:i A') . "</b>.<br><br>Atentamente,<br>El equipo de {$details['nombre_negocio']}.";
                break;
        }

        $mail->send();

        return true;
    } catch (Exception $e) {
        // El correo falló. Registramos el error para depuración pero no rompemos la ejecución.
        error_log("Función enviarNotificacionCita falló para cita ID {$id_cita}: " . $mail->ErrorInfo);
        return false;
    }
}
?>