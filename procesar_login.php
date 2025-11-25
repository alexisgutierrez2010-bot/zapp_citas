<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
require_once 'config.php';
require_once 'audit_log.php'; // CORRECCIÓN: Incluir antes de usar la función

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_usuario = $_POST['usuario'];
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
                header("Location: login.php?error=" . urlencode("Tu cuenta de usuario ha sido desactivada."));
                exit();
            }

            // Verificar la contraseña
            if (password_verify($password, $usuario['password_hash'])) {
                // Contraseña correcta, iniciar sesión
                session_start();
                $_SESSION['loggedin'] = true;
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nombre_usuario'] = $nombre_usuario; // ¡La clave para multiempresa!
                $_SESSION['id_negocio'] = $usuario['id_negocio'];
                $_SESSION['rol'] = $usuario['rol'];
                $_SESSION['last_activity'] = time(); // Iniciar el contador de inactividad

                // Registrar auditoría de inicio de sesión
                registrar_auditoria($conn, $usuario['id_usuario'], $usuario['id_negocio'], 'LOGIN_SUCCESS', "El usuario '{$nombre_usuario}' ha iniciado sesión.");

                // Redirigir a la página de inicio
                header("Location: dashboard.php"); // CORRECCIÓN: Redirigir directamente al dashboard
                exit();
            }
        }
    }
    // Si algo falla, redirigir de vuelta al login con un error
    header("Location: login.php?error=" . urlencode("Usuario o contraseña incorrectos."));
    exit();
}
?>