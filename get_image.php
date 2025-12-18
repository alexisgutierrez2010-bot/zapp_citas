<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
require_once 'auth_check.php'; // Necesitamos la sesión para saber qué imagen mostrar
require_once 'config.php';

// CORRECCIÓN: Determinar qué ID de negocio usar. Priorizar el parámetro GET.
// Esto permite que el script muestre la imagen de CUALQUIER negocio, no solo el de la sesión.
$id_negocio_a_mostrar = isset($_GET['id']) ? (int)$_GET['id'] : $id_negocio_session;

// Obtener la imagen de la base de datos
$stmt = $conn->prepare("SELECT background_image_data, background_image_type FROM j102_negocios WHERE id_negocio = ?");
$stmt->bind_param("i", $id_negocio_a_mostrar);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    if (!empty($row['background_image_data']) && !empty($row['background_image_type'])) {
        // Establecer la cabecera con el tipo de contenido correcto
        header("Content-Type: " . $row['background_image_type']);
        
        // Imprimir los datos binarios de la imagen
        echo $row['background_image_data'];
    } else {
        // Si no hay imagen en la BD, servir una por defecto
        $default_image_path = __DIR__ . '/assets/images/default_bg.jpg';
        header("Content-Type: image/jpeg");
        readfile($default_image_path);
    }
} else {
    // Si la consulta falla, servir la imagen por defecto
    $default_image_path = __DIR__ . '/assets/images/default_bg.jpg';
    header("Content-Type: image/jpeg");
    readfile($default_image_path);
}

exit();
?>