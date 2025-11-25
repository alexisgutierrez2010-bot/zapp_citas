<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
// session_start(); // ELIMINADO: El guardián ya inicia la sesión.
require_once 'api_owner_session_check.php'; // 1. Guardián de sesión y timeout
header('Content-Type: application/json'); // 2. Establecer cabecera
require_once 'config.php';
require_once 'audit_log.php';

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

$id_cita = $input['id_cita'] ?? 0;
$estado_cita = $input['estado_cita'] ?? '';
$descripcion_trabajo = $input['descripcion_trabajo'] ?? '';

if ($id_cita <= 0 || empty($estado_cita)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos para actualizar la cita.']);
    exit;
}

$sql = "UPDATE j108_citas SET estado_cita = ?, descripcion_trabajo = ? WHERE id_cita = ? AND id_negocio = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssii", $estado_cita, $descripcion_trabajo, $id_cita, $id_negocio_session);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    registrar_auditoria($conn, $_SESSION['owner_id_usuario'], $id_negocio_session, 'OWNER_SPA_CITA_UPDATE', "Propietario actualizó la cita ID {$id_cita} al estado '{$estado_cita}'.");
    echo json_encode(['success' => true, 'message' => 'Cita actualizada con éxito.']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo actualizar la cita o no se realizaron cambios.']);
}
?>