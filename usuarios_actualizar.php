<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-29-2025).
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

// Solo el rol Administrador puede editar usuarios desde este panel.
if (strcasecmp(trim($rol_session ?? ''), 'Administrador') != 0) {
    header("Location: dashboard.php?status=error&message=" . urlencode("Acceso no autorizado."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Recoger datos del formulario
    $id_usuario = (int)$_POST['id_usuario'];
    $nombre_usuario = trim($_POST['nombre_usuario']);
    $correo_electronico = trim($_POST['correo_electronico']);
    $password = $_POST['password']; // Puede estar vacío
    $rol = $_POST['rol'];
    $id_negocio = (int)$_POST['id_negocio'];
    $activo = isset($_POST['activo']) ? 1 : 0;

    // 2. Validaciones
    if ($id_usuario <= 0 || empty($nombre_usuario) || empty($correo_electronico) || empty($rol) || $id_negocio <= 0) {
        header("Location: usuarios_editar.php?id={$id_usuario}&message_key=" . urlencode("Faltan campos obligatorios."));
        exit();
    }

    // 3. Verificar duplicados (solo nombre de usuario, excluyendo al usuario actual)
    $sql_check = "SELECT id_usuario FROM j100_usuarios WHERE nombre_usuario = ? AND id_usuario != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("si", $nombre_usuario, $id_usuario);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        header("Location: usuarios_editar.php?id={$id_usuario}&message_key=" . urlencode("El nombre de usuario ya está en uso por otra cuenta."));
        exit();
    }
    $stmt_check->close();

    // 4. Construir la consulta de actualización
    if (!empty($password)) {
        // Si se proporcionó una nueva contraseña, la encriptamos
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE j100_usuarios SET nombre_usuario = ?, correo_electronico = ?, password_hash = ?, rol = ?, id_negocio = ?, activo = ? WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssiii", $nombre_usuario, $correo_electronico, $password_hash, $rol, $id_negocio, $activo, $id_usuario);
    } else {
        // Si no se proporcionó contraseña, no actualizamos ese campo
        $sql = "UPDATE j100_usuarios SET nombre_usuario = ?, correo_electronico = ?, rol = ?, id_negocio = ?, activo = ? WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssiii", $nombre_usuario, $correo_electronico, $rol, $id_negocio, $activo, $id_usuario);
    }

    // 5. Ejecutar y redirigir
    if ($stmt->execute()) {
        $descripcion_audit = "Se actualizó el usuario '{$nombre_usuario}' (ID: {$id_usuario}).";
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'UPDATE_USER', $descripcion_audit);
        header("Location: usuarios_lista.php?status=success&message=" . urlencode("Usuario actualizado con éxito."));
    } else {
        header("Location: usuarios_editar.php?id={$id_usuario}&message_key=" . urlencode("Error al actualizar el usuario: " . $stmt->error));
    }

    $stmt->close();
    $conn->close();
    exit();
}
?>