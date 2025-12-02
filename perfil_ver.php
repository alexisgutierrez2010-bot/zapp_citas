<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
require_once 'auth_check.php';

require_once 'config.php';
// Obtener datos del usuario actual para mostrarlos
$stmt_user = $conn->prepare("SELECT nombre_usuario, correo_electronico FROM j100_usuarios WHERE id_usuario = ?");
$stmt_user->bind_param("i", $_SESSION['id_usuario']);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$usuario = $result_user->fetch_assoc();
$stmt_user->close();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3>Mi Perfil</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        if (isset($_GET['status'])) {
                            $status_type = $_GET['status'] == 'success' ? 'success' : 'danger';
                            $message = htmlspecialchars($_GET['message']);
                            echo "<div class='alert alert-{$status_type}'>{$message}</div>";
                        }
                        ?>
                        <div class="mb-3">
                            <label class="form-label">Nombre de Usuario</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario['nombre_usuario']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($usuario['correo_electronico']); ?>" readonly>
                        </div>

                        <hr>

                        <h4 class="mt-4">Cambiar Contraseña</h4>
                        <form action="perfil_actualizar.php" method="POST">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Contraseña Actual</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nueva Contraseña</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Actualizar Contraseña</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>