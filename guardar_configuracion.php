<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
require_once 'Auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

// Solo un Master puede ejecutar este script
if ($rol_session != 'Master') {
    header("Location: dashboard.php?status=error&message=" . urlencode("Acceso no autorizado."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Datos del negocio
    $nombre_negocio = trim($_POST['nombre_negocio']);
    $email_negocio = trim($_POST['email']);

    // Datos para el nuevo usuario Admin
    $admin_user = trim($_POST['admin_user']);
    $admin_pass = $_POST['admin_pass'];
    $admin_email = trim($_POST['admin_email']);

    // Validaciones básicas
    if (empty($nombre_negocio) || empty($email_negocio) || empty($admin_user) || empty($admin_pass) || empty($admin_email)) {
        header("Location: crear_negocio.php?status=error&message=" . urlencode("Todos los campos son obligatorios."));
        exit();
    }

    // Iniciar transacción para asegurar que ambas operaciones (crear negocio y crear usuario) se completen
    $conn->begin_transaction();

    try {
        // 1. Crear la nueva configuración del negocio
        $sql_config = "INSERT INTO j102_negocios (nombre_negocio, email) VALUES (?, ?)";
        $stmt_config = $conn->prepare($sql_config);
        $stmt_config->bind_param("ss", $nombre_negocio, $email_negocio);
        $stmt_config->execute();
        $id_nuevo_negocio = $stmt_config->insert_id;
        $stmt_config->close();

        // 2. Crear el usuario Admin para este nuevo negocio
        $password_hash = password_hash($admin_pass, PASSWORD_DEFAULT);
        $rol_admin = 'Propietario';
        $sql_user = "INSERT INTO j100_usuarios (nombre_usuario, correo_electronico, password_hash, rol, id_negocio) VALUES (?, ?, ?, ?, ?)";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("ssssi", $admin_user, $admin_email, $password_hash, $rol_admin, $id_nuevo_negocio);
        $stmt_user->execute();
        $id_nuevo_usuario = $stmt_user->insert_id;
        $stmt_user->close();

        // Si todo fue bien, confirmar la transacción
        $conn->commit();

        // Registrar auditoría
        $descripcion_audit = "Se creó el nuevo negocio '{$nombre_negocio}' (ID: {$id_nuevo_negocio}) y su usuario admin '{$admin_user}'.";
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'CREATE_BUSINESS', $descripcion_audit);

        header("Location: configuracion.php?id={$id_nuevo_negocio}&status=success&message=" . urlencode("Negocio creado con éxito. Ya puedes editar sus detalles."));

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback(); // Revertir cambios si algo falla

        // Analizar el error para dar un mensaje más útil
        if ($conn->errno == 1062) { // Código de error para entrada duplicada
            header("Location: crear_negocio.php?status=error&message=" . urlencode("Error: El nombre del negocio, el usuario admin o el email ya existen."));
        } else {
            header("Location: crear_negocio.php?status=error&message=" . urlencode("Error de base de datos: " . $exception->getMessage()));
        }
    } finally {
        $conn->close();
        exit();
    }
}