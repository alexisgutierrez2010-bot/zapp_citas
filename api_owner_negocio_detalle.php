<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
session_start();
require_once 'api_owner_session_check.php'; // 1. Guardián de sesión y timeout
header('Content-Type: application/json'); // 2. Establecer cabecera
require_once 'config.php';

// Verificación de la conexión a la base de datos
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Error de conexión a la base de datos: ' . $conn->connect_error]);
    exit;
}

$id_negocio_session = $_SESSION['owner_id_negocio'];

$sql = "SELECT * FROM j102_negocios WHERE id_negocio = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_negocio_session);
$stmt->execute();
$result = $stmt->get_result();
$negocio = $result->fetch_assoc();

if (!$negocio) {
    http_response_code(404);
    echo json_encode(['error' => 'Negocio no encontrado.']);
    exit;
}

echo json_encode($negocio);
?>