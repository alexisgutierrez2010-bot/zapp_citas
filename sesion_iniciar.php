<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
session_start();

// Si el usuario ya ha iniciado sesión, redirigirlo a la página principal
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

// Si se recibe un mensaje de error desde procesar_login.php, se mostrará.
$error_msg = "";
if (isset($_GET['error'])) {
    $error_msg = htmlspecialchars($_GET['error']);
}

// Generar un nuevo código CAPTCHA para mostrar en el formulario
// Se genera un hash corto basado en la hora actual y un "salt" aleatorio.
$_SESSION['captcha_time'] = time();
$_SESSION['captcha_hash'] = strtoupper(substr(sha1(session_id() . $_SESSION['captcha_time']), 0, 6));

?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container vh-100 d-flex justify-content-center align-items-center">
        <div class="card" style="width: 22rem;">
            <div class="card-header text-center">
                <h3>Iniciar Sesión</h3>
            </div>
            <div class="card-body">
                <?php 
                if(!empty($error_msg)){
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
                    <div class="text-center mt-3">
                        <a href="olvide_clave.php">Olvidé mi clave de usuario</a>
                    </div>
                </form>
            </div>
        </div>
    </div>    

    <?php include 'footer.php'; ?>
</body>
</html>