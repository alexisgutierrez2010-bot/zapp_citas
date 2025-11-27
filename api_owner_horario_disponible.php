<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
session_start();
require_once 'api_owner_session_check.php'; // Guardián de sesión y timeout
header('Content-Type: application/json'); // CORRECCIÓN: La cabecera se establece ANTES de cualquier lógica.
require_once 'config.php';

// --- PROCESO AUTOMÁTICO DE ACTUALIZACIÓN DE ESTADO ---
// Este proceso se ejecuta de forma segura después de que la cabecera JSON ha sido establecida.
$sql_update_vencidas = "UPDATE j108_citas SET estado_cita = 'Vencida' WHERE fecha_hora_inicio < NOW() AND estado_cita = 'Pendiente' AND id_negocio = ?";
$stmt_update = $conn->prepare($sql_update_vencidas);
$stmt_update->bind_param("i", $_SESSION['owner_id_negocio']);
$stmt_update->execute();
$stmt_update->close();

$id_negocio_session = $_SESSION['owner_id_negocio'];
$fecha_str = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

try {
    $fecha = new DateTime($fecha_str);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato de fecha no válido.']);
    exit;
}

$response_data = [
    'fecha' => $fecha->format('Y-m-d'),
    'slots' => [],
    'info_negocio' => []
];

// 1. Obtener configuración del negocio
$sql_negocio = "SELECT hora_inicio, hora_cierre, intervalo_minutos, dias_trabajo FROM j102_negocios WHERE id_negocio = ?";
$stmt_negocio = $conn->prepare($sql_negocio);
$stmt_negocio->bind_param("i", $id_negocio_session);
$stmt_negocio->execute();
$result_negocio = $stmt_negocio->get_result();
$config_negocio = $result_negocio->fetch_assoc();
$stmt_negocio->close();

if (!$config_negocio) {
    http_response_code(404);
    echo json_encode(['error' => 'Configuración del negocio no encontrada.']);
    exit;
}

$response_data['info_negocio'] = $config_negocio;

// 2. Verificar si es un día de trabajo
$dia_semana_num = $fecha->format('N'); // 1 (para lunes) a 7 (para domingo)
$dias_trabajo_arr = explode(',', $config_negocio['dias_trabajo']);

if (!in_array($dia_semana_num, $dias_trabajo_arr)) {
    echo json_encode($response_data); // No hay slots si no es día de trabajo
    exit;
}

// 3. Obtener citas existentes para la fecha
$citas_existentes = [];
$sql_citas = "SELECT 
                c.id_cita, c.fecha_hora_inicio, c.fecha_hora_fin, c.estado_cita, c.descripcion_trabajo,
                cl.nombre_completo AS nombre_cliente, cl.numero_celular AS telefono_cliente,
                s.nombre_servicio, c.tipo_cita,
                s.duracion_valor, s.duracion_unidad -- CAMPOS FALTANTES AÑADIDOS
              FROM j108_citas c
              JOIN j106_clientes cl ON c.id_cliente = cl.id_cliente
              LEFT JOIN j104_servicios s ON c.id_servicio = s.id_servicio
              WHERE c.id_negocio = ? AND DATE(c.fecha_hora_inicio) = ?
              ORDER BY c.fecha_hora_inicio ASC";
$stmt_citas = $conn->prepare($sql_citas);
$stmt_citas->bind_param("is", $id_negocio_session, $fecha_str);
$stmt_citas->execute();
$result_citas = $stmt_citas->get_result();
while ($row = $result_citas->fetch_assoc()) {
    $citas_existentes[] = $row;
}
$stmt_citas->close();

// 4. Generar slots de tiempo y superponer citas
$hora_inicio_dt = new DateTime($fecha_str . ' ' . $config_negocio['hora_inicio']);
$hora_cierre_dt = new DateTime($fecha_str . ' ' . $config_negocio['hora_cierre']);
$intervalo = new DateInterval('PT' . $config_negocio['intervalo_minutos'] . 'M');

$current_slot_start = clone $hora_inicio_dt;

while ($current_slot_start < $hora_cierre_dt) {
    $current_slot_end = clone $current_slot_start;
    $current_slot_end->add($intervalo);

    $slot_info = [
        'start_time' => $current_slot_start->format('H:i'),
        'end_time' => $current_slot_end->format('H:i'),
        'status' => 'available', // Por defecto, disponible
        'cita' => null
    ];

    // Verificar si este slot está ocupado por una cita
    foreach ($citas_existentes as $cita) {
        $cita_start = new DateTime($cita['fecha_hora_inicio']);
        $cita_end = new DateTime($cita['fecha_hora_fin']);

        // Si el inicio del slot está dentro de la cita, o el inicio de la cita está dentro del slot
        if (($current_slot_start >= $cita_start && $current_slot_start < $cita_end) ||
            ($cita_start >= $current_slot_start && $cita_start < $current_slot_end)) {
            
            $slot_info['status'] = 'booked';
            // CORRECCIÓN LÓGICA: Usar la hora de inicio y fin REAL de la cita para el slot.
            $slot_info['start_time'] = $cita_start->format('H:i');
            $slot_info['end_time'] = $cita_end->format('H:i');
            $slot_info['cita'] = [
                'id_cita' => $cita['id_cita'],
                'nombre_cliente' => $cita['nombre_cliente'],
                'nombre_servicio' => $cita['nombre_servicio'],
                'estado_cita' => $cita['estado_cita'],
                'duracion_valor' => $cita['duracion_valor'],
                'duracion_unidad' => $cita['duracion_unidad'],
            ];
            // SALTO EN EL TIEMPO: Movemos el cursor al final de la cita encontrada.
            $current_slot_start = clone $cita_end;
            break; 
        }
    }

    $response_data['slots'][] = $slot_info;

    // Si el slot no fue 'booked', avanzamos el intervalo normal. Si lo fue, el cursor ya se movió.
    if ($slot_info['status'] === 'available') {
        $current_slot_start->add($intervalo);
    }
}

echo json_encode($response_data);
?>