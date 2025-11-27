<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
session_start();
require_once 'config.php';
require_once 'audit_log.php';

header('Content-Type: application/json');

// Seguridad: Verificar que el propietario ha iniciado sesión
if (!isset($_SESSION['owner_loggedin']) || $_SESSION['owner_loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$id_negocio_session = $_SESSION['owner_id_negocio'];
$input = json_decode(file_get_contents('php://input'), true);

$id_servicio = $input['id_servicio'] ?? 0;

if ($id_servicio <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de servicio no válido.']);
    exit;
}

$sql = "UPDATE j104_servicios SET activo = FALSE WHERE id_servicio = ? AND id_negocio = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_servicio, $id_negocio_session);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    registrar_auditoria($conn, $_SESSION['owner_id_usuario'], $id_negocio_session, 'OWNER_SPA_SERVICE_DELETE', "Propietario eliminó servicio ID {$id_servicio}.");
    echo json_encode(['success' => true, 'message' => 'Servicio eliminado con éxito.']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo eliminar el servicio o no se encontró.']);
}
?>