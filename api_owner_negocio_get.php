<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-27-2025). CORRECCIÓN: Se estandariza el guardián de sesión.
session_start();
require_once 'api_owner_session_check.php'; // Guardián de sesión que valida y carga config.
header('Content-Type: application/json');

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