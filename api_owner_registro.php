<?php
// api_owner_registro.php

// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).

require_once 'config.php';
require_once 'audit_log.php';
require_once 'vendor/autoload.php'; // Cargar PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// 1. Recoger y validar datos
$nombre_negocio = trim($input['nombre_negocio'] ?? '');
$telefono_negocio = trim($input['telefono_negocio'] ?? '');
$email_negocio = trim($input['email_negocio'] ?? '');
$id_categoria_negocio = (int)($input['id_categoria_negocio'] ?? 0);
$id_pais = (int)($input['id_pais'] ?? 0);
$id_estado = (int)($input['id_estado'] ?? 0);
$direccion1 = trim($input['direccion1'] ?? '');
$ciudad = trim($input['ciudad'] ?? '');
$zip_code = trim($input['zip_code'] ?? '');
$dias_trabajo_arr = $input['dias_trabajo'] ?? [];
$hora_inicio = trim($input['hora_inicio'] ?? '');
$hora_cierre = trim($input['hora_cierre'] ?? '');
$intervalo_minutos = (int)($input['intervalo_minutos'] ?? 0);
$nombre_usuario = trim($input['nombre_usuario'] ?? '');
$email_usuario = trim($input['email_usuario'] ?? '');
$password_usuario = $input['password_usuario'] ?? '';

// Validaciones básicas
if (empty($nombre_negocio) || empty($telefono_negocio) || empty($email_negocio) || $id_categoria_negocio <= 0 || $id_pais <= 0 || $id_estado <= 0 || empty($nombre_usuario) || empty($email_usuario) || empty($password_usuario) || empty($dias_trabajo_arr)) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan datos obligatorios para el registro.']);
    exit;
}

if (!filter_var($email_negocio, FILTER_VALIDATE_EMAIL) || !filter_var($email_usuario, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Uno o más correos electrónicos no son válidos.']);
    exit;
}

$dias_trabajo_str = implode(',', $dias_trabajo_arr);

$conn->begin_transaction();

try {
    // 2. Verificar duplicados (Solo Usuario)

    // Solo verificamos que el nombre de usuario sea único. El correo puede repetirse.
    $sql_check_user = "SELECT id_usuario FROM j100_usuarios WHERE nombre_usuario = ?";
    $stmt_check_user = $conn->prepare($sql_check_user);
    $stmt_check_user->bind_param("s", $nombre_usuario);
    $stmt_check_user->execute();
    if ($stmt_check_user->get_result()->num_rows > 0) {
        throw new Exception("El nombre de usuario ya existe. Por favor elige otro.", 409);
    }
    $stmt_check_user->close();

    // 3. Insertar el nuevo negocio
    // Por defecto, el estado es '4' (Pendiente por Aprobar)
    $sql_negocio_insert = "INSERT INTO j102_negocios (nombre_negocio, telefono, email, id_categoria_negocio, id_pais, id_estado, direccion1, ciudad, zip_code, dias_trabajo, hora_inicio, hora_cierre, intervalo_minutos, activo, fecha_registro) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 4, NOW())";
    $stmt_negocio_insert = $conn->prepare($sql_negocio_insert);
    $stmt_negocio_insert->bind_param("sssiissssssii", 
        $nombre_negocio, $telefono_negocio, $email_negocio, $id_categoria_negocio, $id_pais, $id_estado, 
        $direccion1, $ciudad, $zip_code, $dias_trabajo_str, $hora_inicio, $hora_cierre, $intervalo_minutos
    );
    if (!$stmt_negocio_insert->execute()) {
        throw new Exception("Error al registrar el negocio: " . $stmt_negocio_insert->error, 500);
    }
    $id_nuevo_negocio = $stmt_negocio_insert->insert_id;
    $stmt_negocio_insert->close();

    // 4. Insertar el nuevo usuario propietario
    $password_hash = password_hash($password_usuario, PASSWORD_DEFAULT);
    $rol_propietario = 'Propietario';
    $sql_user_insert = "INSERT INTO j100_usuarios (id_negocio, nombre_usuario, correo_electronico, password_hash, rol, activo) 
                        VALUES (?, ?, ?, ?, ?, 1)";
    $stmt_user_insert = $conn->prepare($sql_user_insert);
    $stmt_user_insert->bind_param("issss", $id_nuevo_negocio, $nombre_usuario, $email_usuario, $password_hash, $rol_propietario);
    if (!$stmt_user_insert->execute()) {
        throw new Exception("Error al registrar el usuario propietario: " . $stmt_user_insert->error, 500);
    }
    $id_nuevo_usuario = $stmt_user_insert->insert_id;
    $stmt_user_insert->close();

    // 5. Confirmar transacción y registrar auditoría
    $conn->commit();
    registrar_auditoria($conn, $id_nuevo_usuario, $id_nuevo_negocio, 'OWNER_SPA_REGISTER', "Nuevo negocio '{$nombre_negocio}' y propietario '{$nombre_usuario}' registrados desde la SPA.");

    // --- INICIO: ENVÍO DE CORREO DE BIENVENIDA ---
    try {
        // Definir BASE_URL si no existe (fallback para construir enlaces)
        if (!defined('BASE_URL')) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            define('BASE_URL', $protocol . "://" . $host . $path . "/");
        }
        
        // URL fija para el portal del propietario según solicitud
        $link_owner = 'https://appcitas.acticven.com/zapp_citas/spa_owner.php'; 
        // URL fija para el portal de clientes según solicitud
        $link_client = 'https://appcitas.acticven.com/zapp_citas/spa_client.php';

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = (defined('SMTP_SECURE') && SMTP_SECURE == 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
        $mail->CharSet = 'UTF-8';

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom(SMTP_USERNAME, 'ZApp Citas - Registro');
        $mail->addAddress($email_usuario, $nombre_usuario);
        
        // Incrustar Logo
        $logoPath = __DIR__ . '/logo_zapp_citas.png';
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'logo_zapp');
        }

        $mail->isHTML(true);
        $mail->Subject = 'Bienvenido a ZApp Citas - Registro de Negocio Exitoso';
        
        // Estilos CSS en línea para compatibilidad con clientes de correo
        $style_body = "font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; background-color: #f9f9f9; padding: 20px;";
        $style_container = "max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1);";
        $style_header = "background-color: #343a40; color: #ffffff; padding: 20px; text-align: center;";
        $style_content = "padding: 30px;";
        $style_table = "width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px;";
        $style_th = "border-bottom: 2px solid #eee; padding: 10px; text-align: left; color: #555; width: 40%;";
        $style_td = "border-bottom: 1px solid #eee; padding: 10px; color: #333;";
        $style_btn = "display: inline-block; padding: 12px 24px; margin: 10px 5px; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px;";
        $style_footer = "background-color: #f1f1f1; padding: 20px; text-align: center; font-size: 12px; color: #777;";

        $cuerpo = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Bienvenido a ZApp Citas</title>
        </head>
        <body style=\"{$style_body}\">
            <div style=\"{$style_container}\">
                <div style=\"{$style_header}\">
                    <img src=\"cid:logo_zapp\" alt=\"ZApp Citas\" style=\"max-height: 50px; margin-bottom: 10px;\"><br>
                    <h2 style=\"margin: 0;\">¡Registro Exitoso!</h2>
                </div>
                <div style=\"{$style_content}\">
                    <p>Estimado/a <strong>" . htmlspecialchars($nombre_usuario) . "</strong>,</p>
                    <p>Bienvenido a <strong>ZApp Citas</strong>. Su negocio <strong>" . htmlspecialchars($nombre_negocio) . "</strong> ha sido registrado correctamente en nuestra plataforma.</p>
                    <p>A continuación, encontrará los detalles de su registro y sus credenciales de acceso:</p>
                    
                    <h3 style=\"color: #0d6efd; border-bottom: 1px solid #eee; padding-bottom: 5px;\">Datos del Negocio</h3>
                    <table style=\"{$style_table}\">
                        <tr><th style=\"{$style_th}\">Negocio</th><td style=\"{$style_td}\">" . htmlspecialchars($nombre_negocio) . "</td></tr>
                        <tr><th style=\"{$style_th}\">Teléfono</th><td style=\"{$style_td}\">" . htmlspecialchars($telefono_negocio) . "</td></tr>
                        <tr><th style=\"{$style_th}\">Email</th><td style=\"{$style_td}\">" . htmlspecialchars($email_negocio) . "</td></tr>
                        <tr><th style=\"{$style_th}\">Dirección</th><td style=\"{$style_td}\">" . htmlspecialchars($direccion1) . ", " . htmlspecialchars($ciudad) . "</td></tr>
                    </table>

                    <h3 style=\"color: #0d6efd; border-bottom: 1px solid #eee; padding-bottom: 5px;\">Credenciales de Acceso</h3>
                    <table style=\"{$style_table}\">
                        <tr><th style=\"{$style_th}\">Usuario</th><td style=\"{$style_td}\">" . htmlspecialchars($nombre_usuario) . "</td></tr>
                        <tr><th style=\"{$style_th}\">Email de Acceso</th><td style=\"{$style_td}\">" . htmlspecialchars($email_usuario) . "</td></tr>
                        <tr><th style=\"{$style_th}\">Contraseña</th><td style=\"{$style_td}\">" . htmlspecialchars($password_usuario) . "</td></tr>
                    </table>

                    <div style=\"background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-size: 14px;\">
                        <strong>Estado de la Cuenta:</strong> Pendiente por Aprobar.<br>
                        Un administrador revisará su solicitud y activará su cuenta pronto.
                    </div>

                    <p style=\"text-align: center;\">Guarde estos enlaces para acceder a la plataforma:</p>
                    
                    <div style=\"text-align: center;\">
                        <a href=\"{$link_owner}\" style=\"{$style_btn} background-color: #0d6efd;\">Acceso Propietario</a>
                        <a href=\"{$link_client}\" style=\"{$style_btn} background-color: #198754;\">Portal de Clientes</a>
                    </div>
                </div>
                <div style=\"{$style_footer}\">
                    <p>&copy; " . date("Y") . " ZApp Citas. Todos los derechos reservados.</p>
                    <p>Software development and Authorized by <a href=\"http://www.acticven.com\" style=\"color: #777; text-decoration: none;\">WWW.ACTICVEN.COM</a></p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->Body = $cuerpo;
        $mail->AltBody = "Bienvenido a ZApp Citas.\n\nNegocio: $nombre_negocio\nUsuario: $nombre_usuario\nContraseña: $password_usuario\n\nAcceso Propietario: $link_owner\nAcceso Clientes: $link_client";
        
        $mail->send();
    } catch (Exception $e) {
        // No interrumpimos el flujo si falla el correo, pero podríamos loguearlo
        registrar_auditoria($conn, $id_nuevo_usuario, $id_nuevo_negocio, 'EMAIL_FAIL_OWNER', "Fallo al enviar email de bienvenida al propietario. Error: {$e->getMessage()}");
    }
    // --- FIN: ENVÍO DE CORREO ---

    // --- INICIO: ENVÍO DE CORREO DE NOTIFICACIÓN AL ADMINISTRADOR ---
    try {
        $mail_admin = new PHPMailer(true);
        $mail_admin->isSMTP();
        $mail_admin->Host = SMTP_HOST;
        $mail_admin->SMTPAuth = true;
        $mail_admin->Username = SMTP_USERNAME;
        $mail_admin->Password = SMTP_PASSWORD;
        $mail_admin->SMTPSecure = (defined('SMTP_SECURE') && SMTP_SECURE == 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail_admin->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
        $mail_admin->CharSet = 'UTF-8';

        $mail_admin->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // El destinatario es el administrador del sistema, cuya dirección está en la configuración SMTP
        $mail_admin->setFrom(SMTP_USERNAME, 'ZApp Citas - Sistema');
        $mail_admin->addAddress(SMTP_USERNAME, 'Administrador');

        $mail_admin->isHTML(true);
        $mail_admin->Subject = 'Nuevo Negocio Registrado - Requiere Aprobación';

        $link_aprobacion = BASE_URL . 'negocios_configuracion.php?id_negocio=' . $id_nuevo_negocio;

        $cuerpo_admin = "<h2>Notificación de Nuevo Registro</h2>";
        $cuerpo_admin .= "<p>Se ha registrado un nuevo negocio en la plataforma y está pendiente de aprobación.</p>";
        $cuerpo_admin .= "<ul><li><strong>Negocio:</strong> " . htmlspecialchars($nombre_negocio) . "</li><li><strong>Propietario:</strong> " . htmlspecialchars($nombre_usuario) . "</li><li><strong>Email Propietario:</strong> " . htmlspecialchars($email_usuario) . "</li></ul>";
        $cuerpo_admin .= "<p>Para revisar y aprobar, por favor acceda al siguiente enlace:</p>";
        $cuerpo_admin .= "<p><a href='{$link_aprobacion}' style='padding: 10px 15px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Revisar y Aprobar Negocio</a></p>";

        $mail_admin->Body = $cuerpo_admin;
        $mail_admin->send();
    } catch (Exception $e) {
        // No interrumpir el flujo si este correo falla. Se registra en la auditoría.
        registrar_auditoria($conn, null, $id_nuevo_negocio, 'EMAIL_FAIL_ADMIN', "Fallo al notificar al admin sobre el nuevo negocio ID {$id_nuevo_negocio}.");
    }
    // --- FIN: ENVÍO DE CORREO AL ADMIN ---

    echo json_encode(['success' => true, 'message' => '¡Tu negocio ha sido registrado! Un administrador revisará tu solicitud y la activará pronto.']);

} catch (Exception $e) {
    $conn->rollback();
    $http_code = ($e->getCode() >= 400) ? $e->getCode() : 500;
    http_response_code($http_code);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>