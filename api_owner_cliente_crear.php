<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.

require_once 'config.php';
require_once 'audit_log.php';

session_start();
if (!isset($_SESSION['owner_loggedin']) || $_SESSION['owner_loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$id_negocio = $_SESSION['owner_id_negocio'];
$nombre_completo = trim($input['nombre_completo'] ?? '');
$numero_celular = trim($input['numero_celular'] ?? '');
$correo_electronico = trim($input['correo_electronico'] ?? '');
$direccion1 = trim($input['direccion1'] ?? '');
$direccion2 = trim($input['direccion2'] ?? '');
$ciudad = trim($input['ciudad'] ?? '');
$zip_code = trim($input['zip_code'] ?? '');
$id_pais = (int)($input['id_pais'] ?? 0);
$id_estado = (int)($input['id_estado'] ?? 0);
$notas_adicionales = trim($input['notas_adicionales'] ?? '');
$in_email = !empty($input['in_email']) ? 1 : 0;
$in_sms = !empty($input['in_sms']) ? 1 : 0;
$in_whatsapp = !empty($input['in_whatsapp']) ? 1 : 0;

if (empty($nombre_completo) || empty($numero_celular)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nombre y celular son obligatorios.']);
    exit;
}

// Verificar duplicados
$sql_check = "SELECT id_cliente FROM j106_clientes WHERE (numero_celular = ? OR (correo_electronico != '' AND correo_electronico = ?)) AND id_negocio = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ssi", $numero_celular, $correo_electronico, $id_negocio);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'El teléfono o correo ya está registrado para otro cliente.']);
    exit;
}
$stmt_check->close();

$sql = "INSERT INTO j106_clientes (id_negocio, nombre_completo, numero_celular, correo_electronico, direccion1, direccion2, ciudad, zip_code, id_pais, id_estado, notas_adicionales, in_email, in_sms, in_whatsapp, activo, fecha_registro) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isssssssisiiis", 
    $id_negocio, $nombre_completo, $numero_celular, $correo_electronico, 
    $direccion1, $direccion2, $ciudad, $zip_code, $id_pais, $id_estado, 
    $notas_adicionales, $in_email, $in_sms, $in_whatsapp
);

if ($stmt->execute()) {
    $id_nuevo_cliente = $stmt->insert_id;
    registrar_auditoria($conn, $_SESSION['owner_id_usuario'], $id_negocio, 'CLIENT_CREATE', "Cliente ID $id_nuevo_cliente creado por el propietario.");
    echo json_encode(['success' => true, 'message' => 'Cliente creado correctamente.']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al crear el cliente: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>