<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
session_start(); // RESTAURADO: El script principal es responsable de iniciar la sesión.
require_once 'api_owner_session_check.php'; // 1. Guardián de sesión
header('Content-Type: application/json');      // 2. Cabecera JSON
require_once 'config.php';
require_once 'audit_log.php'; // Reactivado

// 3. Verificación de método y conexión
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos: ' . $conn->connect_error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido. Se esperaba POST.']);
    exit;
}

$id_negocio_session = $_SESSION['owner_id_negocio'];

// --- PROCESO DE ACTUALIZACIÓN DE ESTADO ---
$sql_update_vencidas = "UPDATE j108_citas SET estado_cita = 'Vencida' WHERE fecha_hora_inicio < NOW() AND estado_cita = 'Pendiente' AND id_negocio = ?";
$stmt_update = $conn->prepare($sql_update_vencidas);
$stmt_update->bind_param("i", $id_negocio_session);

if ($stmt_update->execute()) {
    $citas_actualizadas = $stmt_update->affected_rows;
    if ($citas_actualizadas > 0) {
        registrar_auditoria($conn, $_SESSION['owner_id_usuario'], $id_negocio_session, 'OWNER_SPA_CLOSE_APPOINTMENTS', "Propietario ejecutó cierre manual desde SPA, actualizando {$citas_actualizadas} citas."); // Reactivado
    }
    echo json_encode(['success' => true, 'citas_actualizadas' => $citas_actualizadas]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Ocurrió un error durante el proceso de cierre: ' . $stmt_update->error]);
}
$stmt_update->close();