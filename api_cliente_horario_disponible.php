<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
header('Content-Type: application/json');
require_once 'config.php';

try {
    // 1. OBTENER PARÁMETROS
    $id_negocio = isset($_GET['id_negocio']) ? (int)$_GET['id_negocio'] : 0;
    $id_servicio = isset($_GET['id_servicio']) ? (int)$_GET['id_servicio'] : 0;
    $fecha_str = isset($_GET['fecha']) ? $_GET['fecha'] : '';

    if ($id_negocio <= 0 || $id_servicio <= 0 || empty($fecha_str)) {
        throw new Exception('Parámetros incompletos.', 400);
    }

    $fecha_seleccionada = new DateTime($fecha_str);
    $dia_semana_num = $fecha_seleccionada->format('N'); // 1 (Lunes) a 7 (Domingo)

    // 2. OBTENER CONFIGURACIÓN DEL NEGOCIO (HORARIO E INTERVALO)
    $stmt_negocio = $conn->prepare("SELECT dias_trabajo, hora_inicio, hora_cierre, intervalo_minutos FROM j102_negocios WHERE id_negocio = ?");
    $stmt_negocio->bind_param("i", $id_negocio);
    $stmt_negocio->execute();
    $negocio = $stmt_negocio->get_result()->fetch_assoc();
    $stmt_negocio->close();

    if (!$negocio) {
        throw new Exception('Configuración del negocio no encontrada.', 404);
    }

    // Verificar si el negocio trabaja ese día
    $dias_trabajo = explode(',', $negocio['dias_trabajo']);
    if (!in_array($dia_semana_num, $dias_trabajo)) {
        echo json_encode([]); // Día no laborable, devolver array vacío.
        exit;
    }

    // 3. OBTENER DURACIÓN DEL SERVICIO
    $sql_servicio = "SELECT duracion_valor, duracion_unidad FROM j104_servicios WHERE id_servicio = ?";
    $stmt_servicio = $conn->prepare($sql_servicio);
    $stmt_servicio->bind_param("i", $id_servicio);
    $stmt_servicio->execute();
    $servicio = $stmt_servicio->get_result()->fetch_assoc();
    $stmt_servicio->close();

    if (!$servicio) {
        throw new Exception('Servicio no encontrado.', 404);
    }

    $duracion_minutos = 0;
    switch ($servicio['duracion_unidad']) {
        case 'Minutos':
            $duracion_minutos = (int)$servicio['duracion_valor'];
            break;
        case 'Horas':
            $duracion_minutos = (int)$servicio['duracion_valor'] * 60;
            break;
        case 'Dias':
            $duracion_minutos = (int)$servicio['duracion_valor'] * 8 * 60; // Asumir jornada de 8h
            break;
    }

    $intervalo_base_minutos = (int)$negocio['intervalo_minutos'];

    if ($duracion_minutos <= 0 || $intervalo_base_minutos <= 0) {
        echo json_encode([]); // Duraciones inválidas, no se pueden generar horarios.
        exit;
    }

    // 4. OBTENER CITAS EXISTENTES PARA ESE DÍA
    $fecha_inicio_dia = $fecha_str . ' 00:00:00';
    $fecha_fin_dia = $fecha_str . ' 23:59:59';
    $sql_citas = "SELECT fecha_hora_inicio, fecha_hora_fin FROM j108_citas WHERE id_negocio = ? AND fecha_hora_inicio BETWEEN ? AND ? AND estado_cita NOT IN ('Cancelada')";
    $stmt_citas = $conn->prepare($sql_citas);
    $stmt_citas->bind_param("iss", $id_negocio, $fecha_inicio_dia, $fecha_fin_dia);
    $stmt_citas->execute();
    $result_citas = $stmt_citas->get_result();
    $citas_ocupadas = [];
    while ($row = $result_citas->fetch_assoc()) {
        $citas_ocupadas[] = [
            'inicio' => new DateTime($row['fecha_hora_inicio']),
            'fin' => new DateTime($row['fecha_hora_fin'])
        ];
    }
    $stmt_citas->close();

    // 5. GENERAR Y FILTRAR HORARIOS DISPONIBLES
    $slots_disponibles = [];
    $hora_actual = new DateTime($fecha_str . ' ' . $negocio['hora_inicio']);
    $hora_cierre = new DateTime($fecha_str . ' ' . $negocio['hora_cierre']);
    $intervalo_base = new DateInterval('PT' . $intervalo_base_minutos . 'M');

    while ($hora_actual < $hora_cierre) {
        $slot_inicio = clone $hora_actual;
        $slot_fin = clone $hora_actual;
        $slot_fin->add(new DateInterval('PT' . $duracion_minutos . 'M'));

        // El slot no puede terminar después de la hora de cierre
        if ($slot_fin > $hora_cierre) break;

        $slot_ocupado = false;
        // Comprobar si el slot que queremos agendar (desde $slot_inicio hasta $slot_fin)
        // se solapa con alguna cita ya existente.
        foreach ($citas_ocupadas as $cita_existente) {
            if ($slot_inicio < $cita_existente['fin'] && $slot_fin > $cita_existente['inicio']) {
                $slot_ocupado = true;
                break;
            }
        }

        if (!$slot_ocupado) {
            // Si no está ocupado, este horario está disponible
            $slots_disponibles[] = $slot_inicio->format('H:i');
        }

        // Avanzamos al siguiente posible inicio de cita, basado en el intervalo del negocio.
        $hora_actual->add($intervalo_base);
    }

    echo json_encode($slots_disponibles);

} catch (Throwable $e) {
    $codigo_error = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($codigo_error);
    echo json_encode([
        'error' => '[Centinela v5] Fallo en API de horarios: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
    exit;
}
?>