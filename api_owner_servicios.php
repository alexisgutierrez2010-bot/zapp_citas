<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-20-2025).
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Seguridad: Verificar que el propietario ha iniciado sesión
if (!isset($_SESSION['owner_loggedin']) || $_SESSION['owner_loggedin'] !== true) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

$id_negocio_session = $_SESSION['owner_id_negocio'];

$servicios = [];

$sql = "SELECT id_servicio, nombre_servicio, duracion_valor, duracion_unidad, precio FROM j104_servicios WHERE activo = TRUE AND id_negocio = ? ORDER BY nombre_servicio ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_negocio_session);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $servicios[] = $row;
}

echo json_encode($servicios);
?>