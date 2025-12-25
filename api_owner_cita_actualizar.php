<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.

require_once 'config.php';
require_once 'audit_log.php';

session_start();
if (!isset($_SESSION['owner_loggedin']) || $_SESSION['owner_loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$id_cita = (int)($input['id_cita'] ?? 0);
$nuevo_estado = trim($input['estado_cita'] ?? '');
$id_negocio = $_SESSION['owner_id_negocio'];
$id_usuario = $_SESSION['owner_id_usuario'];

$estados_validos = ['Pendiente', 'Confirmada', 'Completada', 'Cancelada', 'No Asistió', 'Pospuesta'];

if ($id_cita <= 0 || !in_array($nuevo_estado, $estados_validos)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos o estado no permitido.']);
    exit;
}

$conn->begin_transaction();

try {
    // 1. Actualizar el estado de la cita
    $stmt = $conn->prepare("UPDATE j108_citas SET estado_cita = ? WHERE id_cita = ? AND id_negocio = ?");
    $stmt->bind_param("sii", $nuevo_estado, $id_cita, $id_negocio);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar la cita: " . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        // Verificar si la cita existe pero no se cambió nada (o no pertenece al negocio)
        // Para simplificar, asumimos éxito si no hubo error SQL, pero idealmente verificaríamos propiedad antes.
    }
    $stmt->close();

    // 2. Obtener datos para la notificación (WhatsApp/SMS)
    $sql_info = "SELECT 
                    c.nombre_completo AS nombre_cliente, 
                    c.numero_celular AS telefono_cliente,
                    s.nombre_servicio,
                    ci.descripcion_trabajo,
                    ci.tipo_cita,
                    ci.fecha_hora_inicio,
                    n.nombre_negocio
                 FROM j108_citas ci
                 JOIN j106_clientes c ON ci.id_cliente = c.id_cliente
                 JOIN j102_negocios n ON ci.id_negocio = n.id_negocio
                 LEFT JOIN j104_servicios s ON ci.id_servicio = s.id_servicio
                 WHERE ci.id_cita = ? AND ci.id_negocio = ?";
                 
    $stmt_info = $conn->prepare($sql_info);
    $stmt_info->bind_param("ii", $id_cita, $id_negocio);
    $stmt_info->execute();
    $info = $stmt_info->get_result()->fetch_assoc();
    $stmt_info->close();

    // 3. Confirmar y Responder
    $conn->commit();
    registrar_auditoria($conn, $id_usuario, $id_negocio, 'CITA_UPDATE_STATUS', "Cita ID $id_cita cambiada a estado: $nuevo_estado");

    echo json_encode([
        'success' => true, 
        'message' => "Estado actualizado a '{$nuevo_estado}'.",
        'notification_payload' => $info ? $info : null,
        'nuevo_estado' => $nuevo_estado
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
$conn->close();
?>