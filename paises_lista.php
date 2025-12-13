<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Aplicada la internacionalización (i18n).
require_once 'auth_check.php';
require_once 'config.php';

// Obtener la lista de zonas horarias para el menú desplegable
$timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

// Obtener todos los países para la tabla
$paises_result = $conn->query("SELECT * FROM j110_paises ORDER BY nombre_pais ASC");
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('locations_countries_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: <?php echo $daily_bg_color; ?>;">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Formulario para agregar país -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h3><?php echo __('locations_register_country'); ?></h3></div>
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
                            }
                            if (!empty($message)) {
                                $alert_type = strpos($status, 'error') === false ? 'success' : 'danger';
                                echo "<div class='alert alert-{$alert_type}'>" . htmlspecialchars($message) . "</div>";
                            }
                        }
                        ?>
                        <form action="paises_crear.php" method="POST">
                            <div class="mb-3">
                                <label for="nombre_pais" class="form-label"><?php echo __('locations_form_country_name'); ?></label>
                                <input type="text" class="form-control" id="nombre_pais" name="nombre_pais" required>
                            </div>
                            <div class="mb-3">
                                <label for="codigo_pais" class="form-label"><?php echo __('locations_form_country_code'); ?></label>
                                <input type="text" class="form-control" id="codigo_pais" name="codigo_pais" maxlength="2" required>
                            </div>
                            <div class="mb-3">
                                <label for="codigo_telefono" class="form-label"><?php echo __('locations_form_phone_code'); ?></label>
                                <input type="text" class="form-control" id="codigo_telefono" name="codigo_telefono" required>
                            </div>
                            <div class="mb-3">
                                <label for="timezone" class="form-label"><?php echo __('locations_form_timezone'); ?></label>
                                <select class="form-select" id="timezone" name="timezone" required>
                                    <option value=""><?php echo __('locations_form_select_timezone'); ?></option>
                                    <?php foreach ($timezones as $tz): ?>
                                        <option value="<?php echo $tz; ?>"><?php echo htmlspecialchars($tz); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><?php echo __('locations_form_save_country'); ?></button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lista de países -->
            <div class="col-md-8">
                <h3><?php echo __('locations_list_countries'); ?></h3>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th><?php echo __('locations_col_country'); ?></th>
                                <th><?php echo __('locations_col_code'); ?></th>
                                <th><?php echo __('locations_col_phone'); ?></th>
                                <th><?php echo __('locations_col_timezone'); ?></th>
                                <th><?php echo __('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 1;
                            if ($paises_result->num_rows > 0):
                                while($pais = $paises_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo htmlspecialchars($pais['nombre_pais']); ?></td>
                                        <td><?php echo htmlspecialchars($pais['codigo_pais']); ?></td>
                                        <td><?php echo htmlspecialchars($pais['codigo_telefono']); ?></td>
                                        <td><?php echo htmlspecialchars($pais['timezone']); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="estados_lista.php?id_pais=<?php echo $pais['id_pais']; ?>&lang=<?php echo $lang; ?>" class="btn btn-sm btn-info"><?php echo __('locations_action_states'); ?></a>
                                                <a href="paises_editar.php?id=<?php echo $pais['id_pais']; ?>&lang=<?php echo $lang; ?>" class="btn btn-sm btn-warning"><?php echo __('edit'); ?></a>
                                                <form action="paises_eliminar.php" method="POST" class="d-inline" onsubmit="return confirm('<?php echo __('locations_confirm_delete_country'); ?>');">
                                                    <input type="hidden" name="id_pais" value="<?php echo $pais['id_pais']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger"><?php echo __('delete'); ?></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr><td colspan="6" class="text-center"><?php echo __('locations_no_countries'); ?></td></tr>
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