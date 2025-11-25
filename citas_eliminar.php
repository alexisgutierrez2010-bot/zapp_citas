<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-20-2025).
require_once 'auth_check.php';
require_once 'audit_log.php';
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Recoger y validar el ID de la cita
    $id_cita = isset($_POST['id_cita']) ? (int)$_POST['id_cita'] : 0;

    if ($id_cita <= 0) {
        header("Location: citas_lista.php?status=error&message=" . urlencode("ID de cita inválido."));
        exit();
    }

    // 2. Preparar la consulta SQL de eliminación
    $sql = "DELETE FROM j108_citas WHERE id_cita = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id_cita);

        if ($stmt->execute()) {
            $descripcion_audit = "Se eliminó la cita (ID: {$id_cita}).";
            registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'DELETE_APPOINTMENT', $descripcion_audit);

            header("Location: citas_lista.php?status=success_delete");
        } else {
            header("Location: citas_lista.php?status=error&message=" . urlencode($stmt->error));
        }
        $stmt->close();
    } else {
        header("Location: citas_lista.php?status=error&message=" . urlencode($conn->error));
    }

    exit();
}
?>