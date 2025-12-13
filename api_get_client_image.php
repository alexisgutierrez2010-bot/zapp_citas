<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-08-2025).
session_start();
require_once 'config.php';

$id_cliente = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_cliente <= 0) {
    http_response_code(400);
    exit;
}

$stmt = $conn->prepare("SELECT foto_perfil_data, foto_perfil_tipo FROM j106_clientes WHERE id_cliente = ?");
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (!empty($row['foto_perfil_data']) && !empty($row['foto_perfil_tipo'])) {
        header("Content-Type: " . $row['foto_perfil_tipo']);
        echo $row['foto_perfil_data'];
        exit();
    }
}

// Si no hay imagen o el cliente no existe, servir una por defecto
$default_image_path = __DIR__ . '/assets/images/default_avatar.png';
header("Content-Type: image/png");
readfile($default_image_path);
exit();