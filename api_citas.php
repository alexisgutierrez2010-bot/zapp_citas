<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
// 1. Incluir configuración y establecer la cabecera para que la respuesta sea JSON
require_once 'auth_check.php';
require_once 'config.php';
header('Content-Type: application/json');

$es_administrador = (isset($rol_session) && strcasecmp(trim($rol_session), 'Administrador') == 0);
$id_negocio_get = isset($_GET['id_negocio']) ? (int)$_GET['id_negocio'] : 0;

$sql = "SELECT
            c.id_cita,
            c.fecha_hora_inicio,
            c.fecha_hora_fin,
            cl.nombre_completo,
            c.estado_cita,
            n.nombre_negocio
        FROM j108_citas c
        JOIN j106_clientes cl ON c.id_cliente = cl.id_cliente
        JOIN j102_negocios n ON c.id_negocio = n.id_negocio
        WHERE c.estado_cita != 'Cancelada'";

$params = [];
$types = "";

if ($es_administrador) {
    if ($id_negocio_get > 0) {
        $sql .= " AND c.id_negocio = ?";
        $params[] = $id_negocio_get;
        $types .= "i";
    }
    // Si es admin y $id_negocio_get es 0, no se añade filtro de negocio (muestra todos).
} else {
    // Si no es admin, se fuerza a ver solo su negocio.
    $sql .= " AND c.id_negocio = ?";
    $params[] = $id_negocio_session;
    $types .= "i";
}

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$eventos = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $title = $es_administrador && $id_negocio_get == 0 ? $row['nombre_completo'] . ' (' . $row['nombre_negocio'] . ')' : $row['nombre_completo'];
        $eventos[] = [
            'id'    => $row['id_cita'],
            'title' => $title,
            'start' => $row['fecha_hora_inicio'], // Fecha y hora de inicio
            'end'   => $row['fecha_hora_fin'],   // Fecha y hora de fin
        ];
    }
}

echo json_encode($eventos);
exit();
?>