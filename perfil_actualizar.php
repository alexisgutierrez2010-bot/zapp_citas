<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
require_once 'auth_check.php';
require_once 'audit_log.php';
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario = $_SESSION['id_usuario'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Validar que las contraseñas nuevas coincidan
    if ($new_password !== $confirm_password) {
        header("Location: perfil_ver.php?status=error&message=" . urlencode("La nueva contraseña y su confirmación no coinciden."));
        exit();
    }

    // 2. Obtener el hash de la contraseña actual de la base de datos
    $stmt = $conn->prepare("SELECT password_hash FROM j100_usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();

    if (!$usuario) {
        header("Location: perfil_ver.php?status=error&message=" . urlencode("Error: Usuario no encontrado."));
        exit();
    }

    // 3. Verificar que la contraseña actual sea correcta
    if (!password_verify($current_password, $usuario['password_hash'])) {
        header("Location: perfil_ver.php?status=error&message=" . urlencode("La contraseña actual es incorrecta."));
        exit();
    }

    // 4. Si todo es correcto, encriptar y actualizar la nueva contraseña
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    $update_stmt = $conn->prepare("UPDATE j100_usuarios SET password_hash = ? WHERE id_usuario = ?");
    $update_stmt->bind_param("si", $new_password_hash, $id_usuario);

    if ($update_stmt->execute()) {
        // Registrar auditoría
        $descripcion_audit = "El usuario '{$_SESSION['nombre_usuario']}' ha cambiado su propia contraseña.";
        registrar_auditoria($conn, $id_usuario, $id_negocio_session, 'UPDATE_PROFILE_PASSWORD', $descripcion_audit);

        header("Location: perfil_ver.php?status=success&message=" . urlencode("Contraseña actualizada con éxito."));
    } else {
        header("Location: perfil_ver.php?status=error&message=" . urlencode("Error al actualizar la contraseña en la base de datos."));
    }

    $update_stmt->close();
    exit();
}
?>