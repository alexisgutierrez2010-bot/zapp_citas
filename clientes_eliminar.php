<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025). Corregido para compatibilidad con Linux.
require_once 'auth_check.php';
require_once 'audit_log.php';
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Seguridad: Solo el rol Administrador puede desactivar clientes.
    if (strcasecmp(trim($rol_session ?? ''), 'Administrador') != 0) {
        header("Location: dashboard.php?status=error&message=" . urlencode("Acceso no autorizado."));
        exit;
    }

    // 1. Recoger y validar el ID del cliente
    $id_cliente = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 0;

    if ($id_cliente <= 0) {
        header("Location: clientes_lista.php?status=error&message=" . urlencode("ID de cliente inválido."));
        exit();
    }

    // Obtener nombre para auditoría antes de borrar
    $stmt_info = $conn->prepare("SELECT nombre_completo, id_negocio FROM j106_clientes WHERE id_cliente = ?");
    $stmt_info->bind_param("i", $id_cliente);
    $stmt_info->execute();
    $info = $stmt_info->get_result()->fetch_assoc();
    $nombre_cliente_eliminado = $info['nombre_completo'] ?? 'Desconocido';
    $id_negocio_cliente = $info['id_negocio'] ?? null;
    $stmt_info->close();

    // 2. Preparar la consulta SQL de eliminación
    $sql = "UPDATE j106_clientes SET activo = 0 WHERE id_cliente = ?"; // Borrado lógico

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id_cliente);

        if ($stmt->execute()) {
            $descripcion_audit = "Se marcó como inactivo al cliente '{$nombre_cliente_eliminado}' (ID: {$id_cliente}).";
            registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_cliente, 'DEACTIVATE_CLIENT', $descripcion_audit);

            header("Location: clientes_lista.php?status=success&message=" . urlencode("Cliente desactivado con éxito."));
        } else {
            header("Location: clientes_lista.php?status=error&message=" . urlencode($stmt->error));
        }
        $stmt->close();
    }

    exit();
}
?>