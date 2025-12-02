<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-28-2025).
session_start(); // RESTAURADO: El script principal es responsable de iniciar la sesión.
require_once 'api_owner_session_check.php'; // 1. Guardián de sesión y timeout
header('Content-Type: application/json');
require_once 'config.php';
require_once 'audit_log.php'; // Reactivado

// Cargar dependencias de PHPMailer y la función de envío centralizada.
require_once __DIR__ . '/vendor/autoload.php';
require_once 'api_owner_email_sender.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$id_negocio_session = $_SESSION['owner_id_negocio'];
$input = json_decode(file_get_contents('php://input'), true);

$id_cita = $input['id_cita'] ?? 0;
$estado_cita = $input['estado_cita'] ?? '';

if ($id_cita <= 0 || empty($estado_cita)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos para actualizar la cita.']);
    exit;
}
// --- SOLUCIÓN DEFINITIVA: Manejo flexible de `descripcion_trabajo` ---
// El problema es que a veces la API se llama solo con el estado (cambio rápido) y a veces con estado y descripción.
// Si la descripción no se envía, PHP genera un error "Undefined array key".

// 1. Primero, obtenemos la descripción actual de la base de datos.
$stmt_get_desc = $conn->prepare("SELECT descripcion_trabajo FROM j108_citas WHERE id_cita = ? AND id_negocio = ?");
$stmt_get_desc->bind_param("ii", $id_cita, $id_negocio_session);
$stmt_get_desc->execute();
$result_desc = $stmt_get_desc->get_result();
$cita_actual = $result_desc->fetch_assoc();
$stmt_get_desc->close();

if (!$cita_actual) {
    http_response_code(404);
    echo json_encode(['error' => 'La cita no existe o no pertenece a este negocio.']);
    exit;
}

// 2. Usamos la descripción enviada si existe; si no, mantenemos la actual.
$descripcion_trabajo = $input['descripcion_trabajo'] ?? $cita_actual['descripcion_trabajo'];
$sql = "UPDATE j108_citas SET estado_cita = ?, descripcion_trabajo = ? WHERE id_cita = ? AND id_negocio = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssii", $estado_cita, $descripcion_trabajo, $id_cita, $id_negocio_session);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    registrar_auditoria($conn, $_SESSION['owner_id_usuario'], $id_negocio_session, 'OWNER_SPA_CITA_UPDATE', "Propietario actualizó la cita ID {$id_cita} al estado '{$estado_cita}'.");

    echo json_encode(['success' => true, 'message' => 'Cita actualizada con éxito.']);
} else {
    // Si affected_rows es 0, puede ser porque no hubo cambios.
    if ($stmt->affected_rows === 0) {
        echo json_encode(['success' => true, 'message' => 'No se realizaron cambios en la cita.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo actualizar la cita. Error: ' . $stmt->error]);
    }
}
?>