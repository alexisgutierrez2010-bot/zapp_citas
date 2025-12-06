<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
require_once 'auth_check.php';
require_once 'config.php';

/*
// Solo el rol Master puede acceder a esta página
if ($rol_session != 'Master') {
    header("Location: dashboard.php?status=error&message=" . urlencode("No tienes permiso para acceder a esta sección."));
    exit;
}
*/

// Obtener lista de negocios si el usuario es Master
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
    <title>Gestión de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h3>Registrar Nuevo Usuario</h3></div>
                    <div class="card-body">
                        <?php
                        if (isset($_GET['status'])) {
                            $status_type = strpos($_GET['status'], 'success') !== false ? 'success' : 'danger';
                            $message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : ($status_type == 'success' ? 'Acción completada con éxito.' : 'Ocurrió un error.');
                            echo "<div class='alert alert-{$status_type}'>{$message}</div>";
                        }
                        ?>
                        <form action="usuarios_crear.php" method="POST">
                            <div class="mb-3">
                                <label for="nombre_usuario" class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" required>
                            </div>
                            <div class="mb-3">
                                <label for="correo_electronico" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="correo_electronico" name="correo_electronico" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="rol" class="form-label">Rol</label>
                                <select class="form-select" id="rol" name="rol" required>
                                    <option value="Propietario" selected>Propietario</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="id_negocio" class="form-label">Negocio Asignado</label>
                                <select class="form-select" id="id_negocio" name="id_negocio" required>
                                    <?php foreach ($configs_list as $config_item): ?>
                                        <option value="<?php echo $config_item['id_negocio']; ?>"><?php echo htmlspecialchars($config_item['nombre_negocio']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="activo_crear" name="activo" value="1" checked>
                                <label class="form-check-label" for="activo_crear">Usuario Activo</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mt-2">Guardar Usuario</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <h3>Lista de Usuarios</h3>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Negocio</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT u.id_usuario, u.nombre_usuario, u.correo_electronico, u.rol, u.activo, n.nombre_negocio 
                                    FROM j100_usuarios u
                                    JOIN j102_negocios n ON u.id_negocio = n.id_negocio";
                            $sql .= " ORDER BY n.nombre_negocio, u.nombre_usuario";
                            $stmt = $conn->prepare($sql);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            $i = 1;
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $i++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row["nombre_usuario"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["correo_electronico"]) . "</td>";
                                    echo "<td><span class='badge bg-secondary'>" . htmlspecialchars($row["rol"]) . "</span></td>";
                                    echo "<td>" . htmlspecialchars($row["nombre_negocio"]) . "</td>";
                                    $estado_usuario = $row['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>';
                                    echo "<td>" . $estado_usuario . "</td>";
                                    echo '<td>';
                                    
                                    // Lógica para mostrar los botones de acción
                                    $puede_actuar = false;
                                    // Un usuario no puede actuar sobre sí mismo
                                    if ($_SESSION['id_usuario'] != $row['id_usuario']) {
                                        // Temporalmente, todos pueden actuar sobre todos (excepto sobre sí mismos)
                                        $puede_actuar = true;
                                    }

                                    if ($puede_actuar) {
                                        echo '<a href="usuarios_editar.php?id=' . $row['id_usuario'] . '" class="btn btn-sm btn-warning">Editar</a>
                                              <form action="usuarios_eliminar.php" method="POST" style="display:inline-block;" onsubmit="return confirm(\'¿Estás seguro de que quieres eliminar este usuario?\');">
                                                  <input type="hidden" name="id_usuario" value="' . $row['id_usuario'] . '">
                                                  <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                              </form>';
                                    } else {
                                        echo '<span class="text-muted fst-italic"> (No permitido) </span>';
                                    }
                                    echo '</td>';
                                    echo "</tr>";
                                }
                            }
                            $stmt->close();
                            ?>
                            <?php if ($result->num_rows === 0): ?>
                                <tr><td colspan="7" class="text-center">No hay usuarios registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>