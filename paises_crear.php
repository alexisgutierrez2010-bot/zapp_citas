<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
require_once 'Auth_check.php';
require_once 'audit_log.php';
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_pais = trim($_POST['nombre_pais']);
    $codigo_pais = strtoupper(trim($_POST['codigo_pais']));
    $codigo_telefono = trim($_POST['codigo_telefono']);
    $timezone = trim($_POST['timezone']);

    if (empty($nombre_pais) || empty($codigo_pais) || empty($codigo_telefono) || empty($timezone)) {
        header("Location: paises_lista.php?status=error&message=" . urlencode("Todos los campos son obligatorios."));
        exit();
    }

    $sql = "INSERT INTO j110_paises (nombre_pais, codigo_pais, codigo_telefono, timezone) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nombre_pais, $codigo_pais, $codigo_telefono, $timezone);

    if ($stmt->execute()) {
        $id_nuevo = $stmt->insert_id;
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'CREATE_COUNTRY', "Se creó el país '{$nombre_pais}' (ID: {$id_nuevo}).");
        header("Location: paises_lista.php?status=success_create");
    } else {
        if ($conn->errno == 1062) { // Error de entrada duplicada
            header("Location: paises_lista.php?status=error&message=" . urlencode("Ya existe un país con ese nombre o código."));
        } else {
            header("Location: paises_lista.php?status=error&message=" . urlencode("Error al crear el país: " . $stmt->error));
        }
    }
    $stmt->close();
    exit();
}