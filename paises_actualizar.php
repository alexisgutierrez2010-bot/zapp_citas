<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_pais = (int)$_POST['id_pais'];
    $nombre_pais = trim($_POST['nombre_pais']);
    $codigo_pais = strtoupper(trim($_POST['codigo_pais']));
    $codigo_telefono = trim($_POST['codigo_telefono']);
    $timezone = trim($_POST['timezone']);

    if ($id_pais <= 0) {
        header("Location: paises_lista.php?status=error&message=" . urlencode("ID inválido."));
        exit();
    }

    $sql = "UPDATE j110_paises SET nombre_pais = ?, codigo_pais = ?, codigo_telefono = ?, timezone = ? WHERE id_pais = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $nombre_pais, $codigo_pais, $codigo_telefono, $timezone, $id_pais);

    if ($stmt->execute()) {
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'UPDATE_COUNTRY', "Se actualizó el país '{$nombre_pais}' (ID: {$id_pais}).");
        header("Location: paises_lista.php?status=success_edit");
    } else {
        header("Location: paises_editar.php?id={$id_pais}&status=error&message=" . urlencode("Error: " . $stmt->error));
    }
    $stmt->close();
    exit();
}
?>