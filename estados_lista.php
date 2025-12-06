<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Aplicada la internacionalización (i18n).
require_once 'auth_check.php';
require_once 'config.php';

$id_pais = isset($_GET['id_pais']) ? (int)$_GET['id_pais'] : 0;
if ($id_pais <= 0) {
    header("Location: paises_lista.php?status=error&message=" . urlencode("País no especificado."));
    exit();
}

// Obtener nombre del país para mostrarlo
$stmt_pais = $conn->prepare("SELECT nombre_pais FROM j110_paises WHERE id_pais = ?");
$stmt_pais->bind_param("i", $id_pais);
$stmt_pais->execute();
$pais = $stmt_pais->get_result()->fetch_assoc();
$stmt_pais->close();
if (!$pais) {
    header("Location: paises_lista.php?status=error&message=" . urlencode("País no encontrado."));
    exit();
}

$estados_result = $conn->query("SELECT * FROM j111_estados WHERE id_pais = $id_pais ORDER BY nombre_estado ASC");
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo str_replace('{country}', htmlspecialchars($pais['nombre_pais']), __('locations_states_title')); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <a href="paises_lista.php?lang=<?php echo $lang; ?>" class="btn btn-secondary mb-3"><?php echo __('locations_back_to_countries'); ?></a>
        <h2><?php echo str_replace('{country}', '<strong>' . htmlspecialchars($pais['nombre_pais']) . '</strong>', __('locations_states_title')); ?></h2>

        <div class="row mt-4">
            <!-- Formulario para agregar estado -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h4><?php echo __('locations_register_state'); ?></h4></div>
                    <div class="card-body">
                        <?php
                        if (isset($_GET['status']) || isset($_GET['message_key'])) {
                            $status = $_GET['status'] ?? '';
                            $message_key = $_GET['message_key'] ?? '';
                            $message = '';

                            if (!empty($message_key)) {
                                $message = __($message_key);
                            } elseif (strpos($status, 'success') !== false) {
                                $message = __('operation_success');
                            }
                            if (!empty($message)) {
                                $alert_type = strpos($status, 'error') === false ? 'success' : 'danger';
                                echo "<div class='alert alert-{$alert_type}'>" . htmlspecialchars($message) . "</div>";
                            }
                        }
                        ?>
                        <form action="estados_crear.php" method="POST">
                            <input type="hidden" name="id_pais" value="<?php echo $id_pais; ?>">
                            <div class="mb-3">
                                <label for="nombre_estado" class="form-label"><?php echo __('locations_form_state_name'); ?></label>
                                <input type="text" class="form-control" id="nombre_estado" name="nombre_estado" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><?php echo __('locations_form_save_state'); ?></button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lista de estados -->
            <div class="col-md-8">
                <h4><?php echo __('locations_list_states'); ?></h4>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th><?php echo __('locations_form_state_name'); ?></th>
                                <th><?php echo __('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 1;
                            if ($estados_result->num_rows > 0):
                                while($estado = $estados_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo htmlspecialchars($estado['nombre_estado']); ?></td>
                                        <td>
                                            <a href="estados_editar.php?id=<?php echo $estado['id_estado']; ?>&lang=<?php echo $lang; ?>" class="btn btn-sm btn-warning"><?php echo __('edit'); ?></a>
                                            <form action="estados_eliminar.php" method="POST" class="d-inline" onsubmit="return confirm('<?php echo __('locations_confirm_delete_state'); ?>');">
                                                <input type="hidden" name="id_estado" value="<?php echo $estado['id_estado']; ?>">
                                                <input type="hidden" name="id_pais" value="<?php echo $id_pais; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger"><?php echo __('delete'); ?></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr><td colspan="3" class="text-center"><?php echo __('locations_no_states'); ?></td></tr>
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