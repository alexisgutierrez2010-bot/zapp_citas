<?php
// Revisado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM en fecha Dec/01/2025 //
// Update :Dec-01-2025).
header('Content-Type: application/json');
// session_start(); // ELIMINADO: El guardián ya inicia la sesión.
require_once 'config.php';

$id_cliente = isset($_GET['id_cliente']) ? (int)$_GET['id_cliente'] : 0;

if ($id_cliente <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de cliente no válido.']);
    exit;
}

$historial = [];
$sql = "SELECT 
            c.id_cita,
            c.fecha_hora_inicio,
            c.estado_cita,
            s.nombre_servicio,
            c.descripcion_trabajo
        FROM j108_citas c
        JOIN j104_servicios s ON c.id_servicio = s.id_servicio
        WHERE c.id_cliente = ?
        ORDER BY c.fecha_hora_inicio DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $historial[] = $row;
}

echo json_encode($historial);
?>