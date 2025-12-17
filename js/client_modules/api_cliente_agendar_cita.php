<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-14-2025).

session_start(); // CORRECCIÓN: Iniciar la sesión para poder verificar al cliente.

require_once 'config.php';
require_once 'audit_log.php';

header('Content-Type: application/json');

// Guardián de sesión: Verificar que el cliente haya iniciado sesión.
if (!isset($_SESSION['client_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Validar datos de entrada
$id_cliente = (int)($input['id_cliente'] ?? 0);
$id_negocio = (int)($input['id_negocio'] ?? 0);
$id_servicio = (int)($input['id_servicio'] ?? 0);
$fecha_hora_inicio_str = $input['fecha_hora_inicio'] ?? '';
$descripcion_trabajo = trim($input['descripcion_trabajo'] ?? '');

// Verificación de seguridad: el ID del cliente en la sesión debe coincidir con el que se envía.
if ($id_cliente !== (int)$_SESSION['client_id']) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Intento de agendar cita para otro cliente.']);
    exit;
}

if ($id_cliente <= 0 || $id_negocio <= 0 || $id_servicio <= 0 || empty($fecha_hora_inicio_str)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos para agendar la cita.']);
    exit;
}

try {
    // Obtener duración del servicio para calcular la hora de fin
    $stmt_servicio = $conn->prepare("SELECT duracion_valor, duracion_unidad FROM j104_servicios WHERE id_servicio = ?");
    $stmt_servicio->bind_param("i", $id_servicio);
    $stmt_servicio->execute();
    $servicio = $stmt_servicio->get_result()->fetch_assoc();
    $stmt_servicio->close();

    if (!$servicio) {
        throw new Exception('Servicio no encontrado.', 404);
    }

    $duracion_minutos = 0;
    if ($servicio['duracion_unidad'] === 'Minutos') $duracion_minutos = (int)$servicio['duracion_valor'];
    elseif ($servicio['duracion_unidad'] === 'Horas') $duracion_minutos = (int)$servicio['duracion_valor'] * 60;

    $fecha_hora_inicio = new DateTime($fecha_hora_inicio_str);
    $fecha_hora_fin = clone $fecha_hora_inicio;
    $fecha_hora_fin->add(new DateInterval('PT' . $duracion_minutos . 'M'));

    // Insertar la nueva cita
    $sql = "INSERT INTO j108_citas (id_cliente, id_negocio, id_servicio, fecha_hora_inicio, fecha_hora_fin, estado_cita, descripcion_trabajo, tipo_cita) VALUES (?, ?, ?, ?, ?, 'Pendiente', ?, 'Servicio')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiisss", $id_cliente, $id_negocio, $id_servicio, $fecha_hora_inicio->format('Y-m-d H:i:s'), $fecha_hora_fin->format('Y-m-d H:i:s'), $descripcion_trabajo);

    if ($stmt->execute()) {
        registrar_auditoria($conn, $id_cliente, $id_negocio, 'CLIENT_BOOKING_SUCCESS', "Cliente ID {$id_cliente} agendó cita para servicio ID {$id_servicio}.");
        echo json_encode(['success' => true, 'message' => '¡Tu cita ha sido agendada con éxito!']);
    } else {
        throw new Exception('No se pudo guardar la cita en la base de datos.', 500);
    }
    $stmt->close();
} catch (Exception $e) {
    http_response_code($e->getCode() >= 400 ? $e->getCode() : 500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>