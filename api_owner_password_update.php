<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-06-2025).
session_start();
require_once 'api_owner_session_check.php'; // Primero, verificar que la sesión es válida.
require_once 'config.php';                   // Luego, cargar la configuración de la BD.
require_once 'audit_log.php';                // SOLUCIÓN: Incluir el archivo de auditoría que faltaba.

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$password_actual = $input['password_actual'] ?? '';
$nueva_password = $input['nueva_password'] ?? '';

if (empty($password_actual) || empty($nueva_password)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Todos los campos son obligatorios.']);
    exit;
}

// MEJORA SUGERIDA: Validar la longitud mínima de la nueva contraseña.
if (strlen($nueva_password) < 8) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'La nueva contraseña debe tener al menos 8 caracteres.']);
    exit;
}

// Obtener el ID de usuario de la sesión segura
$id_usuario = $_SESSION['owner_id_usuario'];
$id_negocio = $_SESSION['owner_id_negocio'];

// 1. Obtener el hash de la contraseña actual del usuario desde la BD
$stmt = $conn->prepare("SELECT password_hash FROM j100_usuarios WHERE id_usuario = ? AND id_negocio = ?");
$stmt->bind_param("ii", $id_usuario, $id_negocio);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

if (!$usuario) {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'No se encontró el usuario.']);
    exit;
}

// 2. Verificar que la contraseña actual es correcta
if (!password_verify($password_actual, $usuario['password_hash'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'La contraseña actual es incorrecta.']);
    exit;
}

// 3. Generar el nuevo hash y actualizar la base de datos
$nuevo_hash = password_hash($nueva_password, PASSWORD_DEFAULT);

$update_stmt = $conn->prepare("UPDATE j100_usuarios SET password_hash = ? WHERE id_usuario = ?");
$update_stmt->bind_param("si", $nuevo_hash, $id_usuario);

if ($update_stmt->execute()) {
    registrar_auditoria($conn, $id_usuario, $id_negocio, 'OWNER_PASSWORD_CHANGE', 'El propietario cambió su contraseña.');
    http_response_code(200); // OK
    echo json_encode(['message' => 'Contraseña actualizada con éxito.']);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Hubo un error al actualizar la contraseña en la base de datos.']);
}

$update_stmt->close();
$conn->close();
