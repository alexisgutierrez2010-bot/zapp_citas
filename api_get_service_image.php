<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
require_once 'config.php';

$id_servicio = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_servicio <= 0) {
    http_response_code(400);
    exit;
}

$stmt = $conn->prepare("SELECT foto_servicio FROM j104_servicios WHERE id_servicio = ?");
$stmt->bind_param("i", $id_servicio);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (!empty($row['foto_servicio'])) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($row['foto_servicio']);
        header("Content-Type: " . $mimeType);
        echo $row['foto_servicio'];
        exit();
    }
}

// Si no hay imagen, servir una por defecto
$default_image_path = __DIR__ . '/assets/images/no-image.png';
header("Content-Type: image/png");
readfile($default_image_path);
exit();