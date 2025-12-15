<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
session_start(); // El script principal es responsable de iniciar la sesión.
require_once 'api_owner_session_check.php'; // 1. Guardián de sesión y timeout
header('Content-Type: application/json'); // 2. Establecer cabecera

try {
    $id_negocio_session = $_SESSION['owner_id_negocio'];
    $fecha_filtro = $_GET['fecha'] ?? date('Y-m-d');

    // SOLUCIÓN FINAL: Se unifica la consulta para que sea idéntica a la del calendario, que funciona correctamente.
    // Un LEFT JOIN devuelve todas las filas de la tabla de la izquierda (citas),
    // incluso si no hay una coincidencia en la tabla de la derecha (servicios).
    $sql_citas = "SELECT 
                    c.id_cita, 
                    c.fecha_hora_inicio, 
                    c.fecha_hora_fin, 
                    c.estado_cita,
                    c.descripcion_trabajo,
                    c.tipo_cita,
                    c.in_email, c.in_sms,
                    cl.nombre_completo AS nombre_cliente,
                    s.nombre_servicio
                  FROM j108_citas c
                  JOIN j106_clientes cl ON c.id_cliente = cl.id_cliente
                  LEFT JOIN j104_servicios s ON c.id_servicio = s.id_servicio
                  WHERE c.id_negocio = ? AND DATE(c.fecha_hora_inicio) = ?
                  ORDER BY c.fecha_hora_inicio ASC";

    $stmt = $conn->prepare($sql_citas);
    $stmt->bind_param("is", $id_negocio_session, $fecha_filtro);
    $stmt->execute();
    $result = $stmt->get_result();
    $citas = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode($citas);

} catch (Exception $e) {
    $codigo_error = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($codigo_error);
    echo json_encode(['error' => $e->getMessage()]);
}
?>