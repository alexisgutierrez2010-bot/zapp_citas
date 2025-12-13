<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Aplicada la internacionalización (i18n).
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
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('users_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: <?php echo $daily_bg_color; ?>;">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h3><?php echo __('users_register_new'); ?></h3></div>
                    <div class="card-body">
                        <?php
                        if (isset($_GET['status']) || isset($_GET['message_key'])) {
                            $status = $_GET['status'] ?? '';
                            $message_key = $_GET['message_key'] ?? '';
                            $message = '';

                            if (!empty($message_key)) {
                                $message = __($message_key);
                            } elseif ($status === 'success_create') {
                                $message = __('create_success');
                            } elseif ($status === 'success_deactivate') {
                                $message = __('deactivate_success');
                            }
                            if (!empty($message)) {
                                $alert_type = strpos($status, 'error') === false ? 'success' : 'danger';
                                echo "<div class='alert alert-{$alert_type}'>" . htmlspecialchars($message) . "</div>";
                            }
                        }
                        ?>
                        <form action="usuarios_crear.php" method="POST">
                            <div class="mb-3">
                                <label for="nombre_usuario" class="form-label"><?php echo __('users_form_username'); ?></label>
                                <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" required>
                            </div>
                            <div class="mb-3">
                                <label for="correo_electronico" class="form-label"><?php echo __('users_form_email'); ?></label>
                                <input type="email" class="form-control" id="correo_electronico" name="correo_electronico" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label"><?php echo __('users_form_password'); ?></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="rol" class="form-label"><?php echo __('users_form_role'); ?></label>
                                <select class="form-select" id="rol" name="rol" required>
                                    <option value="Propietario" selected><?php echo __('users_role_owner'); ?></option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="id_negocio" class="form-label"><?php echo __('users_form_business'); ?></label>
                                <select class="form-select" id="id_negocio" name="id_negocio" required>
                                    <?php foreach ($configs_list as $config_item): ?>
                                        <option value="<?php echo $config_item['id_negocio']; ?>"><?php echo htmlspecialchars($config_item['nombre_negocio']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="activo_crear" name="activo" value="1" checked>
                                <label class="form-check-label" for="activo_crear"><?php echo __('users_form_active'); ?></label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mt-2"><?php echo __('users_form_save'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <h3><?php echo __('users_list_title'); ?></h3>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th><?php echo __('users_col_user'); ?></th>
                                <th><?php echo __('clients_form_email'); ?></th>
                                <th><?php echo __('users_form_role'); ?></th>
                                <th><?php echo __('users_col_business'); ?></th>
                                <th><?php echo __('status'); ?></th>
                                <th><?php echo __('actions'); ?></th>
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
                                    $estado_usuario = $row['activo'] ? '<span class="badge bg-success">' . __('active') . '</span>' : '<span class="badge bg-danger">' . __('inactive') . '</span>';
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
                                        echo '<a href="usuarios_editar.php?id=' . $row['id_usuario'] . '&lang=' . $lang . '" class="btn btn-sm btn-warning">' . __('edit') . '</a>
                                              <form action="usuarios_eliminar.php" method="POST" style="display:inline-block;" onsubmit="return confirm(\'' . __('users_confirm_deactivate') . '\');">
                                                  <input type="hidden" name="id_usuario" value="' . $row['id_usuario'] . '">
                                                  <button type="submit" class="btn btn-sm btn-danger">' . __('deactivate') . '</button>
                                              </form>';
                                    } else {
                                        echo '<span class="text-muted fst-italic"> ' . __('users_not_allowed') . ' </span>';
                                    }
                                    echo '</td>';
                                    echo "</tr>";
                                }
                            }
                            $stmt->close();
                            ?>
                            <?php if ($result->num_rows === 0): ?>
                                <tr><td colspan="7" class="text-center"><?php echo __('users_no_users'); ?></td></tr>
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