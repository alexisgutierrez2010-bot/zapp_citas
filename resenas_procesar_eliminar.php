<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

// Solo el rol Administrador puede eliminar reseñas.
if (strcasecmp(trim($rol_session ?? ''), 'Administrador') != 0) {
    header("Location: dashboard.php?status=error&message=" . urlencode("Acceso no autorizado."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_resena = (int)$_POST['id_resena'];

    if ($id_resena <= 0) {
        header("Location: resenas_lista.php?status=error&message=" . urlencode("ID de reseña inválido."));
        exit();
    }

    $sql = "DELETE FROM j112_resenas WHERE id_resena = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_resena);

    if ($stmt->execute()) {
        registrar_auditoria($conn, $_SESSION['id_usuario'], null, 'DELETE_REVIEW', "Se eliminó la reseña ID: {$id_resena}.");
        header("Location: resenas_lista.php?status=success&message=" . urlencode("Reseña eliminada permanentemente."));
    } else {
        header("Location: resenas_lista.php?status=error&message=" . urlencode("Error al eliminar la reseña: " . $stmt->error));
    }

    $stmt->close();
    $conn->close();
    exit();
}
?>