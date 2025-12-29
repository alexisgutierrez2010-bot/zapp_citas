<?php
require_once 'auth_check.php';
require_once 'config.php';

// Solo el rol Administrador puede resetear claves.
if (strcasecmp(trim($rol_session ?? ''), 'Administrador') != 0) {
    header("Location: dashboard.php?status=error&message=" . urlencode("Acceso no autorizado."));
    exit;
}

$id_usuario_reset = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_usuario_reset <= 0) {
    header("Location: usuarios_lista.php?status=error&message=" . urlencode("ID de usuario no válido."));
    exit;
}

// Obtener nombre del usuario para mostrarlo
$stmt_user = $conn->prepare("SELECT nombre_usuario FROM j100_usuarios WHERE id_usuario = ?");
$stmt_user->bind_param("i", $id_usuario_reset);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user->num_rows !== 1) {
    header("Location: usuarios_lista.php?status=error&message=" . urlencode("Usuario no encontrado."));
    exit;
}
$usuario = $result_user->fetch_assoc();
$stmt_user->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resetear Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h3>Resetear Contraseña para <?php echo htmlspecialchars($usuario['nombre_usuario']); ?></h3></div>
                    <div class="card-body">
                        <?php if (isset($_GET['message_key'])) { echo "<div class='alert alert-danger'>" . htmlspecialchars($_GET['message_key']) . "</div>"; } ?>
                        <form action="usuarios_procesar_reset_clave.php" method="POST">
                            <input type="hidden" name="id_usuario" value="<?php echo $id_usuario_reset; ?>">
                            <div class="mb-3"><label for="new_password" class="form-label">Nueva Contraseña</label><input type="password" class="form-control" id="new_password" name="new_password" required></div>
                            <div class="mb-3"><label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label><input type="password" class="form-control" id="confirm_password" name="confirm_password" required></div>
                            <div class="d-flex justify-content-between"><a href="usuarios_lista.php" class="btn btn-secondary">Cancelar</a><button type="submit" class="btn btn-primary">Establecer Nueva Contraseña</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>