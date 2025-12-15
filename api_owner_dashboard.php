<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-14-2025).

session_start();
require_once 'api_owner_session_check.php';
header('Content-Type: application/json');

$id_negocio_session = $_SESSION['owner_id_negocio'];
$response = [
    'clientesPorMes' => [],
    'citasPorMes' => [],
    'ingresosPorMes' => []
];

try {
    // 1. Clientes registrados por mes (últimos 12 meses)
    $sql_clientes = "SELECT 
                        DATE_FORMAT(fecha_registro, '%Y-%m') AS mes,
                        COUNT(id_cliente) AS total
                     FROM j106_clientes
                     WHERE id_negocio = ? AND fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                     GROUP BY mes
                     ORDER BY mes ASC";
    $stmt_clientes = $conn->prepare($sql_clientes);
    $stmt_clientes->bind_param("i", $id_negocio_session);
    $stmt_clientes->execute();
    $result_clientes = $stmt_clientes->get_result();
    while ($row = $result_clientes->fetch_assoc()) {
        $response['clientesPorMes'][] = $row;
    }
    $stmt_clientes->close();

    // 2. Citas completadas por mes (últimos 12 meses)
    $sql_citas = "SELECT 
                    DATE_FORMAT(fecha_hora_inicio, '%Y-%m') AS mes,
                    COUNT(id_cita) AS total
                  FROM j108_citas
                  WHERE id_negocio = ? AND estado_cita = 'Completada' AND fecha_hora_inicio >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                  GROUP BY mes
                  ORDER BY mes ASC";
    $stmt_citas = $conn->prepare($sql_citas);
    $stmt_citas->bind_param("i", $id_negocio_session);
    $stmt_citas->execute();
    $result_citas = $stmt_citas->get_result();
    while ($row = $result_citas->fetch_assoc()) {
        $response['citasPorMes'][] = $row;
    }
    $stmt_citas->close();

    // 3. Ingresos por servicios por mes (citas completadas, últimos 12 meses)
    $sql_ingresos = "SELECT 
                        DATE_FORMAT(c.fecha_hora_inicio, '%Y-%m') AS mes,
                        SUM(s.precio) AS total
                     FROM j108_citas c
                     JOIN j104_servicios s ON c.id_servicio = s.id_servicio
                     WHERE c.id_negocio = ? AND c.estado_cita = 'Completada' AND c.fecha_hora_inicio >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                     GROUP BY mes
                     ORDER BY mes ASC";
    $stmt_ingresos = $conn->prepare($sql_ingresos);
    $stmt_ingresos->bind_param("i", $id_negocio_session);
    $stmt_ingresos->execute();
    $result_ingresos = $stmt_ingresos->get_result();
    while ($row = $result_ingresos->fetch_assoc()) {
        $response['ingresosPorMes'][] = $row;
    }
    $stmt_ingresos->close();

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener los datos del dashboard: ' . $e->getMessage()]);
}
?>