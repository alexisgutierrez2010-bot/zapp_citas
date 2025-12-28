<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_categoria = isset($_POST['id_categoria']) ? (int)$_POST['id_categoria'] : 0;

    if ($id_categoria <= 0) {
        header("Location: categorias_lista.php?status=error&message=" . urlencode("ID de categoría no válido."));
        exit();
    }

    // Obtener nombre para auditoría antes de borrar
    $stmt_info = $conn->prepare("SELECT nombre_categoria FROM j103_categorias WHERE id_categoria = ?");
    $stmt_info->bind_param("i", $id_categoria);
    $stmt_info->execute();
    $info = $stmt_info->get_result()->fetch_assoc();
    $nombre_categoria_eliminada = $info['nombre_categoria'] ?? 'Desconocido';
    $stmt_info->close();

    // Preparar la consulta SQL de eliminación
    $sql = "DELETE FROM j103_categorias WHERE id_categoria = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id_categoria);

        if ($stmt->execute()) {
            $descripcion_audit = "Se eliminó la categoría '{$nombre_categoria_eliminada}' (ID: {$id_categoria}).";
            registrar_auditoria($conn, $_SESSION['id_usuario'], null, 'DELETE_CATEGORY', $descripcion_audit);
            header("Location: categorias_lista.php?status=success");
        } else {
            // Error común: la categoría está en uso por un negocio.
            $error_message = "No se puede eliminar la categoría. Es posible que esté asignada a uno o más negocios.";
            header("Location: categorias_lista.php?status=error&message=" . urlencode($error_message));
        }
        $stmt->close();
    }
    exit();
}