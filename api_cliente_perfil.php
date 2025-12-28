<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
session_start();
require_once 'config.php';
require_once 'audit_log.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// --- Lógica para obtener datos del perfil (GET) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // SOLUCIÓN: Aplicar el guardián de sesión para proteger la lectura de datos y establecer la conexión a la BD.
    require_once 'api_cliente_session_check.php';
    // Usar el ID de la sesión por seguridad, no un parámetro GET.
    $id_cliente = (int)($_SESSION['client_id']);

    // La conexión $conn ya está disponible desde el guardián.
    $sql = "SELECT 
                nombre_completo, correo_electronico, numero_celular, id_pais, id_estado,
                direccion1, direccion2, ciudad, zip_code,
                in_email, in_sms, in_whatsapp
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
    // SOLUCIÓN: Aplicar el guardián de sesión para proteger la actualización.
    require_once 'api_cliente_session_check.php';

    $input = json_decode(file_get_contents('php://input'), true);

    // SOLUCIÓN: Usar el ID de la sesión por seguridad, no el del input.
    $id_cliente = (int)($_SESSION['client_id']);
    $nombre = trim($input['nombre_completo'] ?? '');
    $email = trim($input['correo_electronico'] ?? '');
    $celular = trim($input['numero_celular'] ?? ''); // AÑADIDO
    $id_pais = (int)($input['id_pais'] ?? 0);
    $id_estado = (int)($input['id_estado'] ?? 0);
    $direccion1 = trim($input['direccion1'] ?? '');
    $direccion2 = trim($input['direccion2'] ?? '');
    $ciudad = trim($input['ciudad'] ?? '');
    $zip_code = trim($input['zip_code'] ?? '');
    $in_email = isset($input['in_email']) ? 1 : 0; // AÑADIDO
    $in_sms = isset($input['in_sms']) ? 1 : 0;     // AÑADIDO
    $in_whatsapp = isset($input['in_whatsapp']) ? 1 : 0; // AÑADIDO

    if (empty($nombre) || empty($email) || $id_pais <= 0 || $id_estado <= 0) {
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

    // AÑADIDO: Verificar que el celular no esté en uso por OTRO cliente
    $sql_check_cel = "SELECT id_cliente FROM j106_clientes WHERE numero_celular = ? AND id_cliente != ?";
    $stmt_check_cel = $conn->prepare($sql_check_cel);
    $stmt_check_cel->bind_param("si", $celular, $id_cliente);
    $stmt_check_cel->execute();
    if ($stmt_check_cel->get_result()->num_rows > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['error' => 'El número de celular ya está en uso por otro cliente.']);
        exit;
    }
    $stmt_check_cel->close();

    // Actualizar los datos
    $sql_update = "UPDATE j106_clientes SET 
                        nombre_completo = ?, correo_electronico = ?, numero_celular = ?,
                        direccion1 = ?, direccion2 = ?, ciudad = ?, zip_code = ?, id_pais = ?, 
                        id_estado = ?, in_email = ?, in_sms = ?, in_whatsapp = ?
                   WHERE id_cliente = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sssssssiiiiii", 
        $nombre, $email, $celular, $direccion1, $direccion2, $ciudad, $zip_code,
        $id_pais, $id_estado, $in_email, $in_sms, $in_whatsapp, $id_cliente
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