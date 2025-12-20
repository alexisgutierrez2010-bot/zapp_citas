<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
session_start(); // RESTAURADO: El script principal es responsable de iniciar la sesión.
require_once 'config.php';
require_once 'audit_log.php'; // Reactivado

header('Content-Type: application/json');

// Seguridad: Verificar que el propietario ha iniciado sesión
if (!isset($_SESSION['owner_loggedin']) || $_SESSION['owner_loggedin'] !== true) {
    // Este bloque es redundante si se usa api_owner_session_check.php, pero se deja por seguridad.
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$id_usuario_session = $_SESSION['owner_id_usuario'];
$id_negocio_session = $_SESSION['owner_id_negocio'];
$input = json_decode(file_get_contents('php://input'), true);

$nombre_usuario = trim($input['nombre_usuario'] ?? '');
$correo_electronico = trim($input['correo_electronico'] ?? '');
$current_password = $input['current_password'] ?? '';
$new_password = $input['new_password'] ?? '';

if (empty($nombre_usuario) || empty($correo_electronico)) {
    http_response_code(400);
    echo json_encode(['error' => 'El nombre de usuario y el correo son obligatorios.']);
    exit;
}

// Verificar duplicados de email (excluyendo al usuario actual)
$sql_check_email = "SELECT id_usuario FROM j100_usuarios WHERE correo_electronico = ? AND id_usuario != ?";
$stmt_check_email = $conn->prepare($sql_check_email);
$stmt_check_email->bind_param("si", $correo_electronico, $id_usuario_session);
$stmt_check_email->execute();
if ($stmt_check_email->get_result()->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'El correo electrónico ya está en uso por otro usuario.']);
    exit;
}

$conn->begin_transaction();
try {
    // Actualizar nombre y correo
    $sql_update_user = "UPDATE j100_usuarios SET nombre_usuario = ?, correo_electronico = ? WHERE id_usuario = ?";
    $stmt_update_user = $conn->prepare($sql_update_user);
    $stmt_update_user->bind_param("ssi", $nombre_usuario, $correo_electronico, $id_usuario_session);
    $stmt_update_user->execute();

    // Si se proporcionó una nueva contraseña, actualizarla
    if (!empty($new_password)) {
        if (empty($current_password)) {
            throw new Exception("Debe proporcionar su contraseña actual para cambiarla.");
        }
        $sql_pass = "SELECT password_hash FROM j100_usuarios WHERE id_usuario = ?";
        $stmt_pass = $conn->prepare($sql_pass);
        $stmt_pass->bind_param("i", $id_usuario_session);
        $stmt_pass->execute();
        $user_data = $stmt_pass->get_result()->fetch_assoc();

        if (!password_verify($current_password, $user_data['password_hash'])) {
            throw new Exception("La contraseña actual es incorrecta.");
        }

        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $sql_update_pass = "UPDATE j100_usuarios SET password_hash = ? WHERE id_usuario = ?";
        $stmt_update_pass = $conn->prepare($sql_update_pass);
        $stmt_update_pass->bind_param("si", $new_password_hash, $id_usuario_session);
        $stmt_update_pass->execute();
    }

    $conn->commit();
    registrar_auditoria($conn, $id_usuario_session, $id_negocio_session, 'OWNER_SPA_PROFILE_UPDATE', "Propietario actualizó su perfil de usuario desde la SPA."); // Reactivado
    echo json_encode(['success' => true, 'message' => 'Perfil actualizado con éxito.']);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}