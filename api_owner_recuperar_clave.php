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
    // SOLUCIÓN: Devolver una estructura consistente para que el frontend sepa que no se envió correo.
    echo json_encode([
        'message' => 'Solicitud procesada.'
    ]);
    exit;
}

// 2. Generar nueva contraseña temporal
$nueva_password = bin2hex(random_bytes(4)); // 8 caracteres hexadecimales

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
    // SOLUCIÓN: Eliminar la dependencia de traducciones y fijar los textos en español.
    $mail->Subject = 'Recuperación de Contraseña - ZApp Citas';
    $mail->Body    = '
        <html><body>
            <h2>Recuperación de Contraseña</h2>
            <p>Hola ' . htmlspecialchars($usuario['nombre_usuario']) . ',</p>
            <p>Has solicitado una nueva contraseña. Tus nuevos datos de acceso son:</p>
            <p><strong>Contraseña Temporal:</strong> ' . $nueva_password . '</p>
            <p>Te recomendamos cambiar esta contraseña después de iniciar sesión.</p>
        </body></html>';

    $mail->send();

    // SOLUCIÓN: Actualizar la contraseña en la BD SOLO DESPUÉS de que el correo se haya enviado con éxito.
    $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
    $sql_update = "UPDATE j100_usuarios SET password_hash = ? WHERE id_usuario = ?";
    $stmt_update = $conn->prepare($sql_update);
    if (!$stmt_update) {
        throw new Exception('Error al preparar la actualización de la contraseña.');
    }
    $stmt_update->bind_param("si", $password_hash, $usuario['id_usuario']);
    if (!$stmt_update->execute()) {
        // Esto es un caso raro: el correo se envió pero la BD falló. Se debe registrar.
        throw new Exception('El correo se envió, pero hubo un error al guardar la nueva contraseña.');
    }
    $stmt_update->close();

    registrar_auditoria($conn, $usuario['id_usuario'], null, 'OWNER_PASSWORD_RECOVERY', "Usuario '{$usuario['nombre_usuario']}' solicitó recuperación de contraseña.");
    // SOLUCIÓN: Devolver el correo al que se enviaron las instrucciones.
    echo json_encode([
        'message' => 'Instrucciones enviadas con éxito.',
        'email_sent_to' => $usuario['correo_electronico']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => "No se pudo enviar el correo. Error del servidor: {$mail->ErrorInfo}"]);
}