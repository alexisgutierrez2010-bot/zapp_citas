<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-08-2025). SOLUCIÓN FINAL: Estandarización de la carga de dependencias y seguridad.
session_start();
require_once 'config.php'; // Cargar la conexión a la BD.
header('Content-Type: application/json');

// --- Guardián de Sesión Estándar para APIs ---
if (!isset($_SESSION['owner_loggedin']) || $_SESSION['owner_loggedin'] !== true) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Acceso no autorizado. La sesión ha expirado.']);
    exit;
}

if (!isset($_SESSION['owner_id_negocio']) || empty($_SESSION['owner_id_negocio'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'No se pudo identificar el negocio. Por favor, inicie sesión de nuevo.']);
    exit;
}
$id_negocio_session = (int)$_SESSION['owner_id_negocio'];
$servicios = [];

$sql = "SELECT id_servicio, nombre_servicio, duracion_valor, duracion_unidad, precio, activo, foto_servicio FROM j104_servicios WHERE id_negocio = ? ORDER BY nombre_servicio ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_negocio_session);
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

echo json_encode(['servicios' => $servicios]);