<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Internacionalización completa del contenido de los correos.

// Cargar sistema de internacionalización (i18n)
session_start(); // Necesario para que el selector de idioma funcione
require_once __DIR__ . '/languages.php';

require_once 'audit_log.php';
require_once 'config.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo_electronico = trim($_POST['correo_electronico']);
    $lang_param = isset($_POST['lang']) ? '&lang=' . $_POST['lang'] : '';

    if (empty($correo_electronico) || !filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
        header("Location: olvide_clave.php?status=error&message_key=error_invalid_email" . $lang_param);
        exit();
    }

    // Buscar usuario por correo
    $sql = "SELECT id_usuario, nombre_usuario FROM j100_usuarios WHERE correo_electronico = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo_electronico);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        $id_usuario = $usuario['id_usuario'];
        $nombre_usuario = $usuario['nombre_usuario'];

        // Generar una contraseña temporal segura
        $nueva_password_temporal = bin2hex(random_bytes(4)); // 8 caracteres
        $password_hash = password_hash($nueva_password_temporal, PASSWORD_DEFAULT);

        // Actualizar la contraseña en la base de datos
        $update_sql = "UPDATE j100_usuarios SET password_hash = ? WHERE id_usuario = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $password_hash, $id_usuario);
        
        if ($update_stmt->execute()) {
            // Registrar auditoría
            registrar_auditoria($conn, $id_usuario, null, 'PASSWORD_RECOVERY', "El usuario '{$nombre_usuario}' solicitó recuperación de contraseña.");

            // Enviar correo electrónico con la nueva contraseña
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USERNAME;
                $mail->Password   = SMTP_PASSWORD;
                $mail->SMTPSecure = (SMTP_SECURE == 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = SMTP_PORT;
                $mail->CharSet    = 'UTF-8';
                
                $mail->setFrom(SMTP_USERNAME, 'Sistema ZApp Citas');
                $mail->addAddress($correo_electronico);

                $mail->isHTML(true);
                $mail->Subject = __('email_recovery_subject');
                $mail->Body    = '
                    <html><body>
                        <h2>' . __('email_recovery_title') . '</h2>
                        <p>' . str_replace('{name}', htmlspecialchars($nombre_usuario), __('email_hello')) . '</p>
                        <p>' . __('email_recovery_body') . '</p>
                        <ul>
                            <li><strong>' . __('email_recovery_user') . ':</strong> ' . htmlspecialchars($nombre_usuario) . '</li>
                            <li><strong>' . __('email_recovery_password') . ':</strong> ' . $nueva_password_temporal . '</li>
                        </ul>
                        <p>' . __('email_recovery_footer') . '</p>
                        <p>' . __('email_recovery_ignore') . '</p>
                    </body></html>';

                $mail->send();
                header("Location: olvide_clave.php?status=success&message_key=email_recovery_generic_success" . $lang_param);

            } catch (Exception $e) {
                header("Location: olvide_clave.php?status=error&message_key=operation_error" . $lang_param);
            }
        } else {
            header("Location: olvide_clave.php?status=error&message_key=operation_error" . $lang_param);
        }
        $update_stmt->close();

    } else {
        // Para no dar pistas a posibles atacantes, mostramos un mensaje genérico
        // aunque el correo no exista.
        header("Location: olvide_clave.php?status=success&message_key=email_recovery_generic_success" . $lang_param);
    }

    $stmt->close();
    exit();
}
?>