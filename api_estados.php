<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-20-2025).
require_once 'config.php';
header('Content-Type: application/json');

$id_pais = isset($_GET['id_pais']) ? (int)$_GET['id_pais'] : 0;
$estados = [];

if ($id_pais > 0) {
    $stmt = $conn->prepare("SELECT id_estado, nombre_estado FROM j111_estados WHERE id_pais = ? ORDER BY nombre_estado ASC");
    if ($stmt) {
        $stmt->bind_param("i", $id_pais);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $estados[] = $row;
        }
        $stmt->close();
    }
}

echo json_encode($estados);
exit();
?>