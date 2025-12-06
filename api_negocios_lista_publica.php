<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
header('Content-Type: application/json');
require_once 'config.php';

$negocios = [];
$sql = "SELECT n.id_negocio, n.nombre_negocio, c.nombre_categoria 
        FROM j102_negocios n
        LEFT JOIN j103_categorias c ON n.id_categoria_negocio = c.id_categoria
        WHERE n.activo = 1
        ORDER BY c.nombre_categoria, n.nombre_negocio ASC";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $negocios[] = $row;
}

echo json_encode($negocios);
?>