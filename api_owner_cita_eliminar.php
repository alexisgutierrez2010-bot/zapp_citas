<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-27-2025).
// Update :Nov-28-2025).
session_start(); // RESTAURADO: El script principal es responsable de iniciar la sesión.
require_once 'api_owner_session_check.php'; // 1. Guardián de sesión
header('Content-Type: application/json');      // 2. Cabecera JSON
require_once 'config.php';
require_once 'audit_log.php'; // Reactivado

// 3. Verificación de método y conexión
if ($conn->connect_error) {
    http_response_code(500);
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

if ($id_cita <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de cita no válido.']);
    exit;
}

// 4. Ejecutar la cancelación (borrado lógico)
$sql = "UPDATE j108_citas SET estado_cita = 'Cancelada' WHERE id_cita = ? AND id_negocio = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_cita, $id_negocio_session);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    registrar_auditoria($conn, $_SESSION['owner_id_usuario'], $id_negocio_session, 'OWNER_SPA_CITA_CANCEL', "Propietario canceló la cita ID {$id_cita} desde la SPA.");
    // --- SOLUCIÓN: Se elimina la siguiente línea que causaba el error fatal. ---
    // La acción de eliminar no debe incluir ni ejecutar scripts de envío de correo.
    echo json_encode(['success' => true, 'message' => 'Cita cancelada con éxito.']);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'No se pudo cancelar la cita. Es posible que no exista o no pertenezca a tu negocio.']);
}