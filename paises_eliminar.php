<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_pais = (int)$_POST['id_pais'];

    if ($id_pais <= 0) {
        header("Location: paises_lista.php?status=error&message=" . urlencode("ID inválido."));
        exit();
    }

    // La BD está configurada con ON DELETE CASCADE, así que los estados se borrarán automáticamente.
    $sql = "DELETE FROM j110_paises WHERE id_pais = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_pais);

    if ($stmt->execute()) {
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'DELETE_COUNTRY', "Se eliminó el país ID: {$id_pais} y sus estados.");
        header("Location: paises_lista.php?status=success_delete");
    } else {
        $error_message = "No se puede eliminar el país. Es posible que esté en uso por un cliente o negocio.";
        header("Location: paises_lista.php?status=error&message=" . urlencode($error_message));
    }
    $stmt->close();
    exit();
}
?>