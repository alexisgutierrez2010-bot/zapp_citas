<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-27-2025).
session_start();
// --- SOLUCIÓN ---
// Se elimina la llamada a 'api_owner_session_check.php' porque este script termina la ejecución (exit;)
// e impide que se devuelva la lista de detalles del negocio. La seguridad ya está cubierta por la
// comprobación de sesión inicial en app_owner.js.

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

// Seguridad: Verificar que el propietario ha iniciado sesión.
// Se añade un guardián local que no termina la ejecución con exit().
if (!isset($_SESSION['owner_loggedin']) || $_SESSION['owner_loggedin'] !== true) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Acceso no autorizado. La sesión ha expirado.']);
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