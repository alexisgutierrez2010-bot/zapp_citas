<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-20-2025).
session_start();
require_once 'api_cliente_session_check.php'; // 1. Guardián de sesión y timeout
header('Content-Type: application/json'); // 2. Establecer cabecera
require_once 'config.php'; // 3. Configuración de BD
require_once 'audit_log.php';

$input = json_decode(file_get_contents('php://input'), true);

$id_cliente = (int)($input['id_cliente'] ?? 0);
$id_negocio = (int)($input['id_negocio'] ?? 0);
$id_servicio = (int)($input['id_servicio'] ?? 0);
$fecha_hora_inicio_str = trim($input['fecha_hora_inicio'] ?? ''); // Formato YYYY-MM-DD HH:MM

if ($id_cliente <= 0 || $id_negocio <= 0 || $id_servicio <= 0 || empty($fecha_hora_inicio_str)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos para agendar la cita.']);
    exit;
}

// Seguridad: Podríamos verificar si el cliente que agenda es el que está en sesión, si tuviéramos sesión de cliente.
// Por ahora, confiamos en el ID enviado desde el frontend.

// Obtener duración del servicio para calcular fecha_hora_fin
$sql_servicio = "SELECT duracion_valor, duracion_unidad FROM j104_servicios WHERE id_servicio = ? AND id_negocio = ? AND activo = 1";
$stmt_servicio = $conn->prepare($sql_servicio);
$stmt_servicio->bind_param("ii", $id_servicio, $id_negocio);
$stmt_servicio->execute();
$result_servicio = $stmt_servicio->get_result();
$servicio = $result_servicio->fetch_assoc();
$stmt_servicio->close();

if (!$servicio) {
    http_response_code(404);
    echo json_encode(['error' => 'Servicio no encontrado o no pertenece a este negocio.']);
    exit;
}

$fecha_hora_inicio = new DateTime($fecha_hora_inicio_str);
$fecha_hora_fin = clone $fecha_hora_inicio;

// Calcular fecha_hora_fin basado en la duración del servicio
$interval_spec = 'PT' . $servicio['duracion_valor'];
if ($servicio['duracion_unidad'] === 'Horas') $interval_spec .= 'H';
else if ($servicio['duracion_unidad'] === 'Dias') $interval_spec .= 'D';
else $interval_spec .= 'M'; // Minutos por defecto
$fecha_hora_fin->add(new DateInterval($interval_spec));

$sql = "INSERT INTO j108_citas (id_negocio, id_cliente, id_servicio, fecha_hora_inicio, fecha_hora_fin, estado_cita, descripcion_trabajo) VALUES (?, ?, ?, ?, ?, 'Pendiente', 'Cita agendada por el cliente.')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiss", $id_negocio, $id_cliente, $id_servicio, $fecha_hora_inicio->format('Y-m-d H:i:s'), $fecha_hora_fin->format('Y-m-d H:i:s'));

if ($stmt->execute()) {
    $id_nueva_cita = $stmt->insert_id;
    registrar_auditoria($conn, null, $id_negocio, 'CLIENT_SELF_BOOKING', "Cliente ID {$id_cliente} agendó nueva cita ID {$id_nueva_cita}.");
    echo json_encode(['success' => true, 'message' => '¡Tu cita ha sido agendada con éxito!', 'id_cita' => $id_nueva_cita]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al agendar la cita: ' . $stmt->error]);
}
?>