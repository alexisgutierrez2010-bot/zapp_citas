<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).

session_start();
require_once 'api_owner_session_check.php'; // Guardián de sesión que valida y carga config.php

header('Content-Type: application/json');

// Si llegamos aquí, la sesión es válida gracias al guardián.
// Recopilamos los datos que el frontend (app_owner.js) necesita para arrancar.

$id_negocio = $_SESSION['owner_id_negocio'];

// Necesitamos obtener la 'fecha_desactivacion' y si tiene imagen de fondo.
$stmt_negocio = $conn->prepare("SELECT fecha_desactivacion, background_image_type FROM j102_negocios WHERE id_negocio = ?");
$stmt_negocio->bind_param("i", $id_negocio);
$stmt_negocio->execute();
$negocio_data = $stmt_negocio->get_result()->fetch_assoc();
$stmt_negocio->close();

$response_data = [
    'id_usuario' => $_SESSION['owner_id_usuario'],
    'nombre_usuario' => $_SESSION['owner_nombre_usuario'],
    'id_negocio' => (int)$id_negocio,
    'nombre_negocio' => $_SESSION['owner_nombre_negocio'],
    'fecha_desactivacion' => $negocio_data ? $negocio_data['fecha_desactivacion'] : null,
    'has_background' => $negocio_data && !empty($negocio_data['background_image_type'])
];

echo json_encode($response_data);
?>