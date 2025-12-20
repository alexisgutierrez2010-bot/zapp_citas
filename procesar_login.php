<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.

session_start();

// Incluir archivos de configuración y auditoría
require_once 'config.php';
require_once 'audit_log.php';

// Verificar si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validar que los campos no estén vacíos
    if (empty(trim($_POST["nombre_usuario"])) || empty(trim($_POST["password"]))) {
        header("location: sesion_iniciar.php?error_key=login_error_empty_fields");
        exit;
    }

    $username = trim($_POST["nombre_usuario"]);
    $password = trim($_POST["password"]);

    // Preparar la consulta SQL para buscar el usuario
    // Se busca por nombre de usuario O correo electrónico
    $sql = "SELECT id_usuario, id_negocio, nombre_usuario, password_hash, rol, activo FROM j100_usuarios WHERE nombre_usuario = ? OR correo_electronico = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ss", $username, $username);
        
        if ($stmt->execute()) {
            $stmt->store_result();

            // Verificar si el usuario existe
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id_usuario, $id_negocio, $nombre_usuario, $password_hash, $rol, $activo);
                if ($stmt->fetch()) {
                    // Verificar si la cuenta está activa
                    if ($activo == 1) {
                        // Verificar la contraseña
                        if (password_verify($password, $password_hash)) {
                            // Contraseña correcta: Iniciar sesión
                            session_regenerate_id();
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id_usuario"] = $id_usuario;
                            $_SESSION["id_negocio"] = $id_negocio;
                            $_SESSION["nombre_usuario"] = $nombre_usuario;
                            $_SESSION["rol"] = $rol;
                            $_SESSION["last_activity"] = time();

                            // Registrar auditoría de éxito
                            registrar_auditoria($conn, $id_usuario, $id_negocio, 'LOGIN_SUCCESS', "Inicio de sesión exitoso para usuario: $nombre_usuario");

                            // Redirigir al dashboard
                            header("location: dashboard.php");
                            exit;
                        } else {
                            // Contraseña incorrecta
                            registrar_auditoria($conn, $id_usuario, $id_negocio, 'LOGIN_FAILED', "Password incorrecto para: $username");
                            header("location: sesion_iniciar.php?error_key=login_error_invalid_password");
                            exit;
                        }
                    } else {
                        // Cuenta inactiva
                        header("location: sesion_iniciar.php?error_key=login_error_account_inactive");
                        exit;
                    }
                }
            } else {
                // Usuario no encontrado
                header("location: sesion_iniciar.php?error_key=login_error_user_not_found");
                exit;
            }
        } else {
            // Error de ejecución SQL
            header("location: sesion_iniciar.php?error_key=login_error_system");
            exit;
        }
        $stmt->close();
    } else {
        // Error de preparación SQL
        header("location: sesion_iniciar.php?error_key=login_error_system");
        exit;
    }
    $conn->close();
} else {
    // Acceso directo al script sin POST
    header("location: sesion_iniciar.php");
    exit;
}
?>