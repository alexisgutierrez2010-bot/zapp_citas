<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-27-2025).

session_start();
require_once 'api_owner_session_check.php';
header('Content-Type: application/json');
require_once 'config.php';

$id_negocio_session = $_SESSION['owner_id_negocio'];

// FullCalendar envía las fechas 'start' y 'end' del rango visible
$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date = $_GET['end'] ?? date('Y-m-t');

$eventos = [];

$sql = "SELECT 
            c.id_cita,
            c.fecha_hora_inicio,
            c.fecha_hora_fin,
            c.estado_cita,
            cl.nombre_completo AS nombre_cliente,
            IF(c.tipo_cita = 'Reunion', c.descripcion_trabajo, s.nombre_servicio) AS titulo_evento
        FROM j108_citas c
        JOIN j106_clientes cl ON c.id_cliente = cl.id_cliente
        LEFT JOIN j104_servicios s ON c.id_servicio = s.id_servicio
        WHERE c.id_negocio = ? AND c.fecha_hora_inicio BETWEEN ? AND ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $id_negocio_session, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$status_colors = [
    'Pendiente' => 'bg-info', 'Completada' => 'bg-success',
    'Cancelada' => 'bg-danger', 'Pospuesta' => 'bg-warning',
    'No Asistió' => 'bg-secondary', 'Confirmada' => 'bg-primary',
];

while ($row = $result->fetch_assoc()) {
    $eventos[] = [
        'id' => $row['id_cita'],
        'title' => $row['titulo_evento'] . ' - ' . $row['nombre_cliente'],
        'start' => $row['fecha_hora_inicio'],
        'end' => $row['fecha_hora_fin'],
        'className' => $status_colors[$row['estado_cita']] ?? 'bg-light text-dark',
        'allDay' => false
    ];
}

$stmt->close();
echo json_encode($eventos);
?>