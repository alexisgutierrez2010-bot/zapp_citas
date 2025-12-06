<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
// 1. Incluir dependencias y configuración.
// SOLUCIÓN DEFINITIVA: Usar una ruta absoluta con __DIR__ para garantizar que el autoloader siempre se encuentre, sin importar cómo se incluya este archivo.
require_once __DIR__ . '/vendor/autoload.php';
require_once 'config.php';

// SOLUCIÓN FINAL: Declarar explícitamente las clases que se van a usar en este archivo.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function is_api_request() {
    return !empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
}

// 2. Validar el ID de la cita que viene por la URL
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !is_api_request()) {
    die('Acceso no permitido.');
}
$id_cita = isset($_POST['id_cita']) ? (int)$_POST['id_cita'] : 0;
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

if ($id_cita <= 0) {
    if (!is_api_request()) header("Location: citas.php?status=error&message=" . urlencode("No se pudo enviar el correo: ID de cita no válido."));
    return; // Salir silenciosamente en una API
}
if (empty($accion)) {
    header("Location: citas.php?status=error&message=" . urlencode("No se pudo enviar el correo: Acción no especificada."));
    exit();
}

// CORRECCIÓN: Hacer el script compatible con ambas sesiones (admin y owner).
// SOLUCIÓN: Determinar el ID de negocio correcto basado en la sesión activa, sin generar noticias.
if (isset($_SESSION['owner_loggedin']) && $_SESSION['owner_loggedin'] === true) {
    $id_negocio_session = $_SESSION['owner_id_negocio'] ?? null;
} else {
    $id_negocio_session = $_SESSION['id_negocio'] ?? null;
}

// 3. Obtener todos los datos necesarios de la base de datos
$stmt_config = $conn->prepare("SELECT * FROM j102_negocios WHERE id_negocio = ?");
$stmt_config->bind_param("i", $id_negocio_session);
$stmt_config->execute();
$result_config = $stmt_config->get_result();
$config = $result_config->fetch_assoc();
$stmt_config->close();

$sql_cita = "SELECT c.*, cl.nombre_completo, cl.correo_electronico, cl.numero_celular, s.nombre_servicio 
             FROM j108_citas c
             JOIN j106_clientes cl ON c.id_cliente = cl.id_cliente
             LEFT JOIN j104_servicios s ON c.id_servicio = s.id_servicio
             WHERE c.id_cita = ?";
$stmt_cita = $conn->prepare($sql_cita);
$stmt_cita->bind_param("i", $id_cita);
$stmt_cita->execute();
$cita_details = $stmt_cita->get_result()->fetch_assoc();
$stmt_cita->close();

if (!$cita_details) {
    if (!is_api_request()) header("Location: citas.php?status=error&message=" . urlencode("No se pudo enviar el correo: Cita no encontrada."));
    return; // Salir silenciosamente en una API
}

// Si es una reunión, obtener la lista de invitados
$invitados = [];
if ($cita_details['tipo_cita'] === 'Reunion') {
    $stmt_invitados = $conn->prepare("SELECT nombre_invitado, correo_electronico_invitado FROM j109_invitados_cita WHERE id_cita = ?");
    $stmt_invitados->bind_param("i", $id_cita);
    $stmt_invitados->execute();
    $result_invitados = $stmt_invitados->get_result();
    while ($row = $result_invitados->fetch_assoc()) {
        $invitados[] = $row;
    }
    $stmt_invitados->close();
}

// ¡VALIDACIÓN CLAVE! Verificar si el cliente desea recibir correos.
if (!$cita_details['IN_EMAIL']) {
    if (!is_api_request()) header("Location: citas_lista.php?status=success&message=" . urlencode("Acción completada, pero el cliente ha desactivado las notificaciones por correo."));
    return; // Salir silenciosamente en una API
}

// 4. Lógica de envío de correo con PHPMailer
$mail = new PHPMailer(true);

try {
    // Habilitar la salida de depuración detallada de SMTP para diagnóstico
    // 0 = off (para producción), 1 = mensajes del cliente, 2 = mensajes del cliente y servidor
    $mail->SMTPDebug = 0; // Desactivar la depuración para el modo de producción

    // Configuración del servidor SMTP desde config.php
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = (SMTP_SECURE == 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;

    // Remitente y Destinatario
    $mail->setFrom(SMTP_USERNAME, 'ZApp Citas - ' . $config['nombre_negocio']);
    $mail->addAddress($cita_details['correo_electronico'], $cita_details['nombre_completo']); // Correo del cliente
    // Añadir el correo del negocio como destinatario para que también reciba el RSVP.
    $mail->addAddress($config['email'], 'Propietario - ' . $config['nombre_negocio']);

    // Si es una reunión, añadir a los invitados en copia oculta (BCC)
    foreach ($invitados as $invitado) {
        $mail->addBCC($invitado['correo_electronico_invitado'], $invitado['nombre_invitado']);
    }

    // Contenido del correo
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $app_timezone = new DateTimeZone(date_default_timezone_get());
    $fecha_hora_inicio = new DateTime($cita_details['fecha_hora_inicio'], $app_timezone);
    $fecha_hora_fin = new DateTime($cita_details['fecha_hora_fin'], $app_timezone);
    $nombre_cliente = htmlspecialchars($cita_details['nombre_completo']);
    $nombre_negocio = htmlspecialchars($config['nombre_negocio']);

    // Determinar el título del evento/cita
    $titulo_evento = ($cita_details['tipo_cita'] === 'Reunion') 
        ? htmlspecialchars($cita_details['descripcion_trabajo']) // Tema de la reunión
        : htmlspecialchars($cita_details['nombre_servicio']); // Nombre del servicio

    // Personalizar el correo según la acción
    switch ($accion) {
        case 'NUEVA':
            $subject_key = ($cita_details['tipo_cita'] === 'Reunion') ? 'email_new_subject_meeting' : 'email_new_subject_service';
            $mail->Subject = str_replace(['{business_name}', '{event_title}'], [$nombre_negocio, $titulo_evento], __($subject_key));
            $titulo_correo = ($cita_details['tipo_cita'] === 'Reunion') ? __('email_new_title_meeting') : __('email_new_title_service');
            $mensaje_principal = __('email_new_body');
            $nota_final = '<p style="margin-top: 20px; font-style: italic; color: #555;">' . __('email_new_footer') . '</p>';
            break;
        case 'MODIFICADA':
            $subject_key = ($cita_details['tipo_cita'] === 'Reunion') ? 'email_modified_subject_meeting' : 'email_modified_subject_service';
            $mail->Subject = str_replace(['{business_name}', '{event_title}'], [$nombre_negocio, $titulo_evento], __($subject_key));
            $titulo_correo = ($cita_details['tipo_cita'] === 'Reunion') ? __('email_modified_title_meeting') : __('email_modified_title_service');
            $mensaje_principal = __('email_modified_body');
            $nota_final = '<p style="margin-top: 20px; font-style: italic; color: #555;">' . __('email_modified_footer') . '</p>';
            break;
        case 'CANCELADA':
            $subject_key = ($cita_details['tipo_cita'] === 'Reunion') ? 'email_cancelled_subject_meeting' : 'email_cancelled_subject_service';
            $mail->Subject = str_replace(['{business_name}', '{event_title}'], [$nombre_negocio, $titulo_evento], __($subject_key));
            $titulo_correo = ($cita_details['tipo_cita'] === 'Reunion') ? __('email_cancelled_title_meeting') : __('email_cancelled_title_service');
            $mensaje_principal = __('email_cancelled_body');
            $nota_final = '<p style="margin-top: 20px;">' . __('email_cancelled_footer') . '</p>';
            break;
        case 'COMPLETADA':
            // Este caso no estaba implementado, pero lo dejamos preparado
            if ($cita_details['tipo_cita'] === 'Reunion') {
                $mail->Subject = str_replace('{event_title}', $titulo_evento, __('email_completed_subject_meeting'));
                $titulo_correo = __('email_completed_title_meeting');
                $mensaje_principal = __('email_completed_body_meeting');
            } else {
                $mail->Subject = str_replace('{business_name}', $nombre_negocio, __('email_completed_subject_service'));
                $titulo_correo = __('email_completed_title_service');
                $mensaje_principal = __('email_completed_body_service');
            }
            $nota_final = '<p style="margin-top: 20px;">' . __('email_completed_footer') . '</p>';
            break;
        default:
            // Si la acción no es reconocida, no se envía el correo y se redirige con un error.
            if (!is_api_request()) header("Location: citas.php?status=error&message=" . urlencode("Acción de correo no reconocida."));
            return; // Salir silenciosamente en una API
    }

    // --- INICIO: LÓGICA RSVP (GENERACIÓN DE .ICS) ---
    if ($accion == 'NUEVA' || $accion == 'MODIFICADA' || $accion == 'CANCELADA') {
        // Convertir fechas a formato UTC para el archivo .ics
        $start_utc = (clone $fecha_hora_inicio)->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
        $end_utc = (clone $fecha_hora_fin)->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
        $timestamp_utc = (new DateTime())->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');

        // El UID debe ser único y consistente para cada cita para permitir actualizaciones y cancelaciones.
        $uid = $id_cita . "@" . ($_SERVER['SERVER_NAME'] ?? 'zappcitas.com');

        $direccion_completa = htmlspecialchars($config['direccion1'] . ', ' . $config['ciudad']);
        $descripcion_evento = "Servicio: " . htmlspecialchars($cita_details['nombre_servicio']) . ".\\n" . "Notas: " . htmlspecialchars($cita_details['descripcion_trabajo']);

        $ics_content = "BEGIN:VCALENDAR\r\n";
        $ics_content .= "VERSION:2.0\r\n";
        $ics_content .= "PRODID:-//ZAppCitas//ACTICVEN//ES\r\n";

        // Lógica para Cancelación
        if ($accion == 'CANCELADA') {
            $ics_content .= "METHOD:CANCEL\r\n";
        }

        $ics_content .= "BEGIN:VEVENT\r\n";
        $ics_content .= "UID:" . $uid . "\r\n";
        $ics_content .= "DTSTAMP:" . $timestamp_utc . "\r\n";
        $ics_content .= "DTSTART:" . $start_utc . "\r\n";
        $ics_content .= "DTEND:" . $end_utc . "\r\n";
        $ics_content .= "SUMMARY:" . $titulo_evento . " en " . $nombre_negocio . "\r\n";
        $ics_content .= "DESCRIPTION:" . $descripcion_evento . "\r\n";
        $ics_content .= "LOCATION:" . $direccion_completa . "\r\n";

        // Lógica para Cancelación
        if ($accion == 'CANCELADA') {
            $ics_content .= "STATUS:CANCELLED\r\n";
        }

        // Añadir participantes (organizador y asistente)
        $ics_content .= "ORGANIZER;CN=\"" . $nombre_negocio . "\":mailto:" . $config['email'] . "\r\n";
        $ics_content .= "ATTENDEE;CN=\"" . $nombre_cliente . "\";ROLE=REQ-PARTICIPANT:mailto:" . $cita_details['correo_electronico'] . "\r\n";
        // Añadir invitados al archivo ICS
        foreach ($invitados as $invitado) {
            $nombre_invitado_ics = htmlspecialchars($invitado['nombre_invitado']);
            $ics_content .= "ATTENDEE;CN=\"" . $nombre_invitado_ics . "\";ROLE=REQ-PARTICIPANT:mailto:" . $invitado['correo_electronico_invitado'] . "\r\n";
        }

        // No añadir alarma si la cita está cancelada
        if ($accion != 'CANCELADA') {
            $ics_content .= "BEGIN:VALARM\r\n";
            $ics_content .= "TRIGGER:-PT30M\r\n"; // Recordatorio 30 minutos antes
            $ics_content .= "ACTION:DISPLAY\r\n";
            $ics_content .= "DESCRIPTION:Recordatorio de tu cita en " . $nombre_negocio . "\r\n";
            $ics_content .= "END:VALARM\r\n";
        }

        $ics_content .= "END:VEVENT\r\n";
        $ics_content .= "END:VCALENDAR\r\n";

        // Adjuntar el archivo .ics al correo
        $mail->addStringAttachment($ics_content, 'cita.ics', 'base64', 'text/calendar');
    }
    // --- FIN: LÓGICA RSVP ---

    $mail->Body = '
        <html><body style="font-family: Arial, sans-serif; line-height: 1.6;">
            <h2 style="color: #333;">' . str_replace('{name}', $nombre_cliente, __('email_hello')) . '</h2>
            <p>' . $titulo_correo . '</p>
            <p>' . $mensaje_principal . '</p>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="background-color: #f2f2f2;"><td style="padding: 8px; border: 1px solid #ddd;"><strong>' . ($cita_details['tipo_cita'] === 'Reunion' ? __('email_meeting_subject') : __('email_service')) . ':</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' . $titulo_evento . '</td></tr>
                <tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>' . __('email_date') . ':</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' . $fecha_hora_inicio->format('d/m/Y') . '</td></tr>
                <tr style="background-color: #f2f2f2;"><td style="padding: 8px; border: 1px solid #ddd;"><strong>' . __('email_time') . ':</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' . $fecha_hora_inicio->format('h:i A') . '</td></tr>
            </table>
            <h3 style="color: #333; margin-top: 20px; border-bottom: 1px solid #ccc; padding-bottom: 5px;">' . __('email_client_data') . '</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>' . __('email_name') . ':</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($cita_details['nombre_completo']) . '</td></tr>
                <tr style="background-color: #f2f2f2;"><td style="padding: 8px; border: 1px solid #ddd;"><strong>' . __('email_email') . ':</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($cita_details['correo_electronico']) . '</td></tr>
                <tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>' . __('email_phone') . ':</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($cita_details['numero_celular'] ?? __('email_not_specified')) . '</td></tr>
            </table>
            ' . $nota_final . '
            <p><strong>' . $nombre_negocio . '</strong><br>' . nl2br(htmlspecialchars($config['direccion1'] ?? '')) . '<br>' . htmlspecialchars($config['telefono'] ?? '') . '</p>
        </body></html>';

    $mail->send();
    
    // --- INICIO: LÓGICA DE INCREMENTO DEL CONTADOR ---
    // Si el correo se envió con éxito, incrementamos el contador IN_EMAIL para esta cita.
    $sql_update_counter = "UPDATE j108_citas SET IN_EMAIL = IN_EMAIL + 1 WHERE id_cita = ?";
    $stmt_counter = $conn->prepare($sql_update_counter);
    $stmt_counter->bind_param("i", $id_cita);
    $stmt_counter->execute();
    $stmt_counter->close();
    // --- FIN: LÓGICA DE INCREMENTO DEL CONTADOR ---

    // Construir el mensaje de éxito basado en la acción
    $success_message = "Acción completada y correo enviado con éxito.";
    if (!is_api_request()) header("Location: citas_lista.php?status=success&message=" . urlencode($success_message));

} catch (Exception $e) {

    // Si el correo falla, redirigir con un mensaje de error claro.
    $error_message = "La acción se completó, pero hubo un error al enviar el correo de notificación. Error: {$mail->ErrorInfo}";
    if (!is_api_request()) header("Location: citas_lista.php?status=error&message=" . urlencode($error_message));

}

// No hacer exit() para permitir que el script que lo incluye continúe si es una API.
?>