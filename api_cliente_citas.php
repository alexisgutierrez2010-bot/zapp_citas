<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.

require_once 'config.php';

session_start();
if (!isset($_SESSION['client_loggedin']) || $_SESSION['client_loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

header('Content-Type: application/json');

$id_cliente = (int)($_GET['id_cliente'] ?? 0);

if ($id_cliente !== (int)$_SESSION['client_id']) {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permiso para ver estas citas.']);
    exit;
}

$sql = "SELECT 
            ci.id_cita, ci.fecha_hora_inicio, ci.estado_cita, ci.descripcion_trabajo,
            s.nombre_servicio, s.precio
        FROM j108_citas ci
        JOIN j104_servicios s ON ci.id_servicio = s.id_servicio
        WHERE ci.id_cliente = ? 
          AND ci.fecha_hora_inicio >= NOW()
          AND ci.estado_cita NOT IN ('Cancelada', 'Completada', 'No Asistió')
        ORDER BY ci.fecha_hora_inicio ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$result = $stmt->get_result();
$citas_proximas = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(['citas_proximas' => $citas_proximas]);
?>