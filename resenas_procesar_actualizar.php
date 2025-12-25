<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_resena = (int)$_POST['id_resena'];
    $puntuacion = (float)$_POST['puntuacion'];
    $comentario = trim($_POST['comentario']);
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($id_resena <= 0 || $puntuacion < 1.0 || $puntuacion > 5.0) {
        header("Location: resenas_lista.php?status=error&message=Datos inválidos.");
        exit();
    }

    $sql = "UPDATE j112_resenas SET puntuacion = ?, comentario = ?, activo = ? WHERE id_resena = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dsii", $puntuacion, $comentario, $activo, $id_resena);

    if ($stmt->execute()) {
        registrar_auditoria($conn, $_SESSION['id_usuario'], null, 'UPDATE_REVIEW', "Se actualizó la reseña ID {$id_resena}.");
        header("Location: resenas_lista.php?status=success&message=Reseña actualizada con éxito.");
    } else {
        header("Location: resenas_lista.php?status=error&message=Error al actualizar la reseña: " . $stmt->error);
    }
    $stmt->close();
    exit();
}