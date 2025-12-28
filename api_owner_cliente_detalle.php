<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).

require_once 'config.php';

session_start();
if (!isset($_SESSION['owner_loggedin']) || $_SESSION['owner_loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

header('Content-Type: application/json');

$id_cliente = isset($_GET['id_cliente']) ? (int)$_GET['id_cliente'] : 0;
$id_negocio = $_SESSION['owner_id_negocio'];

if ($id_cliente <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de cliente no válido.']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM j106_clientes WHERE id_cliente = ? AND id_negocio = ?");
$stmt->bind_param("ii", $id_cliente, $id_negocio);
$stmt->execute();
$result = $stmt->get_result();
$cliente = $result->fetch_assoc();

if ($cliente) {
    echo json_encode($cliente);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Cliente no encontrado.']);
}

$stmt->close();
$conn->close();
?>