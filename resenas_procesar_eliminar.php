<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_resena = isset($_POST['id_resena']) ? (int)$_POST['id_resena'] : 0;

    if ($id_resena <= 0) {
        header("Location: resenas_lista.php?status=error&message=ID de reseña no válido.");
        exit();
    }

    // Hard delete
    $sql = "DELETE FROM j112_resenas WHERE id_resena = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_resena);

    if ($stmt->execute()) {
        registrar_auditoria($conn, $_SESSION['id_usuario'], null, 'DELETE_REVIEW', "Se eliminó permanentemente la reseña ID {$id_resena}.");
        header("Location: resenas_lista.php?status=success&message=Reseña eliminada con éxito.");
    } else {
        header("Location: resenas_lista.php?status=error&message=Error al eliminar la reseña: " . $stmt->error);
    }
    $stmt->close();
    exit();
}