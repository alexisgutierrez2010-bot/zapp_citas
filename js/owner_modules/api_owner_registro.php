<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.

require_once 'config.php';
require_once 'audit_log.php';
require 'vendor/autoload.php'; // Para PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Iniciar transacción para asegurar la integridad de los datos
$conn->begin_transaction();

try {
    // 1. Validar datos del negocio
    $nombre_negocio = trim($input['nombre_negocio'] ?? '');
    $telefono_negocio = trim($input['telefono_negocio'] ?? '');
    $email_negocio = trim($input['email_negocio'] ?? '');
    $id_categoria_negocio = (int)($input['id_categoria_negocio'] ?? 0);
    $id_pais = (int)($input['id_pais'] ?? 0);
    $id_estado = (int)($input['id_estado'] ?? 0);
    $direccion1 = trim($input['direccion1'] ?? '');
    $ciudad = trim($input['ciudad'] ?? '');
    $zip_code = trim($input['zip_code'] ?? '');
    $dias_trabajo = !empty($input['dias_trabajo']) ? implode(',', $input['dias_trabajo']) : '';
    $hora_inicio = trim($input['hora_inicio'] ?? '08:00');
    $hora_cierre = trim($input['hora_cierre'] ?? '18:00');
    $intervalo_minutos = (int)($input['intervalo_minutos'] ?? 30);

    // 2. Validar datos del propietario
    $nombre_usuario = trim($input['nombre_usuario'] ?? '');
    $email_usuario = trim($input['email_usuario'] ?? '');
    $password_usuario = $input['password_usuario'] ?? '';

    if (empty($nombre_negocio) || empty($telefono_negocio) || empty($email_negocio) || $id_categoria_negocio <= 0 || $id_pais <= 0 || $id_estado <= 0 || empty($nombre_usuario) || empty($email_usuario) || empty($password_usuario)) {
        throw new Exception('Todos los campos son obligatorios.', 400);
    }

    // 3. Verificar duplicados (negocio y usuario)
    $stmt_check = $conn->prepare("SELECT id_negocio FROM j102_negocios WHERE telefono = ?");
    $stmt_check->bind_param("s", $telefono_negocio);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        throw new Exception('El teléfono del negocio ya está registrado.', 409);
    }
    $stmt_check->close();

    $stmt_check = $conn->prepare("SELECT id_usuario FROM j100_usuarios WHERE nombre_usuario = ? OR correo_electronico = ?");
    $stmt_check->bind_param("ss", $nombre_usuario, $email_usuario);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        throw new Exception('El nombre de usuario o el correo electrónico del propietario ya están en uso.', 409);
    }
    $stmt_check->close();

    // 4. Crear el negocio
    // Se establecen valores por defecto razonables para el horario.
    $sql_negocio = "INSERT INTO j102_negocios (nombre_negocio, telefono, email, id_categoria_negocio, id_pais, id_estado, direccion1, ciudad, zip_code, dias_trabajo, hora_inicio, hora_cierre, intervalo_minutos, dias_prueba, fecha_registro, activo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 30, NOW(), 4)"; // 4 = Pendiente por Aprobar
    $stmt_negocio = $conn->prepare($sql_negocio);
    $stmt_negocio->bind_param("sssiiissssssi", 
        $nombre_negocio, $telefono_negocio, $email_negocio, $id_categoria_negocio, $id_pais, $id_estado, 
        $direccion1, $ciudad, $zip_code, $dias_trabajo, $hora_inicio, $hora_cierre, $intervalo_minutos);
    
    if (!$stmt_negocio->execute()) {
        throw new Exception('Error al crear el negocio: ' . $stmt_negocio->error, 500);
    }
    $id_nuevo_negocio = $stmt_negocio->insert_id;
    $stmt_negocio->close();

    // 5. Crear el usuario propietario
    $password_hash = password_hash($password_usuario, PASSWORD_DEFAULT);
    $rol = 'Propietario';
    $sql_usuario = "INSERT INTO j100_usuarios (id_negocio, nombre_usuario, correo_electronico, password_hash, rol, activo) VALUES (?, ?, ?, ?, ?, 1)";
    $stmt_usuario = $conn->prepare($sql_usuario);
    $stmt_usuario->bind_param("issss", $id_nuevo_negocio, $nombre_usuario, $email_usuario, $password_hash, $rol);

    if (!$stmt_usuario->execute()) {
        throw new Exception('Error al crear el usuario propietario: ' . $stmt_usuario->error, 500);
    }
    $id_nuevo_usuario = $stmt_usuario->insert_id;
    $stmt_usuario->close();

    // 6. Confirmar transacción y registrar auditoría
    $conn->commit();
    registrar_auditoria($conn, $id_nuevo_usuario, $id_nuevo_negocio, 'OWNER_SELF_REGISTER', "Nuevo negocio '{$nombre_negocio}' y propietario '{$nombre_usuario}' registrados desde la SPA.");

    // 7. Enviar correos de notificación
    try {
        // Obtener el correo del administrador
        $sql_admin = "SELECT correo_electronico FROM j100_usuarios WHERE rol = 'Master' LIMIT 1";
        $admin_email = $conn->query($sql_admin)->fetch_assoc()['correo_electronico'] ?? null;

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = (SMTP_SECURE == 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(SMTP_USERNAME, 'Sistema ZApp Citas');

        // Correo para el administrador
        if ($admin_email) {
            $mail->addAddress($admin_email);
            $mail->Subject = "Nuevo Negocio Pendiente de Aprobación: " . $nombre_negocio;
            $mail->Body = "Se ha registrado un nuevo negocio y está pendiente de tu aprobación.<br><br><strong>Negocio:</strong> " . htmlspecialchars($nombre_negocio) . "<br><strong>Propietario:</strong> " . htmlspecialchars($nombre_usuario) . "<br><strong>Email Propietario:</strong> " . htmlspecialchars($email_usuario) . "<br><br>Para aprobarlo, ve al panel de administración.";
            $mail->isHTML(true);
            $mail->send();
            $mail->clearAddresses();
        }

        // Correo para el nuevo propietario
        $mail->addAddress($email_usuario, $nombre_usuario);
        $mail->Subject = "Solicitud de Registro Recibida - ZApp Citas";
        $mail->Body = "Hola " . htmlspecialchars($nombre_usuario) . ",<br><br>Hemos recibido tu solicitud para registrar el negocio '" . htmlspecialchars($nombre_negocio) . "'.<br>Tu cuenta está ahora pendiente de aprobación por un administrador. Recibirás una notificación una vez que sea activada.<br><br>Gracias por unirte a ZApp Citas.";
        $mail->send();

    } catch (Exception $e) {
        // No detener el proceso si el correo falla, pero registrarlo para depuración.
        error_log("Error al enviar correos de registro: " . $e->getMessage());
    }

    echo json_encode(['success' => true, 'message' => '¡Tu negocio y tu cuenta han sido creados! Un administrador revisará tu solicitud para activarla.']);

} catch (Exception $e) {
    $conn->rollback();
    $codigo_error = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($codigo_error);
    echo json_encode(['error' => $e->getMessage()]);
}
?>