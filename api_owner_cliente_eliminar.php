<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
session_start(); // RESTAURADO: El script principal es responsable de iniciar la sesión.
require_once 'api_owner_session_check.php'; // 1. Guardián de sesión y timeout
header('Content-Type: application/json'); // 2. Establecer cabecera
require_once 'config.php';
require_once 'audit_log.php'; // Reactivado

// 3. Verificación de la conexión a la base de datos
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Error de conexión a la base de datos: ' . $conn->connect_error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$id_negocio_session = $_SESSION['owner_id_negocio'];
$input = json_decode(file_get_contents('php://input'), true);

$id_cliente = $input['id_cliente'] ?? 0;

if ($id_cliente <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de cliente no válido.']);
    exit;
}

$sql = "UPDATE j106_clientes SET activo = 0 WHERE id_cliente = ? AND id_negocio = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_cliente, $id_negocio_session);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    // Obtener nombre para auditoría
    $stmt_info = $conn->prepare("SELECT nombre_completo FROM j106_clientes WHERE id_cliente = ?");
    $stmt_info->bind_param("i", $id_cliente);
    $stmt_info->execute();
    $nombre_cliente = $stmt_info->get_result()->fetch_assoc()['nombre_completo'] ?? 'Desconocido';
    registrar_auditoria($conn, $_SESSION['owner_id_usuario'], $id_negocio_session, 'OWNER_SPA_CLIENT_DEACTIVATE', "Propietario desactivó al cliente '{$nombre_cliente}' (ID: {$id_cliente}) desde la SPA.");
    echo json_encode(['success' => true, 'message' => 'Cliente desactivado con éxito.']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo eliminar el cliente o no se encontró.']);
}
?>