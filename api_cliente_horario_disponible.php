<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-27-2025).
session_start();
require_once 'config.php';

header('Content-Type: application/json');

$id_negocio = isset($_GET['id_negocio']) ? (int)$_GET['id_negocio'] : 0;
$id_servicio = isset($_GET['id_servicio']) ? (int)$_GET['id_servicio'] : 0;
$fecha_str = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

if ($id_negocio <= 0 || $id_servicio <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan parámetros del negocio o servicio.']);
    exit;
}

try {
    $fecha = new DateTime($fecha_str);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato de fecha no válido.']);
    exit;
}

// 1. Obtener configuración del negocio y duración del servicio
$sql_config = "SELECT n.hora_inicio, n.hora_cierre, n.dias_trabajo, s.duracion_valor, s.duracion_unidad 
               FROM j102_negocios n
               JOIN j104_servicios s ON n.id_negocio = s.id_negocio
               WHERE n.id_negocio = ? AND s.id_servicio = ? AND n.activo = 1 AND s.activo = 1";
$stmt_config = $conn->prepare($sql_config);
$stmt_config->bind_param("ii", $id_negocio, $id_servicio);
$stmt_config->execute();
$result_config = $stmt_config->get_result();
$config = $result_config->fetch_assoc();
$stmt_config->close();

if (!$config) {
    http_response_code(404);
    echo json_encode(['error' => 'Configuración del negocio o servicio no encontrada.']);
    exit;
}

// 2. Verificar si es un día de trabajo
$dia_semana_num = $fecha->format('N'); // 1 (lunes) a 7 (domingo)
$dias_trabajo_arr = explode(',', $config['dias_trabajo']);
if (!in_array($dia_semana_num, $dias_trabajo_arr)) {
    echo json_encode([]); // Devolver array vacío si no es día de trabajo
    exit;
}

// 3. Obtener citas existentes para la fecha
$citas_existentes = [];
$sql_citas = "SELECT fecha_hora_inicio, fecha_hora_fin FROM j108_citas 
              WHERE id_negocio = ? AND DATE(fecha_hora_inicio) = ? AND estado_cita NOT IN ('Cancelada', 'No Asistió')";
$stmt_citas = $conn->prepare($sql_citas);
$stmt_citas->bind_param("is", $id_negocio, $fecha_str);
$stmt_citas->execute();
$result_citas = $stmt_citas->get_result();
while ($row = $result_citas->fetch_assoc()) {
    $citas_existentes[] = [
        'start' => new DateTime($row['fecha_hora_inicio']),
        'end' => new DateTime($row['fecha_hora_fin'])
    ];
}
$stmt_citas->close();

// 4. Calcular la duración del servicio en minutos
$duracion_servicio_minutos = $config['duracion_valor'];
if ($config['duracion_unidad'] === 'Horas') {
    $duracion_servicio_minutos *= 60;
} elseif ($config['duracion_unidad'] === 'Dias') {
    $duracion_servicio_minutos *= 1440; // 24 * 60
}

// 5. Generar slots de tiempo disponibles
$slots_disponibles = [];
$hora_inicio_dt = new DateTime($fecha_str . ' ' . $config['hora_inicio']);
$hora_cierre_dt = new DateTime($fecha_str . ' ' . $config['hora_cierre']);

$current_slot_start = clone $hora_inicio_dt;

while ($current_slot_start < $hora_cierre_dt) {
    $current_slot_end = clone $current_slot_start;
    $current_slot_end->add(new DateInterval('PT' . $duracion_servicio_minutos . 'M'));

    // El slot no puede terminar después de la hora de cierre
    if ($current_slot_end > $hora_cierre_dt) {
        break;
    }

    $slot_ocupado = false;
    foreach ($citas_existentes as $cita) {
        // Comprobar si hay solapamiento
        if ($current_slot_start < $cita['end'] && $current_slot_end > $cita['start']) {
            $slot_ocupado = true;
            break;
        }
    }

    if (!$slot_ocupado) {
        $slots_disponibles[] = $current_slot_start->format('H:i');
    }

    // Avanzar al siguiente posible slot (ej. cada 15 minutos)
    $current_slot_start->add(new DateInterval('PT15M')); // Se puede ajustar este intervalo
}

echo json_encode($slots_disponibles);
?>