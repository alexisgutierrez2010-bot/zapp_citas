<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-27-2025).

// 1. Guardián de sesión: Se ejecuta ANTES de cualquier salida.
session_start(); // RESTAURADO: El script principal es responsable de iniciar la sesión.
require_once 'api_owner_session_check.php';

// 2. Cabecera JSON: Se establece DESPUÉS de la validación de sesión.
header('Content-Type: application/json');

// 3. Configuración y lógica de la aplicación.
require_once 'config.php';
require_once 'audit_log.php'; // Reactivado

// 4. Verificación robusta de la conexión a la BD.
if ($conn->connect_error) {
    http_response_code(500);
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

// Validar y limpiar datos
$nombre_negocio = trim($input['nombre_negocio'] ?? '');
$email = trim($input['email'] ?? '');
$dias_trabajo = implode(',', $input['dias_trabajo'] ?? []);
$hora_inicio = $input['hora_inicio'] ?? '';
$hora_cierre = $input['hora_cierre'] ?? '';
$intervalo_minutos = (int)($input['intervalo_minutos'] ?? 30);
$direccion1 = trim($input['direccion1'] ?? '');
$direccion2 = trim($input['direccion2'] ?? '');
$ciudad = trim($input['ciudad'] ?? '');
$id_pais = (int)($input['id_pais'] ?? 0);
$id_estado = (int)($input['id_estado'] ?? 0);
$zip_code = trim($input['zip_code'] ?? '');

if (empty($nombre_negocio) || empty($email) || empty($hora_inicio) || empty($hora_cierre)) {
    http_response_code(400);
    echo json_encode(['error' => 'Todos los campos son obligatorios.']);
    exit;
}

$sql = "UPDATE j102_negocios SET 
            nombre_negocio = ?, email = ?, dias_trabajo = ?, 
            hora_inicio = ?, hora_cierre = ?, intervalo_minutos = ?,
            direccion1 = ?, direccion2 = ?, ciudad = ?, 
            id_pais = ?, id_estado = ?, zip_code = ?
        WHERE id_negocio = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssisssiisi", $nombre_negocio, $email, $dias_trabajo, $hora_inicio, $hora_cierre, $intervalo_minutos,
                                 $direccion1, $direccion2, $ciudad, $id_pais, $id_estado, $zip_code, $id_negocio_session);

if ($stmt->execute()) {
    registrar_auditoria($conn, $_SESSION['owner_id_usuario'], $id_negocio_session, 'OWNER_SPA_BUSINESS_UPDATE', "Propietario actualizó la configuración de su negocio desde la SPA."); // Reactivado
    echo json_encode(['message' => 'Configuración del negocio guardada con éxito.']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo guardar la configuración del negocio.']);
}
?>