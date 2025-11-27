<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
// Fecha de Creación: 27/11/2025

// 1. Guardián de sesión: Se ejecuta ANTES de cualquier salida.
session_start();
require_once 'api_owner_session_check.php';

// 2. Cabecera JSON: Se establece DESPUÉS de la validación de sesión.
header('Content-Type: application/json');

// 3. Configuración y lógica de la aplicación.
require_once 'config.php';

// 4. Verificación robusta de la conexión a la BD.
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos: ' . $conn->connect_error]);
    exit;
}

$id_negocio_session = $_SESSION['owner_id_negocio'];

$sql = "SELECT nombre_negocio, telefono, email, dias_trabajo, hora_inicio, hora_cierre, intervalo_minutos,
               direccion1, direccion2, ciudad, id_pais, id_estado, zip_code
        FROM j102_negocios 
        WHERE id_negocio = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_negocio_session);
$stmt->execute();
$result = $stmt->get_result();
$negocio = $result->fetch_assoc();

echo json_encode($negocio);
?>