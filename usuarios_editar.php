<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Aplicada la internacionalización (i18n).
require_once 'auth_check.php';
require_once 'config.php';

// Solo el rol Administrador puede editar usuarios desde este panel.
if (strcasecmp(trim($rol_session ?? ''), 'Administrador') != 0) {
    header("Location: dashboard.php?status=error&message=" . urlencode("Acceso no autorizado."));
    exit;
}
$id_usuario_editar = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_usuario_editar <= 0) {
    header("Location: usuarios_lista.php?status=error&message=" . urlencode("ID de usuario no válido."));
    exit;
}

// Obtener datos del usuario a editar
$stmt_user = $conn->prepare("SELECT * FROM j100_usuarios WHERE id_usuario = ?");
$stmt_user->bind_param("i", $id_usuario_editar);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user->num_rows !== 1) {
    header("Location: usuarios_lista.php?status=error&message=" . urlencode("Usuario no encontrado."));
    exit;
}
$usuario = $result_user->fetch_assoc();
$stmt_user->close();

// Obtener lista de negocios si el usuario es Administrador
$configs_list = [];
$configs_result = $conn->query("SELECT id_negocio, nombre_negocio FROM j102_negocios WHERE activo = 1 ORDER BY nombre_negocio");
while ($row = $configs_result->fetch_assoc()) {
    $configs_list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h3>Editando a <?php echo htmlspecialchars($usuario['nombre_usuario']); ?></h3></div>
                    <div class="card-body">
                        <?php
                        if (isset($_GET['message_key'])) {
                            $message = htmlspecialchars($_GET['message_key']);
                            echo "<div class='alert alert-danger'>" . htmlspecialchars($message) . "</div>";
                        } elseif (isset($_GET['status']) && $_GET['status'] == 'error') {
                            // Fallback para mensajes antiguos
                            $errorMessage = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Error en la operación.';
                            echo '<div class="alert alert-danger">' . $errorMessage . '</div>';
                        }
                        ?>
                        <form action="usuarios_actualizar.php" method="POST">
                            <input type="hidden" name="id_usuario" value="<?php echo $usuario['id_usuario']; ?>">
                            <div class="mb-3">
                                <label for="nombre_usuario" class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" value="<?php echo htmlspecialchars($usuario['nombre_usuario']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="correo_electronico" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="correo_electronico" name="correo_electronico" value="<?php echo htmlspecialchars($usuario['correo_electronico']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Nueva Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Dejar en blanco para no cambiar">
                            </div>
                            <div class="mb-3">
                                <label for="rol" class="form-label">Rol</label>
                                <select class="form-select" id="rol" name="rol" required>
                                    <option value="Propietario" <?php echo ($usuario['rol'] == 'Propietario') ? 'selected' : ''; ?>>Propietario</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="id_negocio" class="form-label">Negocio Asignado</label>
                                <select class="form-select" id="id_negocio" name="id_negocio" required>
                                    <?php foreach ($configs_list as $config_item): ?>
                                        <option value="<?php echo $config_item['id_negocio']; ?>" <?php echo ($usuario['id_negocio'] == $config_item['id_negocio']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($config_item['nombre_negocio']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" <?php echo ($usuario['activo'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="activo">Usuario Activo</label>
                            </div>
                            <div class="mb-3">
                                <label for="fecha_registro" class="form-label">Fecha de Registro</label>
                                <input type="text" class="form-control" id="fecha_registro" value="<?php echo !empty($usuario['fecha_registro']) ? date('d/m/Y H:i', strtotime($usuario['fecha_registro'])) : 'No registrada'; ?>" readonly>
                            </div>
                            <button type="submit" class="btn btn-success w-100 mt-2">Actualizar Usuario</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>