<?php
// Elaborado por GEMINI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-06-2025).
session_start(); // RESTAURADO: El script principal es responsable de iniciar la sesión.
require_once 'api_owner_session_check.php'; // 1. Guardián de sesión y timeout
header('Content-Type: application/json'); // 2. Establecer cabecera
require_once 'config.php';

// 3. Verificación de la conexión a la base de datos
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Error de conexión a la base de datos: ' . $conn->connect_error]);
    exit;
}

$id_negocio_session = $_SESSION['owner_id_negocio'];
$id_cliente = isset($_GET['id_cliente']) ? (int)$_GET['id_cliente'] : 0;

if ($id_cliente <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de cliente no válido.']);
    exit;
}

$sql = "SELECT 
            id_cliente, nombre_completo, numero_celular, correo_electronico,
            direccion1, direccion2, ciudad, zip_code, notas_adicionales,
            id_pais, id_estado, activo,
            in_sms, in_email, in_whatsapp
        FROM j106_clientes
        WHERE id_cliente = ? AND id_negocio = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_cliente, $id_negocio_session);
$stmt->execute();
$result = $stmt->get_result();
$cliente = $result->fetch_assoc();

if (!$cliente) {
    http_response_code(404);
    echo json_encode(['error' => 'Cliente no encontrado o no pertenece a su negocio.']);
    exit;
}

echo json_encode($cliente);
?>