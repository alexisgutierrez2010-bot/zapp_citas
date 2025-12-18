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

$id_negocio_session = $_SESSION['owner_id_negocio'];
$input = json_decode(file_get_contents('php://input'), true);

$nombre_servicio = trim($input['nombre_servicio'] ?? '');
$duracion_valor = (int)($input['duracion_valor'] ?? 0);
$duracion_unidad = trim($input['duracion_unidad'] ?? 'Minutos');
$precio = !empty($input['precio']) ? (float)$input['precio'] : null;

if (empty($nombre_servicio) || $duracion_valor <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'El nombre y la duración del servicio son obligatorios.']);
    exit;
}

$sql = "INSERT INTO j104_servicios (id_negocio, nombre_servicio, duracion_valor, duracion_unidad, precio, activo, fecha_registro) VALUES (?, ?, ?, ?, ?, TRUE, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isiss", $id_negocio_session, $nombre_servicio, $duracion_valor, $duracion_unidad, $precio);

if ($stmt->execute()) {
    $id_nuevo_servicio = $stmt->insert_id;
    registrar_auditoria($conn, $_SESSION['owner_id_usuario'], $id_negocio_session, 'OWNER_SPA_SERVICE_CREATE', "Propietario creó el servicio '{$nombre_servicio}' (ID: {$id_nuevo_servicio}) desde la SPA.");
    echo json_encode(['success' => true, 'message' => 'Servicio creado con éxito.']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al crear el servicio: ' . $stmt->error]);
}
?>