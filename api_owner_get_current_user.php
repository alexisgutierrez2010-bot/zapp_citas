<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.

session_start();
// SOLUCIÓN: Incluir config.php para tener acceso a la conexión $conn
// SOLUCIÓN: Incluir el guardián de sesión que carga la configuración y valida el timeout.
require_once 'api_owner_session_check.php';

// Guardián de seguridad: si no hay sesión, no hay nada que devolver.
if (!isset($_SESSION['owner_loggedin']) || $_SESSION['owner_loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'No hay una sesión activa.']);
    exit;
}

// SOLUCIÓN: Obtener la fecha de desactivación directamente desde la base de datos
// para asegurar que siempre esté actualizada, en lugar de depender de la sesión del login.
$id_negocio_session = $_SESSION['owner_id_negocio'] ?? 0;
$fecha_desactivacion = null;
if ($id_negocio_session > 0) {
    $stmt = $conn->prepare("SELECT fecha_desactivacion FROM j102_negocios WHERE id_negocio = ?");
    $stmt->bind_param("i", $id_negocio_session);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $fecha_desactivacion = $result['fecha_desactivacion'] ?? null;
}

// Construir y devolver los datos del usuario desde la sesión.
$response_data = [
    'id_usuario' => $_SESSION['owner_id_usuario'] ?? null,
    'nombre_usuario' => $_SESSION['owner_nombre_usuario'] ?? null,
    'id_negocio' => $_SESSION['owner_id_negocio'] ?? null,
    'nombre_negocio' => $_SESSION['owner_nombre_negocio'] ?? null,
    'fecha_desactivacion' => $fecha_desactivacion
];

echo json_encode($response_data);
?>