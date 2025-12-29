<?php
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

// Solo el rol Administrador puede resetear claves.
if (strcasecmp(trim($rol_session ?? ''), 'Administrador') != 0) {
    header("Location: dashboard.php?status=error&message=" . urlencode("Acceso no autorizado."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario = (int)$_POST['id_usuario'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($id_usuario <= 0) {
        header("Location: usuarios_lista.php?status=error&message_key=" . urlencode("ID de usuario inválido."));
        exit();
    }

    if (empty($new_password) || $new_password !== $confirm_password) {
        header("Location: usuarios_reset_clave.php?id={$id_usuario}&message_key=" . urlencode("Las contraseñas no coinciden o están vacías."));
        exit();
    }

    // Encriptar la nueva contraseña
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    // Actualizar en la base de datos
    $sql = "UPDATE j100_usuarios SET password_hash = ? WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $password_hash, $id_usuario);

    if ($stmt->execute()) {
        // Obtener nombre de usuario para auditoría
        $stmt_info = $conn->prepare("SELECT nombre_usuario FROM j100_usuarios WHERE id_usuario = ?");
        $stmt_info->bind_param("i", $id_usuario);
        $stmt_info->execute();
        $info = $stmt_info->get_result()->fetch_assoc();
        $nombre_usuario_afectado = $info['nombre_usuario'] ?? 'Desconocido';
        $stmt_info->close();

        $descripcion_audit = "El administrador '{$_SESSION['nombre_usuario']}' reseteó la contraseña para el usuario '{$nombre_usuario_afectado}' (ID: {$id_usuario}).";
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'ADMIN_PASSWORD_RESET', $descripcion_audit);
        
        header("Location: usuarios_lista.php?status=success&message_key=" . urlencode("Contraseña para {$nombre_usuario_afectado} reseteada con éxito."));
    } else {
        header("Location: usuarios_reset_clave.php?id={$id_usuario}&message_key=" . urlencode("Error al actualizar la contraseña: " . $stmt->error));
    }

    $stmt->close();
    $conn->close();
    exit();
}
?>