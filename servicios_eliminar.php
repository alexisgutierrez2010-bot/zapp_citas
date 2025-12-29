<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
require_once 'auth_check.php';
require_once 'audit_log.php';
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Seguridad: Solo el rol Administrador puede desactivar servicios.
    if (strcasecmp(trim($rol_session ?? ''), 'Administrador') != 0) {
        header("Location: dashboard.php?status=error&message=" . urlencode("Acceso no autorizado."));
        exit;
    }

    // 1. Recoger y validar el ID del servicio
    $id_servicio = isset($_POST['id_servicio']) ? (int)$_POST['id_servicio'] : 0;

    if ($id_servicio <= 0) {
        header("Location: servicios_lista.php?status=error&message=" . urlencode("ID de servicio inválido."));
        exit();
    }

    // Obtener nombre para auditoría antes de borrar
    $stmt_info = $conn->prepare("SELECT nombre_servicio FROM j104_servicios WHERE id_servicio = ?");
    $stmt_info->bind_param("i", $id_servicio);
    $stmt_info->execute();
    $info = $stmt_info->get_result()->fetch_assoc();
    $nombre_servicio_eliminado = $info['nombre_servicio'] ?? 'Desconocido';
    $stmt_info->close();

    // 2. Preparar la consulta SQL de eliminación
    $sql = "UPDATE j104_servicios SET activo = 0 WHERE id_servicio = ?"; // Eliminación lógica

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id_servicio);

        if ($stmt->execute()) {
        $descripcion_audit = "Se desactivó el servicio '{$nombre_servicio_eliminado}' (ID: {$id_servicio}).";
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'DEACTIVATE_SERVICE', $descripcion_audit);

        header("Location: servicios_lista.php?status=success&message=" . urlencode("Servicio desactivado con éxito."));
        } else {
            // Error común: el servicio está en uso.
            $error_message = "No se puede eliminar el servicio. Es posible que esté asignado a una o más citas.";
            header("Location: servicios_lista.php?status=error&message=" . urlencode($error_message));
        }
        $stmt->close();
    } else {
        header("Location: servicios_lista.php?status=error&message=" . urlencode($conn->error));
    }

    exit();
}