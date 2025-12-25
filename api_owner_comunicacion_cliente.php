<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.

require_once 'config.php';
require_once 'audit_log.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
if (!isset($_SESSION['owner_loggedin']) || $_SESSION['owner_loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$id_cliente = $input['id_cliente'] ?? 0;
$tipo = $input['tipo'] ?? '';
$id_negocio = $_SESSION['owner_id_negocio'];
$id_usuario = $_SESSION['owner_id_usuario'];

if ($id_cliente <= 0 || $tipo !== 'email') {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros incorrectos o tipo de comunicación no soportado por el servidor.']);
    exit;
}

try {
    // 1. Obtener datos del cliente y del negocio
    $stmt_cliente = $conn->prepare("SELECT * FROM j106_clientes WHERE id_cliente = ? AND id_negocio = ?");
    $stmt_cliente->bind_param("ii", $id_cliente, $id_negocio);
    $stmt_cliente->execute();
    $cliente = $stmt_cliente->get_result()->fetch_assoc();
    $stmt_cliente->close();

    $stmt_negocio = $conn->prepare("SELECT n.nombre_negocio, n.email, u.correo_electronico AS email_propietario 
                                    FROM j102_negocios n
                                    LEFT JOIN j100_usuarios u ON n.id_negocio = u.id_negocio AND u.rol = 'Propietario'
                                    WHERE n.id_negocio = ? LIMIT 1");
    $stmt_negocio->bind_param("i", $id_negocio);
    $stmt_negocio->execute();
    $negocio_info = $stmt_negocio->get_result()->fetch_assoc();
    $stmt_negocio->close();

    if (!$cliente || !$negocio_info) {
        throw new Exception("No se encontraron los datos del cliente o del negocio.", 404);
    }

    if (empty($cliente['correo_electronico'])) {
        throw new Exception("El cliente no tiene una dirección de correo electrónico registrada.", 400);
    }

    // 2. Enviar correo de bienvenida
    $link_client = 'https://appcitas.acticven.com/zapp_citas/spa_client.php';
    $nombre_completo = $cliente['nombre_completo'];
    $correo_electronico = $cliente['correo_electronico'];
    $nombre_negocio = $negocio_info['nombre_negocio'];
    $email_negocio = $negocio_info['email'] ?: SMTP_USERNAME;

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = (defined('SMTP_SECURE') && SMTP_SECURE == 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
    $mail->CharSet = 'UTF-8';

    // SOLUCIÓN: Opciones para entorno de desarrollo (XAMPP) que evitan el error de verificación de certificado SSL.
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    // SOLUCIÓN: Enviar desde el usuario SMTP y poner el email del negocio como dirección de respuesta.
    $mail->setFrom(SMTP_USERNAME, $nombre_negocio);
    $mail->addReplyTo($email_negocio, $nombre_negocio);
    if (!empty($negocio_info['email_propietario'])) {
        $mail->addBCC($negocio_info['email_propietario']);
    }

    $mail->addAddress($correo_electronico, $nombre_completo);
    $mail->isHTML(true);
    $mail->Subject = 'Bienvenido/a a ' . htmlspecialchars($nombre_negocio);
    $mail->Body = "<h3>¡Hola " . htmlspecialchars($nombre_completo) . "!</h3>
                   <p>Te damos la bienvenida a <strong>" . htmlspecialchars($nombre_negocio) . "</strong>.</p>
                   <p>Puedes agendar y gestionar tus citas fácilmente a través de nuestro portal de clientes.</p>
                   <p>Tus datos registrados son: <strong>Nombre:</strong> " . htmlspecialchars($cliente['nombre_completo']) . ", <strong>Teléfono:</strong> " . htmlspecialchars($cliente['numero_celular']) . ".</p>
                   <p>Accede a tu portal aquí: <a href='{$link_client}'>Portal de Clientes</a></p>
                   <p>¡Esperamos verte pronto!</p>";
    $mail->send();

    registrar_auditoria($conn, $id_usuario, $id_negocio, 'CLIENT_COMM_EMAIL', "Se envió email de bienvenida al cliente ID {$id_cliente}.");
    echo json_encode(['success' => true, 'message' => 'Correo de bienvenida enviado con éxito.']);

} catch (Exception $e) {
    $http_code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
    http_response_code($http_code);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>