<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
require_once 'auth_check.php';
require_once 'audit_log.php';
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario_eliminar = isset($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : 0;

    // Validaciones de seguridad
    if ($id_usuario_eliminar <= 0) {
        header("Location: usuarios_lista.php?status=error&message=" . urlencode("ID de usuario inválido."));
        exit();
    }

    if ($id_usuario_eliminar == $_SESSION['id_usuario']) {
        header("Location: usuarios_lista.php?status=error&message=" . urlencode("No puedes eliminarte a ti mismo."));
        exit();
    }

    // Obtener info del usuario a eliminar para auditoría y validación
    $stmt_user = $conn->prepare("SELECT nombre_usuario, rol, id_negocio FROM j100_usuarios WHERE id_usuario = ?");
    $stmt_user->bind_param("i", $id_usuario_eliminar);
    $stmt_user->execute();
    $usuario = $stmt_user->get_result()->fetch_assoc();
    $stmt_user->close();

    if (!$usuario) {
        header("Location: usuarios_lista.php?status=error&message=" . urlencode("El usuario que intentas eliminar no existe."));
        exit();
    }

    // Un Admin no puede eliminar a un Master
    if ($rol_session == 'Admin' && $usuario['rol'] == 'Master') {
        header("Location: usuarios_lista.php?status=error&message=" . urlencode("No tienes permiso para eliminar a un usuario Master."));
        exit();
    }

    $sql = "UPDATE j100_usuarios SET activo = 0 WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario_eliminar);

    if ($stmt->execute()) {
        $descripcion_audit = "Se desactivó el usuario '{$usuario['nombre_usuario']}' (ID: {$id_usuario_eliminar}).";
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'DEACTIVATE_USER', $descripcion_audit);
        header("Location: usuarios_lista.php?status=success&message=" . urlencode("Usuario desactivado con éxito."));
    } else {
        header("Location: usuarios_lista.php?status=error&message=" . urlencode("Error al eliminar el usuario: " . $stmt->error));
    }

    $stmt->close();
    $conn->close();
    exit();
}