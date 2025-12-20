<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.

// 1. Iniciar y verificar la sesión del propietario.
// Este script carga la configuración y termina la ejecución si no hay sesión.
require_once 'api_owner_session_check.php';

// 2. Establecer la cabecera de respuesta como JSON.
header('Content-Type: application/json');

// 3. Obtener los IDs de la sesión que ya fue validada.
$id_usuario = $_SESSION['owner_id_usuario'];
$id_negocio = $_SESSION['owner_id_negocio'];

// 4. Preparar la consulta para obtener los datos del usuario y su negocio.
// Se combinan datos de j100_usuarios y j102_negocios.
$sql = "SELECT 
            u.id_usuario, 
            u.nombre_usuario,
            n.id_negocio,
            n.nombre_negocio,
            n.fecha_desactivacion,
            (n.background_image_type IS NOT NULL AND n.background_image_type != '') AS has_background
        FROM j100_usuarios u
        JOIN j102_negocios n ON u.id_negocio = n.id_negocio
        WHERE u.id_usuario = ? AND n.id_negocio = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al preparar la consulta: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ii", $id_usuario, $id_negocio);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

if (!$userData) {
    http_response_code(404);
    echo json_encode(['error' => 'No se encontraron los datos del usuario o negocio para la sesión actual.']);
    exit;
}

$userData['has_background'] = (bool)$userData['has_background'];
echo json_encode($userData);
?>