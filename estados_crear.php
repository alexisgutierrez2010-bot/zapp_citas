<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_pais = (int)$_POST['id_pais'];
    $nombre_estado = trim($_POST['nombre_estado']);

    if ($id_pais <= 0 || empty($nombre_estado)) {
        header("Location: paises_lista.php?status=error&message=" . urlencode("Datos inválidos."));
        exit();
    }

    $sql = "INSERT INTO j111_estados (nombre_estado, id_pais) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $nombre_estado, $id_pais);

    if ($stmt->execute()) {
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'CREATE_STATE', "Se creó el estado '{$nombre_estado}'.");
        header("Location: estados_lista.php?id_pais={$id_pais}&status=success_create");
    } else {
        header("Location: estados_lista.php?id_pais={$id_pais}&status=error&message=" . urlencode("Error: " . $stmt->error));
    }
    $stmt->close();
    exit();
}
?>