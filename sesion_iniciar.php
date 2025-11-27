<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
session_start();

// Si el usuario ya ha iniciado sesión, redirigirlo a la página principal
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

require_once "config.php";
require_once "audit_log.php"; // Incluir para registrar la auditoría

$nombre_usuario = $password = "";
$error_msg = "";

// --- LÓGICA DEL CAPTCHA SIMPLE ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Validar el CAPTCHA primero
    $captcha_valido = true; // CAPTCHA DESACTIVADO PARA DESARROLLO
    /*
    // --- BLOQUE DE VALIDACIÓN DE CAPTCHA ORIGINAL ---
    // ... (La lógica de validación original queda aquí comentada) ...
    */

    // Limpiar el captcha de la sesión después del intento para forzar uno nuevo
    unset($_SESSION['captcha_hash']);
    unset($_SESSION['captcha_time']);

    // 2. Si el CAPTCHA es válido, proceder con la validación del usuario
    if ($captcha_valido) {
    $nombre_usuario = trim($_POST["nombre_usuario"]);
    $password = trim($_POST["password"]);

    if (empty($nombre_usuario) || empty($password)) {
        $error_msg = "Por favor, ingrese su usuario y contraseña.";
    } else {
        $sql = "SELECT id_usuario, nombre_usuario, password_hash, rol, id_negocio FROM j100_usuarios WHERE nombre_usuario = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $nombre_usuario);
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $user, $hashed_password, $rol, $id_negocio);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            // Contraseña correcta, iniciar sesión
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id_usuario"] = $id;
                            $_SESSION["nombre_usuario"] = $user;
                            $_SESSION["rol"] = $rol;
                            $_SESSION["id_negocio"] = $id_negocio;
                            
                            // Registrar auditoría de inicio de sesión
                            registrar_auditoria($conn, $id, $id_negocio, 'LOGIN_SUCCESS', "El usuario '{$user}' ha iniciado sesión.");

                            header("location: dashboard.php");
                            exit; // CRÍTICO: Detener el script después de la redirección.
                        } else {
                            $error_msg = "La contraseña ingresada no es válida.";
                        }
                    }
                } else {
                    $error_msg = "No se encontró una cuenta con ese nombre de usuario.";
                }
            } else {
                $error_msg = "Oops! Algo salió mal. Por favor, intente de nuevo más tarde.";
            }
            $stmt->close();
        }
    }
    }
    // Solo cerramos la conexión si nos quedamos en la página de login (es decir, si hubo un error)
    // Ya no es necesario cerrar la conexión manualmente. PHP lo hará al final.
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
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
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

    <?php include 'footer.php'; // Incluimos el pie de página centralizado ?>
</body>
</html>