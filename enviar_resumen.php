<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php'; // <-- ESTA LÍNEA FALTABA
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$documentos_dir = __DIR__ . '/documentos/';

// Ahora puede venir por GET (para txt) o por POST (para pdf)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_archivo = isset($_POST['file']) ? basename($_POST['file']) : '';
} else {
    $nombre_archivo = isset($_GET['file']) ? basename($_GET['file']) : '';
}


$resumen_file_path = $documentos_dir . $nombre_archivo;
$extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));

// Validaciones de seguridad
if (empty($nombre_archivo) || !file_exists($resumen_file_path)) {
    header("Location: seleccionar_resumen.php?status=error&message=" . urlencode("Archivo no válido o no encontrado."));
    exit;
}

$mail = new PHPMailer(true);

try {
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = (SMTP_SECURE == 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    if ($extension === 'txt') {
        // --- LÓGICA PARA ARCHIVOS .TXT ---
        $lines = file($resumen_file_path, FILE_IGNORE_NEW_LINES);
        $email_from = '';
        $email_to = '';
        $subject = 'Resumen de Avances - ZApp Citas'; // Valor por defecto
        $body_lines = [];
        $is_header = true;

        foreach ($lines as $line) {
            if ($is_header && strpos(strtoupper($line), 'FROM:') === 0) {
                $email_from = trim(substr($line, 5));
            } elseif ($is_header && strpos(strtoupper($line), 'TO:') === 0) {
                $email_to = trim(substr($line, 3));
            } elseif ($is_header && strpos(strtoupper($line), 'SUBJECT:') === 0) {
                $subject = trim(substr($line, 8));
            } elseif (trim($line) !== '' || !$is_header) {
                $is_header = false;
                $body_lines[] = $line;
            }
        }
        $body = implode("\n", $body_lines);
        $mail->setFrom($email_from ?: SMTP_USERNAME, 'Sistema de Reportes ZApp Citas');
        $mail->addAddress($email_to ?: SMTP_USERNAME);
        $mail->Subject = $subject;
        $mail->Body = $body;

    } else { // Para PDF y otros tipos
        // --- LÓGICA PARA ARCHIVOS .PDF ---
        $email_to = $_POST['email_to'] ?? 'alexisgutierrez2010@gmail.com'; // Valor por defecto
        $subject = $_POST['subject'] ?? 'Envío de Documento: ' . $nombre_archivo;

        $mail->setFrom(SMTP_USERNAME, 'Sistema de Reportes ZApp Citas');
        $mail->addAddress($email_to);
        $mail->Subject = $subject;
        $mail->Body    = '<html><body><p>Saludos,</p><p>Por favor, encuentre el documento solicitado adjunto a este correo.</p><p>Atentamente,<br>Sistema ZApp Citas</p></body></html>';
        $mail->addAttachment($resumen_file_path);
    }

    // Contenido
    $mail->isHTML(true);
    $mail->AltBody = 'Para ver este mensaje, por favor, utiliza un cliente de correo compatible con HTML.';

    $mail->send();
    
    $email_to_log = $email_to ?? 'No especificado';
    registrar_auditoria($conn, $_SESSION['id_usuario'], null, 'SEND_DOCUMENT', "Se envió el documento '{$nombre_archivo}' a {$email_to_log}.");
    header("Location: seleccionar_resumen.php?status=success&message=" . urlencode("Documento '{$nombre_archivo}' enviado con éxito."));

} catch (Exception $e) {
    header("Location: seleccionar_resumen.php?status=error&message=" . urlencode("No se pudo enviar el correo. Error: {$mail->ErrorInfo}"));
}

exit;