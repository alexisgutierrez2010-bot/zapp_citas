<?php
// Elaborado por GEMINI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-06-2025).

session_start();
require_once 'api_owner_session_check.php';
header('Content-Type: application/json');
require_once 'config.php';
require_once 'audit_log.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$id_negocio_session = $_SESSION['owner_id_negocio'];
$input = json_decode(file_get_contents('php://input'), true);

// Recoger y limpiar datos
$id_cliente = (int)($input['id_cliente'] ?? 0);
$nombre = trim($input['nombre_completo'] ?? '');
$celular = trim($input['numero_celular'] ?? '');
$email = trim($input['correo_electronico'] ?? '');
$direccion1 = trim($input['direccion1'] ?? '');
$direccion2 = trim($input['direccion2'] ?? '');
$ciudad = trim($input['ciudad'] ?? '');
$id_pais = (int)($input['id_pais'] ?? 0);
$id_estado = (int)($input['id_estado'] ?? 0);
$zip_code = trim($input['zip_code'] ?? '');
$notas = trim($input['notas_adicionales'] ?? '');
$in_sms = (int)($input['in_sms'] ?? 0);
$in_email = (int)($input['in_email'] ?? 0);
$in_whatsapp = (int)($input['in_whatsapp'] ?? 0);
$activo = (int)($input['activo'] ?? 0);

// SOLUCIÓN: Hacer la API más flexible para manejar actualizaciones parciales (como solo activar/desactivar).
if ($id_cliente <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de cliente no válido.']);
    exit;
}

// Si solo se está cambiando el estado 'activo'
if (isset($input['activo']) && count($input) === 2) {
    $sql = "UPDATE j106_clientes SET activo = ? WHERE id_cliente = ? AND id_negocio = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $activo, $id_cliente, $id_negocio_session);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Estado del cliente actualizado con éxito.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar el estado del cliente.']);
    }
    exit;
}

// Si es una actualización completa, se requiere todo
if (empty($nombre) || empty($email) || empty($celular)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nombre, email y celular son obligatorios para una actualización completa.']);
    exit;
}

// Verificar duplicados en otros clientes
$stmt_check = $conn->prepare("SELECT id_cliente FROM j106_clientes WHERE (correo_electronico = ? OR numero_celular = ?) AND id_negocio = ? AND id_cliente != ?");
$stmt_check->bind_param("ssii", $email, $celular, $id_negocio_session, $id_cliente);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(['error' => 'El correo o celular ya está en uso por otro cliente.']);
    exit;
}

$sql = "UPDATE j106_clientes SET nombre_completo=?, numero_celular=?, correo_electronico=?, direccion1=?, direccion2=?, ciudad=?, id_pais=?, id_estado=?, zip_code=?, notas_adicionales=?, in_sms=?, in_email=?, in_whatsapp=?, activo=? WHERE id_cliente=? AND id_negocio=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssiissiiiiii", $nombre, $celular, $email, $direccion1, $direccion2, $ciudad, $id_pais, $id_estado, $zip_code, $notas, $in_sms, $in_email, $in_whatsapp, $activo, $id_cliente, $id_negocio_session);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cliente actualizado con éxito.']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al actualizar el cliente: ' . $stmt->error]);
}
?>