<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-20-2025).
require_once 'auth_check.php';
require_once 'audit_log.php';
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Recoger datos
    $id_usuario = (int)$_POST['id_usuario'];
    $nombre_usuario = trim($_POST['nombre_usuario']);
    $correo_electronico = trim($_POST['correo_electronico']);
    $password = $_POST['password'];
    $rol = $_POST['rol'];
    $id_negocio = (int)$_POST['id_negocio'];
    $activo = isset($_POST['activo']) ? 1 : 0;

    // 2. Validaciones de seguridad
    if ($id_usuario <= 0) {
        header("Location: usuarios_lista.php?status=error&message=" . urlencode("ID de usuario inválido."));
        exit();
    }

    // 3. Verificar duplicados (excluyendo al usuario actual)
    $sql_check = "SELECT id_usuario FROM j100_usuarios WHERE (nombre_usuario = ? OR correo_electronico = ?) AND id_usuario != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ssi", $nombre_usuario, $correo_electronico, $id_usuario);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        header("Location: usuarios_editar.php?id={$id_usuario}&status=error&message=" . urlencode("El nombre de usuario o correo ya está en uso por otro usuario."));
        exit();
    }
    $stmt_check->close();

    // 4. Construir la consulta de actualización
    $sql_parts = ["nombre_usuario = ?", "correo_electronico = ?"];
    $params = [$nombre_usuario, $correo_electronico];
    $types = "ss";

    $sql_parts[] = "id_negocio = ?"; // Añadir el campo id_negocio a la actualización
    $params[] = $id_negocio;
    $types .= "i";

    if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql_parts[] = "password_hash = ?";
        $params[] = $password_hash;
        $types .= "s";
    }

    // Asegurarse de que el rol siempre se actualice
    $sql_parts[] = "rol = ?";
    $params[] = $rol;
    $types .= "s";

    $sql_parts[] = "activo = ?";
    $params[] = $activo;
    $types .= "i";

    $params[] = $id_usuario;
    $types .= "i";

    $sql = "UPDATE j100_usuarios SET " . implode(", ", $sql_parts) . " WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $descripcion_audit = "Se actualizó el usuario '{$nombre_usuario}' (ID: {$id_usuario}).";
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'UPDATE_USER', $descripcion_audit);
        header("Location: usuarios_lista.php?status=success&message=" . urlencode("Usuario actualizado con éxito."));
    } else {
        header("Location: usuarios_editar.php?id={$id_usuario}&status=error&message=" . urlencode("Error al actualizar: " . $stmt->error));
    }

    $stmt->close();
    exit();
}
?>