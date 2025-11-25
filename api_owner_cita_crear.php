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

$tipo_cita = $input['tipo_cita'] ?? 'Servicio';
$id_cliente = (int)($input['id_cliente'] ?? 0);
$id_servicio = !empty($input['id_servicio']) ? (int)$input['id_servicio'] : null;
$fecha_hora_inicio_str = trim($input['fecha_hora_inicio'] ?? ''); // YYYY-MM-DD HH:MM
$descripcion_trabajo = trim($input['descripcion_trabajo'] ?? '');

if ($id_cliente <= 0 || empty($fecha_hora_inicio_str)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos para agendar la cita.']);
    exit;
}

if ($tipo_cita === 'Servicio') {
    if (empty($id_servicio)) {
        http_response_code(400);
        echo json_encode(['error' => 'Debe seleccionar un servicio para este tipo de cita.']);
        exit;
    }
    // Obtener duración del servicio para calcular fecha_hora_fin
    $sql_servicio = "SELECT duracion_valor, duracion_unidad FROM j104_servicios WHERE id_servicio = ? AND id_negocio = ?";
    $stmt_servicio = $conn->prepare($sql_servicio);
    $stmt_servicio->bind_param("ii", $id_servicio, $id_negocio_session);
    $stmt_servicio->execute();
    $result_servicio = $stmt_servicio->get_result();
    $servicio = $result_servicio->fetch_assoc();
    $stmt_servicio->close();
    if (!$servicio) {
        http_response_code(404);
        echo json_encode(['error' => 'Servicio no encontrado o no pertenece a su negocio.']);
        exit;
    }
    $duracion_valor = (int)$servicio['duracion_valor'];
    $duracion_unidad = $servicio['duracion_unidad'];
} else { // Reunión
    $duracion_valor = 60; // Duración por defecto para reuniones
    $duracion_unidad = 'Minutos';
}

$fecha_hora_inicio = new DateTime($fecha_hora_inicio_str);
$fecha_hora_fin = clone $fecha_hora_inicio;

// Calcular fecha_hora_fin basado en la duración del servicio
$interval_spec = 'PT' . $duracion_valor;
if ($duracion_unidad === 'Horas') $interval_spec .= 'H';
else if ($duracion_unidad === 'Dias') $interval_spec .= 'D';
else $interval_spec .= 'M'; // Minutos por defecto
$fecha_hora_fin->add(new DateInterval($interval_spec));

$sql = "INSERT INTO j108_citas (id_negocio, id_cliente, id_servicio, fecha_hora_inicio, fecha_hora_fin, estado_cita, descripcion_trabajo, tipo_cita) VALUES (?, ?, ?, ?, ?, 'Pendiente', ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiissss", $id_negocio_session, $id_cliente, $id_servicio, $fecha_hora_inicio->format('Y-m-d H:i:s'), $fecha_hora_fin->format('Y-m-d H:i:s'), $descripcion_trabajo, $tipo_cita);

if ($stmt->execute()) {
    $id_nueva_cita = $stmt->insert_id;
    registrar_auditoria($conn, $_SESSION['owner_id_usuario'], $id_negocio_session, 'OWNER_SPA_CITA_CREATE', "Propietario agendó nueva cita ID {$id_nueva_cita}.");

    // Si es una reunión, guardar los invitados
    if ($tipo_cita === 'Reunion' && !empty($input['invitados'])) {
        $sql_invitado = "INSERT INTO j109_invitados_cita (id_cita, nombre_invitado, correo_electronico_invitado, numero_celular_invitado) VALUES (?, ?, ?, ?)";
        $stmt_invitado = $conn->prepare($sql_invitado);
        foreach ($input['invitados'] as $invitado) {
            $stmt_invitado->bind_param("isss", $id_nueva_cita, $invitado['nombre'], $invitado['email'], $invitado['telefono']);
            $stmt_invitado->execute();
        }
        $stmt_invitado->close();
    }

    echo json_encode(['success' => true, 'message' => 'Cita agendada con éxito.', 'id_cita' => $id_nueva_cita]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al agendar la cita: ' . $stmt->error]);
}
?>