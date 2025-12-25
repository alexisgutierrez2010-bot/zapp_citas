<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.

require_once 'config.php';

header('Content-Type: application/json');

$categorias = [];

// Consulta para obtener todas las categorías activas, ordenadas alfabéticamente.
$sql = "SELECT id_categoria, nombre_categoria FROM j103_categorias WHERE activo = 1 ORDER BY nombre_categoria ASC";

if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
    $result->free();
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al consultar las categorías: ' . $conn->error]);
    exit;
}

$conn->close();

echo json_encode($categorias);
?>