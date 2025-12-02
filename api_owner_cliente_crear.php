<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-28-2025).
session_start(); // RESTAURADO: El script principal es responsable de iniciar la sesión.
require_once 'api_owner_session_check.php'; // 1. Guardián de sesión y timeout
header('Content-Type: application/json'); // 2. Establecer cabecera
require_once 'config.php';
require_once 'audit_log.php'; // Reactivado

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

if (empty($nombre_completo) || empty($correo_electronico) || empty($numero_celular)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nombre, celular y correo son obligatorios.']);
    exit;
}

// Validar email
if (!filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'El formato del correo electrónico no es válido.']);
    exit;
}

// Verificar duplicados de email o celular para este negocio
$sql_check_duplicate = "SELECT id_cliente FROM j106_clientes WHERE (correo_electronico = ? OR numero_celular = ?) AND id_negocio = ?";
$stmt_check_duplicate = $conn->prepare($sql_check_duplicate);
$stmt_check_duplicate->bind_param("ssi", $correo_electronico, $numero_celular, $id_negocio_session);
$stmt_check_duplicate->execute();
if ($stmt_check_duplicate->get_result()->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(['error' => 'Ya existe un cliente con este correo o número de celular en su negocio.']);
    exit;
}

$sql = "INSERT INTO j106_clientes (nombre_completo, numero_celular, correo_electronico, direccion1, direccion2, ciudad, id_pais, id_estado, zip_code, notas_adicionales, id_negocio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssiissi", $nombre_completo, $numero_celular, $correo_electronico, $direccion1, $direccion2, $ciudad, $id_pais, $id_estado, $zip_code, $notas_adicionales, $id_negocio_session);

if ($stmt->execute()) {
    $id_nuevo_cliente = $stmt->insert_id;
    registrar_auditoria($conn, $_SESSION['owner_id_usuario'], $id_negocio_session, 'OWNER_SPA_CLIENT_CREATE', "Propietario creó al cliente '{$nombre_completo}' (ID: {$id_nuevo_cliente}) desde la SPA.");
    echo json_encode(['success' => true, 'message' => 'Cliente creado con éxito.', 'id_cliente' => $id_nuevo_cliente]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al crear el cliente: ' . $stmt->error]);
}
?>