<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-20-2025).
// api_negocio_publico.php
header('Content-Type: application/json');
require_once 'config.php'; // Correcto, config.php está en la misma carpeta raíz

$id_negocio = isset($_GET['id_negocio']) ? (int)$_GET['id_negocio'] : 0;

if ($id_negocio <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'ID de negocio no válido.']);
    exit;
}

$stmt = $conn->prepare("SELECT nombre_negocio, telefono, email, direccion1, ciudad FROM j102_negocios WHERE id_negocio = ?");
$stmt->bind_param("i", $id_negocio);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $negocio = $result->fetch_assoc();
    echo json_encode($negocio);
} else {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Negocio no encontrado.']);
}

$stmt->close();
$conn->close();
?>