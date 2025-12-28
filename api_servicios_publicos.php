<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
require_once 'config.php';

header('Content-Type: application/json');

$id_negocio = isset($_GET['id_negocio']) ? (int)$_GET['id_negocio'] : 0;

if ($id_negocio <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de negocio no proporcionado.']);
    exit;
}

$servicios = [];

$sql = "SELECT id_servicio, nombre_servicio, duracion_valor, duracion_unidad, precio, foto_servicio FROM j104_servicios WHERE activo = TRUE AND id_negocio = ? ORDER BY nombre_servicio ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_negocio);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Convertir BLOB a Base64 para JSON
    if (!empty($row['foto_servicio'])) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($row['foto_servicio']);
        $row['foto_servicio'] = 'data:' . $mimeType . ';base64,' . base64_encode($row['foto_servicio']);
    } else {
        $row['foto_servicio'] = null;
    }
    $servicios[] = $row;
}

echo json_encode($servicios);