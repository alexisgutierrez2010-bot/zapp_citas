<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-20-2025).
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Seguridad: Verificar que el propietario ha iniciado sesión
if (!isset($_SESSION['owner_loggedin']) || $_SESSION['owner_loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

$id_usuario_session = $_SESSION['owner_id_usuario'];

$sql = "SELECT nombre_usuario, correo_electronico FROM j100_usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario_session);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario) {
    http_response_code(404);
    echo json_encode(['error' => 'Usuario no encontrado.']);
    exit;
}

echo json_encode($usuario);
?>