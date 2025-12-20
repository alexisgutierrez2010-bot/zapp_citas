<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Corregido el inicio de sesión para evitar pantalla en blanco.
session_start(); // SOLUCIÓN: Iniciar la sesión al principio de todo.

// Si el usuario ya ha iniciado sesión, redirigirlo a la página principal
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    // No necesitamos cargar el idioma aquí, solo redirigir.
    // El dashboard.php se encargará de cargar el idioma correcto.
    header("location: dashboard.php");
    exit;
}

// Eliminado sistema de internacionalización (i18n).
// require_once __DIR__ . '/languages.php';

// Si se recibe un mensaje de error desde procesar_login.php, se mostrará.
$error_msg = "";
if (isset($_GET['error'])) {
    $error_msg = htmlspecialchars($_GET['error']);
} elseif (isset($_GET['error_key'])) {
    $error_key = $_GET['error_key'];
    // CORRECCIÓN: Mapear las claves de error a mensajes claros en español.
    switch ($error_key) {
        case 'login_error_empty_fields':
            $error_msg = 'Por favor, complete todos los campos.';
            break;
        case 'login_error_session_expired':
            $error_msg = 'Su sesión ha expirado por inactividad. Por favor, inicie sesión de nuevo.';
            break;
        case 'login_error_user_not_found':
            $error_msg = 'El nombre de usuario no existe.';
            break;
        case 'login_error_invalid_password':
            $error_msg = 'La contraseña es incorrecta.';
            break;
        case 'login_error_user_inactive':
            $error_msg = 'Este usuario se encuentra inactivo.';
            break;
        case 'login_error_system':
            $error_msg = 'Error del sistema. Por favor intente más tarde.';
            break;
        default:
            $error_msg = 'Ha ocurrido un error inesperado. Por favor, intente de nuevo.';
            break;
    }
}

?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f4f7f6;
        }
        .main-container {
            flex: 1;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="index.php" style="padding-top: 0; padding-bottom: 0;">
                <img src="logo_zapp_citas.png" alt="Logo ZApp Citas" style="height: 1.5em; margin-right: 10px; filter: drop-shadow(1px 1px 2px rgba(0,0,0,0.5));">
                <span style="text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">
                    ZApp Citas
                    <span class="ms-2 fw-normal text-white-50" style="font-size: 0.8em;">Panel de Administración</span>
                </span>
            </a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="ayuda_zapp_citas.php" target="_blank">❓ Ayuda</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container main-container d-flex justify-content-center align-items-center">
        <div class="card" style="width: 22rem;">
            <div class="card-header text-center bg-primary text-white">
                <h3>Acceso al Panel Administrador</h3>
            </div>
            <div class="card-body">
                <?php 
                if(!empty($error_msg)){
                    // Los mensajes de error del backend aún no están traducidos, se hará en un paso posterior.
                    echo '<div class="alert alert-danger">' . $error_msg . '</div>'; 
                }        
                ?>
                <form action="procesar_login.php" method="post">
                    <div class="mb-3">
                        <label class="form-label">Usuario</label>
                        <input type="text" name="nombre_usuario" class="form-control" autocomplete="username">
                    </div>    
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-control" autocomplete="current-password">
                    </div>
                    <!-- SECCIÓN DE CAPTCHA DESACTIVADA PARA DESARROLLO -->
                    <!--
                    <div class="mb-3 text-center">
                        <label for="captcha" class="form-label">Código de Seguridad</label>
                        <div class="p-2 bg-dark text-white rounded font-monospace fs-4" style="letter-spacing: 5px;">
                            <?php //echo $_SESSION['captcha_hash']; ?>
                        </div>
                        <input type="text" name="captcha" id="captcha" class="form-control mt-2 text-center" autocomplete="off" required>
                        <small class="form-text text-muted">El código expira en 2 minutos.</small>
                    </div>
                    -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Entrar</button>
                    </div>
                    <hr>
                    <div class="text-center mt-3">
                        <a href="olvide_clave.php">¿Olvidó su contraseña?</a>
                    </div>
                    <div class="text-center mt-2">
                        <a href="index.php" class="text-muted"><small>Volver al Inicio</small></a>
                    </div>
                </form>
            </div>
        </div>
    </div>    

    <?php include 'footer.php'; ?>
</body>
</html>