<?php
// Revisado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM en fecha Nov/20/2025 //
session_start();
require_once 'config.php';
require_once 'audit_log.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// --- Lógica para obtener datos del perfil (GET) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id_cliente = isset($_GET['id_cliente']) ? (int)$_GET['id_cliente'] : 0;

    if ($id_cliente <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de cliente no válido.']);
        exit;
    }

    $sql = "SELECT 
                nombre_completo, correo_electronico, numero_celular, id_pais, id_estado,
                direccion1, direccion2, ciudad, zip_code
            FROM j106_clientes 
            WHERE id_cliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $result = $stmt->get_result();
    $cliente = $result->fetch_assoc();

    if (!$cliente) {
        http_response_code(404);
        echo json_encode(['error' => 'Cliente no encontrado.']);
        exit;
    }

    echo json_encode($cliente);
    $stmt->close();
}

// --- Lógica para actualizar datos del perfil (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $id_cliente = (int)($input['id_cliente'] ?? 0);
    $nombre = trim($input['nombre_completo'] ?? '');
    $email = trim($input['correo_electronico'] ?? '');
    $id_pais = (int)($input['id_pais'] ?? 0);
    $id_estado = (int)($input['id_estado'] ?? 0);
    $direccion1 = trim($input['direccion1'] ?? '');
    $direccion2 = trim($input['direccion2'] ?? '');
    $ciudad = trim($input['ciudad'] ?? '');
    $zip_code = trim($input['zip_code'] ?? '');

    if ($id_cliente <= 0 || empty($nombre) || empty($email) || $id_pais <= 0 || $id_estado <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Todos los campos son obligatorios.']);
        exit;
    }

    // Verificar que el email no esté en uso por OTRO cliente
    $sql_check = "SELECT id_cliente FROM j106_clientes WHERE correo_electronico = ? AND id_cliente != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("si", $email, $id_cliente);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['error' => 'El correo electrónico ya está en uso por otro cliente.']);
        exit;
    }
    $stmt_check->close();

    // Actualizar los datos
    $sql_update = "UPDATE j106_clientes SET 
                        nombre_completo = ?, correo_electronico = ?, 
                        direccion1 = ?, direccion2 = ?, ciudad = ?, zip_code = ?,
                        id_pais = ?, id_estado = ? 
                   WHERE id_cliente = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ssssssiii", 
        $nombre, $email, $direccion1, $direccion2, $ciudad, $zip_code,
        $id_pais, $id_estado, $id_cliente
    );

    if ($stmt_update->execute()) {
        registrar_auditoria($conn, null, null, 'CLIENT_UPDATE_PROFILE', "Cliente ID {$id_cliente} actualizó su perfil.");

        // Enviar correo de notificación
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
            $mail->setFrom(SMTP_USERNAME, 'ZApp Citas');
            $mail->addAddress($email, $nombre);
            $mail->isHTML(true);
            $mail->Subject = 'Tu perfil ha sido actualizado';
            $mail->Body    = "Hola " . htmlspecialchars($nombre) . ",<br><br>Te informamos que los datos de tu perfil en ZApp Citas han sido actualizados con éxito.<br><br>Si no realizaste este cambio, por favor, ponte en contacto con nosotros.<br><br>Gracias.";
            $mail->send();
        } catch (Exception $e) {
            // Si el correo falla, no detenemos el proceso, pero podríamos registrarlo.
            // Por ahora, la actualización del perfil es más importante.
            // Opcional: registrar error de email en un log separado.
        }

        echo json_encode(['success' => true, 'message' => 'Perfil actualizado con éxito.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo actualizar el perfil.']);
    }
    $stmt_update->close();
}
?>