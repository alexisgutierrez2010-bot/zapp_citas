<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_estado = (int)$_POST['id_estado'];
    $id_pais = (int)$_POST['id_pais']; // Para redirigir de vuelta

    if ($id_estado <= 0 || $id_pais <= 0) {
        header("Location: paises_lista.php?status=error&message=" . urlencode("ID inválido."));
        exit();
    }

    $sql = "DELETE FROM j111_estados WHERE id_estado = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_estado);

    if ($stmt->execute()) {
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'DELETE_STATE', "Se eliminó el estado ID: {$id_estado}.");
        header("Location: estados_lista.php?id_pais={$id_pais}&status=success_delete");
    } else {
        $error_message = "No se puede eliminar el estado. Es posible que esté en uso por un cliente o negocio.";
        header("Location: estados_lista.php?id_pais={$id_pais}&status=error&message=" . urlencode($error_message));
    }
    $stmt->close();
    exit();
}