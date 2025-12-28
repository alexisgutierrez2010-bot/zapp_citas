<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).

require_once 'api_cliente_session_check.php';
require_once 'audit_log.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id_cita = (int)($input['id_cita'] ?? 0);
$id_cliente = $_SESSION['client_id'];
$id_negocio = $_SESSION['client_id_negocio'];

if ($id_cita <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de cita no válido.']);
    exit;
}

// Verificar que la cita pertenezca al cliente
$sql_check = "SELECT id_cita, estado_cita, fecha_hora_inicio FROM j108_citas WHERE id_cita = ? AND id_cliente = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $id_cita, $id_cliente);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$cita = $result_check->fetch_assoc();
$stmt_check->close();

if (!$cita) {
    http_response_code(404);
    echo json_encode(['error' => 'Cita no encontrada.']);
    exit;
}

// Proceder a cancelar
$sql_update = "UPDATE j108_citas SET estado_cita = 'Cancelada' WHERE id_cita = ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("i", $id_cita);

if ($stmt_update->execute()) {
    registrar_auditoria($conn, null, $id_negocio, 'CLIENT_CANCEL_APPOINTMENT', "Cliente ID {$id_cliente} canceló su cita ID {$id_cita}.");
    echo json_encode(['success' => true, 'message' => 'Cita cancelada correctamente.']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cancelar la cita.']);
}
$stmt_update->close();
?>