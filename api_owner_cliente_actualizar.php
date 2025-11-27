<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
session_start();
require_once 'api_owner_session_check.php'; // 1. Guardián de sesión y timeout
header('Content-Type: application/json'); // 2. Establecer cabecera
require_once 'config.php';
require_once 'audit_log.php';

// 3. Verificación de la conexión a la base de datos
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Error de conexión a la base de datos: ' . $conn->connect_error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$id_negocio_session = $_SESSION['owner_id_negocio'];
$input = json_decode(file_get_contents('php://input'), true);

$id_cliente = $input['id_cliente'] ?? 0;
$nombre_completo = trim($input['nombre_completo'] ?? '');
$numero_celular = trim($input['numero_celular'] ?? ''); // Ya viene combinado del frontend
$correo_electronico = trim($input['correo_electronico'] ?? '');
$direccion1 = trim($input['direccion1'] ?? '');
$direccion2 = trim($input['direccion2'] ?? '');
$ciudad = trim($input['ciudad'] ?? '');
$id_pais = (int)($input['id_pais'] ?? 0);
$id_estado = (int)($input['id_estado'] ?? 0);
$zip_code = trim($input['zip_code'] ?? '');
$notas_adicionales = trim($input['notas_adicionales'] ?? '');

if ($id_cliente <= 0 || empty($nombre_completo) || empty($correo_electronico) || empty($numero_celular)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos para actualizar el cliente.']);
    exit;
}

// Validar email
if (!filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'El formato del correo electrónico no es válido.']);
    exit;
}

// Verificar duplicados de email (excluyendo al cliente actual)
$sql_check_email = "SELECT id_cliente FROM j106_clientes WHERE correo_electronico = ? AND id_cliente != ? AND id_negocio = ?";
$stmt_check_email = $conn->prepare($sql_check_email);
$stmt_check_email->bind_param("sii", $correo_electronico, $id_cliente, $id_negocio_session);
$stmt_check_email->execute();
if ($stmt_check_email->get_result()->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(['error' => 'El correo electrónico ya está en uso por otro cliente de este negocio.']);
    exit;
}

$sql = "UPDATE j106_clientes SET nombre_completo = ?, numero_celular = ?, correo_electronico = ?, direccion1 = ?, direccion2 = ?, ciudad = ?, id_pais = ?, id_estado = ?, zip_code = ?, notas_adicionales = ? WHERE id_cliente = ? AND id_negocio = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssiissii", $nombre_completo, $numero_celular, $correo_electronico, $direccion1, $direccion2, $ciudad, $id_pais, $id_estado, $zip_code, $notas_adicionales, $id_cliente, $id_negocio_session);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    registrar_auditoria($conn, $_SESSION['owner_id_usuario'], $id_negocio_session, 'OWNER_SPA_CLIENT_UPDATE', "Propietario actualizó cliente ID {$id_cliente}.");
    echo json_encode(['success' => true, 'message' => 'Cliente actualizado con éxito.']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo actualizar el cliente o no se realizaron cambios.']);
}
?>