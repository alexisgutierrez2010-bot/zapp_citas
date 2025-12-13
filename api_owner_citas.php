<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-28-2025).
session_start(); // RESTAURADO: El script principal es responsable de iniciar la sesión.
require_once 'api_owner_session_check.php'; // 1. Guardián de sesión y timeout
header('Content-Type: application/json'); // 2. Establecer cabecera
require_once 'config.php';

// 3. Verificación de la conexión a la base de datos
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Error de conexión a la base de datos: ' . $conn->connect_error]);
    exit;
}

$id_negocio_session = $_SESSION['owner_id_negocio'];

// Determinar la fecha para filtrar las citas. Por defecto, es el día de hoy.
$fecha_filtro = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

$citas = [];

$sql_citas = "SELECT 
                c.id_cita, 
                c.fecha_hora_inicio, 
                c.fecha_hora_fin, 
                c.estado_cita, 
                c.in_email, c.in_sms,
                cl.nombre_completo AS nombre_cliente,
                -- CORRECCIÓN: Usar LEFT JOIN y la función IF para incluir Reuniones.
                IF(c.tipo_cita = 'Reunion', c.descripcion_trabajo, s.nombre_servicio) AS nombre_servicio
              FROM j108_citas c
              JOIN j106_clientes cl ON c.id_cliente = cl.id_cliente
              LEFT JOIN j104_servicios s ON c.id_servicio = s.id_servicio
              WHERE c.id_negocio = ? AND DATE(c.fecha_hora_inicio) = ?
              ORDER BY c.fecha_hora_inicio ASC";

$stmt = $conn->prepare($sql_citas);
$stmt->bind_param("is", $id_negocio_session, $fecha_filtro);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $citas[] = $row;
}

echo json_encode($citas);
?>