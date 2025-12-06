<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025). Corregido para compatibilidad con Linux.
require_once 'auth_check.php';
require_once 'audit_log.php';
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Recoger y validar el ID del cliente
    $id_cliente = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 0;

    if ($id_cliente <= 0) {
        header("Location: clientes_lista.php?status=error&message=" . urlencode("ID de cliente inválido."));
        exit();
    }

    // Obtener nombre para auditoría antes de borrar
    $stmt_info = $conn->prepare("SELECT nombre_completo FROM j106_clientes WHERE id_cliente = ?");
    $stmt_info->bind_param("i", $id_cliente);
    $stmt_info->execute();
    $info = $stmt_info->get_result()->fetch_assoc();
    $nombre_cliente_eliminado = $info['nombre_completo'] ?? 'Desconocido';
    $stmt_info->close();

    // 2. Preparar la consulta SQL de eliminación
    $sql = "UPDATE j106_clientes SET activo = 0 WHERE id_cliente = ? AND id_negocio = ?"; // Borrado lógico

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $id_cliente, $id_negocio_session);

        if ($stmt->execute()) {
            $descripcion_audit = "Se marcó como inactivo al cliente '{$nombre_cliente_eliminado}' (ID: {$id_cliente}).";
            registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'DEACTIVATE_CLIENT', $descripcion_audit);

            header("Location: clientes_lista.php?status=success_delete");
        } else {
            header("Location: clientes_lista.php?status=error&message=" . urlencode($stmt->error));
        }
        $stmt->close();
    } else {
        header("Location: dashboard.php?status=error&message=" . urlencode($conn->error));
    }

    exit();
}
?>