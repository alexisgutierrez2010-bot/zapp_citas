<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.

require_once 'config.php';
require_once 'audit_log.php';

session_start();
if (!isset($_SESSION['client_loggedin']) || $_SESSION['client_loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$id_cita = (int)($input['id_cita'] ?? 0);
$id_cliente_request = (int)($input['id_cliente'] ?? 0);
$accion = trim($input['accion'] ?? '');

$id_cliente_session = $_SESSION['client_id'];

if ($id_cita <= 0 || $id_cliente_request !== $id_cliente_session || !in_array($accion, ['cancelar', 'confirmar'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos o acción no permitida.']);
    exit;
}

$conn->begin_transaction();

try {
    // Verificar que la cita pertenece al cliente y está en un estado modificable
    $stmt_check = $conn->prepare("SELECT estado_cita, id_negocio FROM j108_citas WHERE id_cita = ? AND id_cliente = ?");
    $stmt_check->bind_param("ii", $id_cita, $id_cliente_session);
    $stmt_check->execute();
    $cita_actual = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$cita_actual) {
        throw new Exception("Cita no encontrada o no te pertenece.", 404);
    }

    $estado_actual = $cita_actual['estado_cita'];
    $id_negocio = $cita_actual['id_negocio'];
    $nuevo_estado = '';

    if ($accion === 'cancelar' && in_array($estado_actual, ['Pendiente', 'Confirmada'])) {
        $nuevo_estado = 'Cancelada';
    } elseif ($accion === 'confirmar' && $estado_actual === 'Pendiente') {
        $nuevo_estado = 'Confirmada';
    } else {
        throw new Exception("La acción '{$accion}' no es válida para una cita en estado '{$estado_actual}'.", 409);
    }

    // Actualizar el estado
    $stmt_update = $conn->prepare("UPDATE j108_citas SET estado_cita = ? WHERE id_cita = ?");
    $stmt_update->bind_param("si", $nuevo_estado, $id_cita);
    if (!$stmt_update->execute()) throw new Exception("Error al actualizar la cita.");
    $stmt_update->close();

    $notification_payload = null;
    // Si la acción es cancelar, preparamos el payload para notificar al propietario
    if ($accion === 'cancelar') {
        $sql_info = "SELECT n.telefono AS telefono_propietario, c.nombre_completo AS nombre_cliente, s.nombre_servicio, ci.fecha_hora_inicio FROM j108_citas ci JOIN j102_negocios n ON ci.id_negocio = n.id_negocio JOIN j106_clientes c ON ci.id_cliente = c.id_cliente LEFT JOIN j104_servicios s ON ci.id_servicio = s.id_servicio WHERE ci.id_cita = ?";
        $stmt_info = $conn->prepare($sql_info);
        $stmt_info->bind_param("i", $id_cita);
        $stmt_info->execute();
        $notification_payload = $stmt_info->get_result()->fetch_assoc();
        $stmt_info->close();
    }

    $conn->commit();
    registrar_auditoria($conn, $id_cliente_session, $id_negocio, 'CLIENT_CITA_ACTION', "Cliente cambió estado de cita ID {$id_cita} a {$nuevo_estado}.");

    echo json_encode(['success' => true, 'message' => "Cita {$nuevo_estado} con éxito.", 'notification_payload' => $notification_payload]);

} catch (Exception $e) {
    $conn->rollback();
    $http_code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
    http_response_code($http_code);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>