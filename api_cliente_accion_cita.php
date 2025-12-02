<?php
// Revisado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM en fecha Nov/27/2025 //
require_once 'config.php';
require_once 'audit_log.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once 'vendor/autoload.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$id_cita = (int)($input['id_cita'] ?? 0);
$id_cliente = (int)($input['id_cliente'] ?? 0);
$accion = trim($input['accion'] ?? '');

if ($id_cita <= 0 || $id_cliente <= 0 || empty($accion)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos.']);
    exit;
}

// 1. Verificar que la cita pertenece al cliente (¡muy importante por seguridad!)
$sql_check = "SELECT id_cita FROM j108_citas WHERE id_cita = ? AND id_cliente = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $id_cita, $id_cliente);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows === 0) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'No tienes permiso para modificar esta cita.']);
    exit;
}
$stmt_check->close();

// 2. Determinar el nuevo estado basado en la acción
$nuevo_estado = '';
if ($accion === 'cancelar') {
    $nuevo_estado = 'Cancelada';
} elseif ($accion === 'confirmar') {
    $nuevo_estado = 'Confirmada';
}else {
    http_response_code(400);
    echo json_encode(['error' => 'Acción no válida.']);
    exit;
}

// 3. Actualizar el estado de la cita
$sql_update = "UPDATE j108_citas SET estado_cita = ? WHERE id_cita = ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("si", $nuevo_estado, $id_cita);

if ($stmt_update->execute()) {
    // Registrar auditoría según la acción
    $audit_action = ($accion === 'confirmar') ? 'CLIENT_CONFIRM_APPOINTMENT' : 'CLIENT_CANCEL_APPOINTMENT';
    $audit_message = "Cliente ID {$id_cliente} {$accion}ó la cita ID {$id_cita}.";
    registrar_auditoria($conn, null, null, $audit_action, $audit_message);
    
    // --- LÓGICA DE ENVÍO AUTOMÁTICO DE CORREO ELIMINADA SEGÚN REQUERIMIENTO ---

    $success_message = ($accion === 'confirmar') ? 'Cita confirmada con éxito.' : 'Cita cancelada con éxito.';
    echo json_encode(['success' => true, 'message' => $success_message]);

} else {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo actualizar el estado de la cita.']);
}
$stmt_update->close();
?>