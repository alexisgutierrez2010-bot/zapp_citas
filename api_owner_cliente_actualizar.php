<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).

require_once 'config.php';
require_once 'audit_log.php';

// Verificar sesión del propietario
session_start();
if (!isset($_SESSION['owner_loggedin']) || $_SESSION['owner_loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

header('Content-Type: application/json');

// Capturar errores fatales de PHP para que no rompan el JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode(['error' => "Error interno del servidor: $errstr"]);
    exit;
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$id_cliente = isset($input['id_cliente']) ? (int)$input['id_cliente'] : 0;
$nombre_completo = trim($input['nombre_completo'] ?? '');
$numero_celular = trim($input['numero_celular'] ?? '');
$correo_electronico = trim($input['correo_electronico'] ?? '');
$direccion1 = trim($input['direccion1'] ?? '');
$direccion2 = trim($input['direccion2'] ?? ''); // Faltaba
$ciudad = trim($input['ciudad'] ?? '');
$zip_code = trim($input['zip_code'] ?? '');
$id_pais = (int)($input['id_pais'] ?? 0);
$id_estado = (int)($input['id_estado'] ?? 0);
$notas_adicionales = trim($input['notas_adicionales'] ?? ''); // Faltaba
$in_email = !empty($input['in_email']) ? 1 : 0;
$in_sms = !empty($input['in_sms']) ? 1 : 0;
$in_whatsapp = !empty($input['in_whatsapp']) ? 1 : 0; // Faltaba
$activo = isset($input['activo']) ? (int)$input['activo'] : null;

if ($id_cliente <= 0 || empty($nombre_completo) || empty($numero_celular)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos. Nombre y celular son obligatorios.']);
    exit;
}

$id_negocio = $_SESSION['owner_id_negocio'];

// Verificar duplicados (celular o email) excluyendo el cliente actual
$sql_check = "SELECT id_cliente FROM j106_clientes WHERE (numero_celular = ? OR (correo_electronico != '' AND correo_electronico = ?)) AND id_negocio = ? AND id_cliente != ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ssii", $numero_celular, $correo_electronico, $id_negocio, $id_cliente);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'El teléfono o correo ya está registrado para otro cliente en este negocio.']);
    exit;
}
$stmt_check->close();

$sql_parts = [];
$params = [];
$types = '';

if ($nombre_completo) { $sql_parts[] = "nombre_completo = ?"; $params[] = $nombre_completo; $types .= 's'; }
if ($numero_celular) { $sql_parts[] = "numero_celular = ?"; $params[] = $numero_celular; $types .= 's'; }
if (isset($input['correo_electronico'])) { $sql_parts[] = "correo_electronico = ?"; $params[] = $correo_electronico; $types .= 's'; }
if (isset($input['direccion1'])) { $sql_parts[] = "direccion1 = ?"; $params[] = $direccion1; $types .= 's'; }
if (isset($input['direccion2'])) { $sql_parts[] = "direccion2 = ?"; $params[] = $direccion2; $types .= 's'; }
if (isset($input['ciudad'])) { $sql_parts[] = "ciudad = ?"; $params[] = $ciudad; $types .= 's'; }
if (isset($input['zip_code'])) { $sql_parts[] = "zip_code = ?"; $params[] = $zip_code; $types .= 's'; }
if ($id_pais > 0) { $sql_parts[] = "id_pais = ?"; $params[] = $id_pais; $types .= 'i'; }
if ($id_estado > 0) { $sql_parts[] = "id_estado = ?"; $params[] = $id_estado; $types .= 'i'; }
if (isset($input['notas_adicionales'])) { $sql_parts[] = "notas_adicionales = ?"; $params[] = $notas_adicionales; $types .= 's'; }
if (isset($input['in_email'])) { $sql_parts[] = "in_email = ?"; $params[] = $in_email; $types .= 'i'; }
if (isset($input['in_sms'])) { $sql_parts[] = "in_sms = ?"; $params[] = $in_sms; $types .= 'i'; }
if (isset($input['in_whatsapp'])) { $sql_parts[] = "in_whatsapp = ?"; $params[] = $in_whatsapp; $types .= 'i'; }
if ($activo !== null) { $sql_parts[] = "activo = ?"; $params[] = $activo; $types .= 'i'; }

if (empty($sql_parts)) {
    echo json_encode(['success' => true, 'message' => 'No se proporcionaron datos para actualizar.']);
    exit;
}

$sql = "UPDATE j106_clientes SET " . implode(', ', $sql_parts) . " WHERE id_cliente = ? AND id_negocio = ?";
$params[] = $id_cliente; $types .= 'i';
$params[] = $id_negocio; $types .= 'i';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    registrar_auditoria($conn, $_SESSION['owner_id_usuario'], $id_negocio, 'CLIENT_UPDATE', "Cliente ID $id_cliente actualizado.");
    echo json_encode(['success' => true, 'message' => 'Cliente actualizado correctamente.']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al actualizar el cliente: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>