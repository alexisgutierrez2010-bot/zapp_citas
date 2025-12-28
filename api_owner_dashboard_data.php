<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).

session_start();
require_once 'api_owner_session_check.php';
header('Content-Type: application/json');

try {
    $id_negocio = $_SESSION['owner_id_negocio'];
    $data = [
        'clientesPorMes' => [],
        'citasPorMes' => [],
        'ingresosPorMes' => [],
        'reunionesPorMes' => []
    ];

    // 1. Clientes nuevos por mes (últimos 12 meses)
    $sql_clientes = "SELECT 
                        YEAR(fecha_registro) AS anio, 
                        MONTH(fecha_registro) AS mes, 
                        COUNT(id_cliente) AS total
                     FROM j106_clientes
                     WHERE id_negocio = ? AND fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                     GROUP BY anio, mes
                     ORDER BY anio, mes";
    $stmt = $conn->prepare($sql_clientes);
    $stmt->bind_param("i", $id_negocio);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data['clientesPorMes'][] = $row;
    }
    $stmt->close();

    // 2. Citas totales por mes (últimos 6 meses)
    $sql_citas = "SELECT 
                    YEAR(fecha_hora_inicio) AS anio, 
                    MONTH(fecha_hora_inicio) AS mes, 
                    COUNT(id_cita) AS total_registradas,
                    SUM(IF(estado_cita = 'Completada', 1, 0)) AS total_completadas,
                    SUM(IF(estado_cita = 'Cancelada', 1, 0)) AS total_canceladas
                  FROM j108_citas
                  WHERE id_negocio = ? AND fecha_hora_inicio >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                  GROUP BY anio, mes
                  ORDER BY anio, mes";
    $stmt = $conn->prepare($sql_citas);
    $stmt->bind_param("i", $id_negocio);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data['citasPorMes'][] = $row;
    }
    $stmt->close();

    // 3. Ingresos por servicios por mes (citas completadas, últimos 6 meses)
    $sql_ingresos = "SELECT 
                        YEAR(c.fecha_hora_inicio) AS anio, 
                        MONTH(c.fecha_hora_inicio) AS mes, 
                        SUM(s.precio) AS total
                     FROM j108_citas c
                     JOIN j104_servicios s ON c.id_servicio = s.id_servicio
                     WHERE c.id_negocio = ? 
                       AND c.estado_cita = 'Completada'
                       AND c.fecha_hora_inicio >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                     GROUP BY anio, mes
                     ORDER BY anio, mes";
    $stmt = $conn->prepare($sql_ingresos);
    $stmt->bind_param("i", $id_negocio);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data['ingresosPorMes'][] = $row;
    }
    $stmt->close();

    // 4. Reuniones por mes (últimos 6 meses)
    $sql_reuniones = "SELECT 
                        YEAR(fecha_hora_inicio) AS anio, 
                        MONTH(fecha_hora_inicio) AS mes, 
                        COUNT(id_cita) AS total
                      FROM j108_citas
                      WHERE id_negocio = ? 
                        AND tipo_cita = 'Reunion'
                        AND fecha_hora_inicio >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                      GROUP BY anio, mes
                      ORDER BY anio, mes";
    $stmt = $conn->prepare($sql_reuniones);
    $stmt->bind_param("i", $id_negocio);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data['reunionesPorMes'][] = $row;
    }
    $stmt->close();

    echo json_encode($data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener los datos del dashboard: ' . $e->getMessage()]);
}