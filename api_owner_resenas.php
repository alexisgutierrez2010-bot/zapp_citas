<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).

session_start();
require_once 'api_owner_session_check.php';
require_once 'config.php';
header('Content-Type: application/json');

$id_negocio = $_SESSION['owner_id_negocio'];

// Obtener reseñas con detalles del cliente y servicio
$sql = "SELECT r.id_resena, r.puntuacion, r.comentario, r.fecha_hora, 
               c.nombre_completo AS nombre_cliente, 
               s.nombre_servicio
        FROM j112_resenas r
        JOIN j106_clientes c ON r.id_cliente = c.id_cliente
        LEFT JOIN j104_servicios s ON r.id_servicio = s.id_servicio
        WHERE r.id_negocio = ? AND r.activo = 1
        ORDER BY r.fecha_hora DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_negocio);
$stmt->execute();
$result = $stmt->get_result();

$resenas = [];
$total_puntos = 0;
$count = 0;

while ($row = $result->fetch_assoc()) {
    $resenas[] = $row;
    $total_puntos += $row['puntuacion'];
    $count++;
}

$promedio = $count > 0 ? round($total_puntos / $count, 1) : 0;

echo json_encode([
    'resenas' => $resenas,
    'promedio' => $promedio,
    'total' => $count
]);
?>