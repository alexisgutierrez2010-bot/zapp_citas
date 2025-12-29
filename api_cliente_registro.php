<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).

require_once 'config.php';
require_once 'audit_log.php';
require_once 'vendor/autoload.php';

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
$id_negocio = (int)($input['id_negocio'] ?? 0);
$nombre_completo = trim($input['nombre_completo'] ?? '');
$correo_electronico = trim($input['correo_electronico'] ?? '');
$numero_celular = trim($input['numero_celular'] ?? '');
$direccion1 = trim($input['direccion1'] ?? '');
$direccion2 = trim($input['direccion2'] ?? '');
$ciudad = trim($input['ciudad'] ?? '');
$zip_code = trim($input['zip_code'] ?? '');
$id_pais = !empty($input['id_pais']) ? (int)$input['id_pais'] : null;
$id_estado = !empty($input['id_estado']) ? (int)$input['id_estado'] : null;
$in_email = isset($input['in_email']) && $input['in_email'] ? 1 : 0;
$in_sms = isset($input['in_sms']) && $input['in_sms'] ? 1 : 0;
$in_whatsapp = isset($input['in_whatsapp']) && $input['in_whatsapp'] ? 1 : 0;

// Los campos de dirección no son obligatorios, pero los demás sí.
if ($id_negocio <= 0 || empty($nombre_completo) || empty($correo_electronico) || empty($numero_celular)) { 
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Los campos Nombre, Correo, Teléfono y Negocio son obligatorios.']);
    exit;
}

if (!filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'El formato del correo electrónico no es válido.']);
    exit;
}

// 2. Iniciar transacción
$conn->begin_transaction();

try {
    // Verificar si el cliente ya existe para ese negocio (por número de celular)
    $sql_check = "SELECT id_cliente FROM j106_clientes WHERE numero_celular = ? AND id_negocio = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("si", $numero_celular, $id_negocio);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        throw new Exception("Ya existe un cliente registrado con este número de teléfono para este negocio.", 409);
    }
    $stmt_check->close();

    // Verificar si el cliente ya existe para ese negocio (por correo electrónico)
    $sql_check_email = "SELECT id_cliente FROM j106_clientes WHERE correo_electronico = ? AND id_negocio = ?";
    $stmt_check_email = $conn->prepare($sql_check_email);
    $stmt_check_email->bind_param("si", $correo_electronico, $id_negocio);
    $stmt_check_email->execute();
    if ($stmt_check_email->get_result()->num_rows > 0) {
        throw new Exception("Ya existe un cliente registrado con este correo electrónico para este negocio.", 409);
    }
    $stmt_check_email->close();

    // 3. Insertar el nuevo cliente
    $sql_insert = "INSERT INTO j106_clientes (id_negocio, nombre_completo, correo_electronico, numero_celular, direccion1, direccion2, ciudad, zip_code, id_pais, id_estado, in_email, in_sms, in_whatsapp, activo, fecha_registro) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("isssssssiiisii", 
        $id_negocio, $nombre_completo, $correo_electronico, $numero_celular, 
        $direccion1, $direccion2, $ciudad, $zip_code, $id_pais, $id_estado, $in_email, $in_sms, $in_whatsapp
    );
    
    if (!$stmt_insert->execute()) {
        throw new Exception("Error al registrar el cliente: " . $stmt_insert->error);
    }
    $id_nuevo_cliente = $stmt_insert->insert_id;
    $stmt_insert->close();

    // 4. Obtener datos del negocio y del propietario para los correos
    $stmt_negocio = $conn->prepare("SELECT n.nombre_negocio, n.email AS email_negocio, u.correo_electronico AS email_propietario 
                                    FROM j102_negocios n 
                                    LEFT JOIN j100_usuarios u ON n.id_negocio = u.id_negocio AND u.rol = 'Propietario'
                                    WHERE n.id_negocio = ? LIMIT 1");
    $stmt_negocio->bind_param("i", $id_negocio);
    $stmt_negocio->execute();
    $negocio_info = $stmt_negocio->get_result()->fetch_assoc();
    $stmt_negocio->close();

    // 5. Enviar correo de bienvenida al cliente (si lo permite y hay info del negocio)
    if ($in_email && $negocio_info) {
        try {
            $link_client = 'https://appcitas.acticven.com/zapp_citas/spa_client.php';

            $mail_cliente = new PHPMailer(true);
            $mail_cliente->isSMTP();
            $mail_cliente->Host = SMTP_HOST;
            $mail_cliente->SMTPAuth = true;
            $mail_cliente->Username = SMTP_USERNAME;
            $mail_cliente->Password = SMTP_PASSWORD;
            $mail_cliente->SMTPSecure = (defined('SMTP_SECURE') && SMTP_SECURE == 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail_cliente->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
            $mail_cliente->CharSet = 'UTF-8';

            $mail_cliente->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // SOLUCIÓN: Enviar desde el usuario SMTP y poner el email del negocio como dirección de respuesta.
            $fromEmail = $negocio_info['email_negocio'] ?: SMTP_USERNAME;
            $mail_cliente->setFrom(SMTP_USERNAME, $negocio_info['nombre_negocio']);
            $mail_cliente->addReplyTo($fromEmail, $negocio_info['nombre_negocio']);
            if (!empty($negocio_info['email_propietario'])) {
                $mail_cliente->addBCC($negocio_info['email_propietario']);
            }
            $mail_cliente->addAddress($correo_electronico, $nombre_completo);
            $mail_cliente->isHTML(true);
            $mail_cliente->Subject = '¡Bienvenido a ' . htmlspecialchars($negocio_info['nombre_negocio']) . '!';
            $mail_cliente->Body = "<h3>¡Hola " . htmlspecialchars($nombre_completo) . "!</h3><p>Gracias por registrarte en <strong>" . htmlspecialchars($negocio_info['nombre_negocio']) . "</strong>. Ahora puedes agendar tus citas fácilmente a través de nuestro portal.</p><p>Accede aquí: <a href='{$link_client}'>Portal de Clientes</a></p><p>¡Esperamos verte pronto!</p>";
            $mail_cliente->send();
        } catch (Exception $e) {
            registrar_auditoria($conn, null, $id_negocio, 'EMAIL_FAIL_CLIENT', "Fallo al enviar email de bienvenida al cliente ID {$id_nuevo_cliente}.");
        }
    }

    // 6. Enviar correo de notificación al propietario (si tiene un correo configurado)
    if ($negocio_info && !empty($negocio_info['email_propietario'])) {
        try {
            $mail_owner = new PHPMailer(true);
            $mail_owner->isSMTP();
            $mail_owner->Host = SMTP_HOST;
            $mail_owner->SMTPAuth = true;
            $mail_owner->Username = SMTP_USERNAME;
            $mail_owner->Password = SMTP_PASSWORD;
            $mail_owner->SMTPSecure = (defined('SMTP_SECURE') && SMTP_SECURE == 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail_owner->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
            $mail_owner->CharSet = 'UTF-8';

            $mail_owner->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $mail_owner->setFrom(SMTP_USERNAME, 'ZApp Citas - Sistema');
            $mail_owner->addAddress($negocio_info['email_propietario']);
            $mail_owner->isHTML(true);
            $mail_owner->Subject = 'Nuevo Cliente Registrado: ' . htmlspecialchars($nombre_completo);
            $mail_owner->Body = "<h3>¡Nuevo Cliente!</h3><p>Se ha registrado un nuevo cliente en tu negocio <strong>" . htmlspecialchars($negocio_info['nombre_negocio']) . "</strong>.</p><ul><li><strong>Nombre:</strong> " . htmlspecialchars($nombre_completo) . "</li><li><strong>Correo:</strong> " . htmlspecialchars($correo_electronico) . "</li><li><strong>Teléfono:</strong> " . htmlspecialchars($numero_celular) . "</li></ul>";
            $mail_owner->send();
        } catch (Exception $e) {
            registrar_auditoria($conn, null, $id_negocio, 'EMAIL_FAIL_OWNER_NOTIF', "Fallo al notificar al propietario sobre el nuevo cliente ID {$id_nuevo_cliente}.");
        }
    }

    // 5. Confirmar transacción y registrar auditoría
    $conn->commit();
    registrar_auditoria($conn, null, $id_negocio, 'CLIENT_REGISTER', "Nuevo cliente '{$nombre_completo}' (ID: {$id_nuevo_cliente}) registrado desde la SPA.");

    echo json_encode(['success' => true, 'message' => '¡Te has registrado con éxito! Ya puedes iniciar sesión.']);

} catch (Exception $e) {
    $conn->rollback();
    $http_code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
    http_response_code($http_code);
    echo json_encode(['error' => $e->getMessage()]);
}


$conn->close();
?>