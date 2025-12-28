<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
require_once 'config.php';

$id_negocio = isset($_GET['id_negocio']) ? (int)$_GET['id_negocio'] : 0;

if ($id_negocio <= 0) {
    $id_negocio = 1; // Fallback al negocio principal
}

// Obtener la imagen de la base de datos
$stmt = $conn->prepare("SELECT background_image_data, background_image_type FROM j102_negocios WHERE id_negocio = ?");
$stmt->bind_param("i", $id_negocio);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (!empty($row['background_image_data']) && !empty($row['background_image_type'])) {
        header("Content-Type: " . $row['background_image_type']);
        echo $row['background_image_data'];
        exit();
    }
}

// Si no hay imagen en la BD o falla la consulta, servir una por defecto
$default_image_path = __DIR__ . '/assets/images/default_bg.jpg';
header("Content-Type: image/jpeg");
readfile($default_image_path);
exit();
?>