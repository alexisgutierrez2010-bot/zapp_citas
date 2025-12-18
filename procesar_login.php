<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
session_start(); // CORRECCIÓN: Iniciar la sesión al principio del script.

require_once 'config.php';
require_once 'audit_log.php'; // CORRECCIÓN: Incluir antes de usar la función

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_usuario = $_POST['nombre_usuario'];
    $password = $_POST['password'];

    $sql = "SELECT id_usuario, password_hash, id_negocio, rol, activo FROM j100_usuarios WHERE nombre_usuario = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $nombre_usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $usuario = $result->fetch_assoc();

            // ¡VALIDACIÓN DE SEGURIDAD! Verificar si el usuario está activo.
            if ($usuario['activo'] != 1) {
                header("Location: sesion_iniciar.php?error_key=login_error_inactive");
                exit();
            }

            // Verificar la contraseña
            if (password_verify($password, $usuario['password_hash'])) {
                // Contraseña correcta, iniciar sesión
                $_SESSION['loggedin'] = true;
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nombre_usuario'] = $nombre_usuario; // ¡La clave para multiempresa!
                
                // --- SOLUCIÓN DEFINITIVA PARA EL USUARIO MASTER ---
                // Si el usuario es 'Master', su id_negocio en la BD es NULL. Le asignamos el negocio 1 por defecto para que pueda operar.
                $_SESSION['id_negocio'] = ($usuario['rol'] === 'Master') ? 1 : $usuario['id_negocio'];

                $_SESSION['rol'] = $usuario['rol'];
                $_SESSION['last_activity'] = time(); // Iniciar el contador de inactividad

                // Registrar auditoría de inicio de sesión
                registrar_auditoria($conn, $usuario['id_usuario'], $usuario['id_negocio'], 'LOGIN_SUCCESS', "El usuario '{$nombre_usuario}' ha iniciado sesión.");

                // Redirigir a la página de inicio
                header("Location: dashboard.php"); // CORRECCIÓN: Redirigir directamente al dashboard
                exit();
            } else {
                // Contraseña incorrecta
                header("Location: sesion_iniciar.php?error_key=login_error_invalid_password");
                exit();
            }
        }
    }
    // Si el bucle termina sin un login exitoso, significa que el usuario no fue encontrado.
    header("Location: sesion_iniciar.php?error_key=login_error_user_not_found");
    exit();
}
?>