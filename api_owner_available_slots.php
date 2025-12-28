<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).

session_start();
require_once 'api_owner_session_check.php';
header('Content-Type: application/json');

if (!isset($_GET['fecha'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta el parámetro de fecha.']);
    exit;
}

$fecha_seleccionada_str = $_GET['fecha'];
$id_negocio = $_SESSION['owner_id_negocio'];
// NUEVO: Obtener el ID de la cita a excluir, si se proporciona (para el modo de edición).
$except_id_cita = isset($_GET['except_id_cita']) ? (int)$_GET['except_id_cita'] : 0;

try {
    $fecha_seleccionada = new DateTime($fecha_seleccionada_str);
    $dia_semana = $fecha_seleccionada->format('N'); // 1 (Lunes) a 7 (Domingo)

    // 1. Obtener configuración del negocio
    $stmt_negocio = $conn->prepare("SELECT dias_trabajo, hora_inicio, hora_cierre, intervalo_minutos FROM j102_negocios WHERE id_negocio = ?");
    $stmt_negocio->bind_param("i", $id_negocio);
    $stmt_negocio->execute();
    $negocio = $stmt_negocio->get_result()->fetch_assoc();
    $stmt_negocio->close();

    if (!$negocio) {
        throw new Exception("No se encontró la configuración del negocio.");
    }

    $dias_trabajo = explode(',', $negocio['dias_trabajo']);
    if (!in_array($dia_semana, $dias_trabajo)) {
        echo json_encode([]); // El negocio no trabaja este día, devuelve lista vacía
        exit;
    }

    // 2. Obtener citas existentes para ese día
    $citas_ocupadas = [];
    $sql_citas = "SELECT fecha_hora_inicio, fecha_hora_fin FROM j108_citas WHERE id_negocio = ? AND DATE(fecha_hora_inicio) = ? AND estado_cita NOT IN ('Cancelada', 'Rechazada')";
    $params = [$id_negocio, $fecha_seleccionada_str];
    $types = "is";
    if ($except_id_cita > 0) {
        $sql_citas .= " AND id_cita != ?";
        $params[] = $except_id_cita;
        $types .= "i";
    }
    $stmt_citas = $conn->prepare($sql_citas);
    $stmt_citas->bind_param($types, ...$params);
    $stmt_citas->execute();
    $result_citas = $stmt_citas->get_result();
    while ($row = $result_citas->fetch_assoc()) {
        $citas_ocupadas[] = [
            'inicio' => new DateTime($row['fecha_hora_inicio']),
            'fin' => new DateTime($row['fecha_hora_fin'])
        ];
    }
    $stmt_citas->close();

    // 3. Generar todos los slots posibles y filtrar los ocupados
    $slots_disponibles = [];
    $intervalo = new DateInterval('PT' . $negocio['intervalo_minutos'] . 'M');
    $hora_actual = new DateTime($fecha_seleccionada_str . ' ' . $negocio['hora_inicio']);
    $hora_fin_jornada = new DateTime($fecha_seleccionada_str . ' ' . $negocio['hora_cierre']);

    while ($hora_actual < $hora_fin_jornada) {
        $slot_ocupado = false;
        foreach ($citas_ocupadas as $cita) {
            if ($hora_actual >= $cita['inicio'] && $hora_actual < $cita['fin']) {
                $slot_ocupado = true;
                break;
            }
        }

        if (!$slot_ocupado) {
            $slots_disponibles[] = $hora_actual->format('H:i');
        }
        $hora_actual->add($intervalo);
    }

    echo json_encode($slots_disponibles);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al calcular la disponibilidad: ' . $e->getMessage()]);
}
?>