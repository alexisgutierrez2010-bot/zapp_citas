<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-06-2025).

session_start();
require_once 'config.php';
require_once 'audit_log.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Cargar traducciones para los correos
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'es';
$translations_common = require 'common.php';
$translations_admin = require 'admin.php';
$T = array_merge($translations_common[$lang], $translations_admin[$lang]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$telefono_negocio = trim($input['telefono'] ?? '');

if (empty($telefono_negocio)) {
    http_response_code(400);
    echo json_encode(['error' => 'El número de teléfono es obligatorio.']);
    exit;
}

// 1. Buscar el negocio y el usuario propietario asociado
$sql = "SELECT u.id_usuario, u.nombre_usuario, u.correo_electronico, n.nombre_negocio
        FROM j102_negocios n
        JOIN j100_usuarios u ON n.id_negocio = u.id_negocio
        WHERE n.telefono = ? AND u.rol = 'Propietario' AND u.activo = 1
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $telefono_negocio);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

if (!$usuario) {
    // Para no dar pistas a atacantes, siempre devolvemos un mensaje genérico de éxito.
    echo json_encode(['message' => $T['email_recovery_generic_success']]);
    exit;
}

// 2. Generar nueva contraseña temporal
$nueva_password = bin2hex(random_bytes(4)); // 8 caracteres hexadecimales
$password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);

// 3. Actualizar la contraseña en la base de datos
$sql_update = "UPDATE j100_usuarios SET password_hash = ? WHERE id_usuario = ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("si", $password_hash, $usuario['id_usuario']);

if (!$stmt_update->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al actualizar la contraseña.']);
    exit;
}
$stmt_update->close();

// 4. Enviar correo electrónico con la nueva contraseña
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = (SMTP_SECURE == 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;
    $mail->CharSet = 'UTF-8';

    $mail->setFrom(SMTP_USERNAME, 'ZApp Citas - Soporte');
    $mail->addAddress($usuario['correo_electronico'], $usuario['nombre_usuario']);

    $mail->isHTML(true);
    $mail->Subject = $T['email_recovery_subject'];
    $mail->Body = str_replace(
        ['{name}', '{user}', '{password}', '{footer_recommendation}', '{footer_ignore}'],
        [htmlspecialchars($usuario['nombre_usuario']), htmlspecialchars($usuario['nombre_usuario']), "<b>{$nueva_password}</b>", $T['email_recovery_footer'], $T['email_recovery_ignore']],
        file_get_contents('email_templates/password_recovery_template.html')
    );

    $mail->send();
    registrar_auditoria($conn, $usuario['id_usuario'], null, 'OWNER_PASSWORD_RECOVERY', "Usuario '{$usuario['nombre_usuario']}' solicitó recuperación de contraseña.");
    echo json_encode(['message' => $T['email_recovery_generic_success']]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => "No se pudo enviar el correo. Error: {$mail->ErrorInfo}"]);
}

?>