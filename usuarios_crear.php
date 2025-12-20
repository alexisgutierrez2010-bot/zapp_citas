<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
require_once 'auth_check.php';
require_once 'audit_log.php';
require_once 'config.php'; // Ajustado para la nueva estructura

// Solo Master y Admin pueden crear usuarios
/*
if ($rol_session != 'Master') {
    header("Location: dashboard.php?status=error&message=" . urlencode("No tienes permiso para esta acción."));
    exit;
}
*/

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Recoger datos del formulario
    $nombre_usuario = trim($_POST['nombre_usuario']);
    $correo_electronico = trim($_POST['correo_electronico']);
    $password = $_POST['password'];
    $rol = $_POST['rol'];
    $activo = isset($_POST['activo']) ? 1 : 0;

    // Si el usuario es Master, puede asignar un negocio. Si es Admin, se asigna su propio negocio.
    $id_negocio = (int)$_POST['id_negocio'];

    // 2. Validaciones
    if (empty($nombre_usuario) || empty($correo_electronico) || empty($password) || empty($rol)) {
        header("Location: usuarios_lista.php?status=error&message=" . urlencode("Todos los campos son obligatorios."));
        exit();
    }

    // 3. Verificar duplicados (nombre de usuario o email)
    $sql_check = "SELECT id_usuario FROM j100_usuarios WHERE nombre_usuario = ? OR correo_electronico = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ss", $nombre_usuario, $correo_electronico);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        header("Location: usuarios_lista.php?status=error&message=" . urlencode("El nombre de usuario o el correo ya existen."));
        exit();
    }
    $stmt_check->close();

    // 4. Encriptar contraseña y guardar en la base de datos
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO j100_usuarios (nombre_usuario, correo_electronico, password_hash, rol, id_negocio, activo, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssii", $nombre_usuario, $correo_electronico, $password_hash, $rol, $id_negocio, $activo);

    if ($stmt->execute()) {
        $id_nuevo_usuario = $stmt->insert_id;
        $descripcion_audit = "Se creó el usuario '{$nombre_usuario}' (ID: {$id_nuevo_usuario}) con el rol '{$rol}'.";
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'CREATE_USER', $descripcion_audit);
        header("Location: usuarios_lista.php?status=success&message=" . urlencode("Usuario creado con éxito."));
    } else {
        header("Location: usuarios_lista.php?status=error&message=" . urlencode("Error al crear el usuario: " . $stmt->error));
    }

    $stmt->close();
    exit();
}