<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
// session_start(); // ELIMINADO: El guardián ya inicia la sesión.
require_once 'api_owner_session_check.php'; // 1. Guardián de sesión y timeout
header('Content-Type: application/json'); // 2. Establecer cabecera
require_once 'config.php';

// Verificación de la conexión a la base de datos
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Error de conexión a la base de datos: ' . $conn->connect_error]);
    exit;
}

$id_negocio_session = $_SESSION['owner_id_negocio'];

// Obtener la fecha de inicio de la semana del GET, si no, usar la de hoy.
$start_date_str = isset($_GET['start_date']) ? $_GET['start_date'] : 'now';

try {
    $startOfWeek = new DateTime($start_date_str);
    $startOfWeek->modify('monday this week'); // Asegurarse de que empezamos en lunes

    $endOfWeek = clone $startOfWeek;
    $endOfWeek->modify('sunday this week');
    $endOfWeek->setTime(23, 59, 59); // Incluir todo el domingo

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato de fecha no válido.']);
    exit;
}

$citas = [];

$sql_citas = "SELECT 
                c.id_cita, 
                c.fecha_hora_inicio, 
                c.estado_cita, 
                c.tipo_cita,
                c.descripcion_trabajo,
                cl.nombre_completo AS nombre_cliente, 
                s.nombre_servicio
              FROM j108_citas c
              JOIN j106_clientes cl ON c.id_cliente = cl.id_cliente
              LEFT JOIN j104_servicios s ON c.id_servicio = s.id_servicio
              WHERE c.id_negocio = ? AND c.fecha_hora_inicio BETWEEN ? AND ?
              ORDER BY c.fecha_hora_inicio ASC";

$stmt = $conn->prepare($sql_citas);
$stmt->bind_param("iss", $id_negocio_session, $startOfWeek->format('Y-m-d H:i:s'), $endOfWeek->format('Y-m-d H:i:s'));
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Si es una reunión, el nombre del servicio será el tema de la reunión.
    if ($row['tipo_cita'] === 'Reunion') {
        // Usamos la descripción como el "nombre del servicio" para mostrar en la lista.
        $row['nombre_servicio'] = $row['descripcion_trabajo'] ?: 'Reunión';
    }
    $citas[] = $row;
}

echo json_encode($citas);
?>