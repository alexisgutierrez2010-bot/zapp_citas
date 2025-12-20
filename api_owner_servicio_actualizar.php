<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
session_start(); // RESTAURADO: El script principal es responsable de iniciar la sesión.
require_once 'config.php';
require_once 'audit_log.php';

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

$id_servicio = (int)($input['id_servicio'] ?? 0);
$nombre_servicio = trim($input['nombre_servicio'] ?? '');
$duracion_valor = (int)($input['duracion_valor'] ?? 0);
$duracion_unidad = trim($input['duracion_unidad'] ?? 'Minutos');
$precio = !empty($input['precio']) ? (float)$input['precio'] : null;
$activo = (int)($input['activo'] ?? 1); // Por defecto, al actualizar se mantiene activo

if ($id_servicio <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de servicio no válido.']);
    exit;
}

// Si solo se está cambiando el estado 'activo'
if (isset($input['activo']) && count($input) === 2) {
    $sql = "UPDATE j104_servicios SET activo = ? WHERE id_servicio = ? AND id_negocio = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $activo, $id_servicio, $id_negocio_session);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Estado del servicio actualizado con éxito.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar el estado del servicio.']);
    }
    exit;
}

$sql = "UPDATE j104_servicios SET nombre_servicio = ?, duracion_valor = ?, duracion_unidad = ?, precio = ?, activo = ? WHERE id_servicio = ? AND id_negocio = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sisdiii", $nombre_servicio, $duracion_valor, $duracion_unidad, $precio, $activo, $id_servicio, $id_negocio_session);

if ($stmt->execute()) {
    registrar_auditoria($conn, $_SESSION['owner_id_usuario'], $id_negocio_session, 'OWNER_SPA_SERVICE_UPDATE', "Propietario actualizó el servicio '{$nombre_servicio}' (ID: {$id_servicio}) desde la SPA.");
    echo json_encode(['success' => true, 'message' => 'Servicio actualizado con éxito.']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo actualizar el servicio o no se realizaron cambios.']);
}