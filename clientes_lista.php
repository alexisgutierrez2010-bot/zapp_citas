<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Aplicada la internacionalización (i18n).
require_once 'auth_check.php';
require_once 'config.php'; // Incluimos la conexión aquí para usarla más adelante

// Obtener la lista de países para los menús desplegables
$paises = [];
$paises_result = $conn->query("SELECT id_pais, nombre_pais, codigo_telefono FROM j110_paises ORDER BY nombre_pais ASC");
while ($row = $paises_result->fetch_assoc()) {
    $paises[] = $row;
}

// Obtener el nombre del negocio actual para mostrarlo en la página
$nombre_negocio_actual = 'Negocio Desconocido';
$stmt_negocio = $conn->prepare("SELECT nombre_negocio FROM j102_negocios WHERE id_negocio = ?");
$stmt_negocio->bind_param("i", $id_negocio_session);
$stmt_negocio->execute();
$result_negocio = $stmt_negocio->get_result()->fetch_assoc();
$nombre_negocio_actual = $result_negocio['nombre_negocio'] ?? $nombre_negocio_actual;
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('clients_title'); ?></title>
    <!-- Usaremos Bootstrap para un diseño limpio y rápido -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; // Incluimos el menú de navegación ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo __('clients_register_new'); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php
                        // Mostrar mensajes de éxito o error
                        if (isset($_GET['status']) || isset($_GET['message_key'])) {
                            $status = $_GET['status'] ?? '';
                            $message_key = $_GET['message_key'] ?? '';
                            $message = '';

                            if (!empty($message_key)) {
                                $message = __($message_key);
                            } elseif ($status === 'success_create') {
                                $message = __('create_success');
                            } elseif ($status === 'success_update') {
                                $message = __('update_success');
                            }
                            if (!empty($message)) {
                                $alert_type = strpos($status, 'error') === false ? 'success' : 'danger';
                                echo "<div class='alert alert-{$alert_type}'>" . htmlspecialchars($message) . "</div>";
                            }
                        }
                        ?>

                        <form action="clientes_crear.php" method="POST">
                            <div class="mb-3">
                                <label for="nombre" class="form-label"><?php echo __('clients_form_name'); ?></label>
                                <input type="text" class="form-control" id="nombre" name="nombre_completo" required>
                            </div>
                            <div class="mb-3">
                                <label for="numero_celular" class="form-label"><?php echo __('clients_form_phone'); ?></label>
                                <div class="input-group">
                                    <select class="form-select" id="country_code" name="country_code" style="max-width: 120px;">
                                        <?php foreach ($paises as $pais): ?>
                                            <option value="<?php echo htmlspecialchars($pais['codigo_telefono']); ?>" <?php echo ($pais['id_pais'] == 1) ? 'selected' : ''; ?>><?php echo htmlspecialchars($pais['codigo_telefono']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="tel" class="form-control" id="numero_celular" name="numero_celular" placeholder="Ej: 4121234567">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label"><?php echo __('clients_form_email'); ?></label>
                                <input type="email" class="form-control" id="email" name="correo_electronico" required>
                            </div>
                            <div class="mb-3">
                                <label for="direccion1" class="form-label"><?php echo __('clients_form_address1'); ?></label>
                                <input type="text" class="form-control" id="direccion1" name="direccion1" maxlength="128">
                            </div>
                            <div class="mb-3">
                                <label for="direccion2" class="form-label"><?php echo __('clients_form_address2'); ?></label>
                                <input type="text" class="form-control" id="direccion2" name="direccion2" maxlength="128">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="id_pais" class="form-label"><?php echo __('clients_form_country'); ?></label>
                                    <select class="form-select" id="id_pais" name="id_pais" required>
                                        <option value=""><?php echo __('businesses_form_select_country'); ?></option>
                                        <?php foreach ($paises as $pais): ?>
                                            <option value="<?php echo $pais['id_pais']; ?>" data-codigo-telefono="<?php echo htmlspecialchars($pais['codigo_telefono']); ?>" <?php echo ($pais['id_pais'] == 1) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($pais['nombre_pais']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="id_estado" class="form-label"><?php echo __('clients_form_state'); ?></label>
                                    <select class="form-select" id="id_estado" name="id_estado" required disabled>
                                        <option value=""><?php echo __('businesses_form_loading'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="ciudad" class="form-label"><?php echo __('clients_form_city'); ?></label>
                                    <input type="text" class="form-control" id="ciudad" name="ciudad">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="zip_code" class="form-label"><?php echo __('clients_form_zip'); ?></label>
                                    <input type="text" class="form-control" id="zip_code" name="zip_code">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="notas" class="form-label"><?php echo __('clients_form_notes'); ?></label>
                                <textarea class="form-control" id="notas" name="notas_adicionales" rows="3"></textarea>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <label class="form-label"><?php echo __('clients_form_communication'); ?></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="in_sms" value="1" id="in_sms_crear" checked>
                                    <label class="form-check-label" for="in_sms_crear"><?php echo __('clients_form_sms_notifications'); ?></label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="in_email" value="1" id="in_email_crear" checked>
                                    <label class="form-check-label" for="in_email_crear"><?php echo __('clients_form_email_notifications'); ?></label>
                                </div>
                            </div>
                            <div class="d-grid gap-2 d-sm-flex">
                                <button type="submit" class="btn btn-primary flex-grow-1"><?php echo __('clients_form_save'); ?></button>
                                <button type="reset" class="btn btn-secondary flex-grow-1"><?php echo __('clients_form_clear'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <h3><?php echo __('clients_list_title'); ?></h3>
                <h5 class="text-muted mb-3"><?php echo __('clients_list_for'); ?>: <?php echo htmlspecialchars($nombre_negocio_actual); ?></h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th><?php echo __('clients_col_name'); ?></th>
                                <th><?php echo __('clients_col_phone'); ?></th>
                                <th><?php echo __('clients_col_email'); ?></th>
                                <th>SMS</th>
                                <th>Email</th>
                                <th><?php echo __('clients_col_status'); ?></th>
                                <th><?php echo __('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT id_cliente, nombre_completo, numero_celular, correo_electronico, activo, IN_SMS, IN_EMAIL FROM j106_clientes WHERE id_negocio = ? ORDER BY activo DESC, nombre_completo ASC";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $id_negocio_session);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            $i = 1;
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $i++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row["nombre_completo"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["numero_celular"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["correo_electronico"]) . "</td>";
                                    $sms_status = $row['IN_SMS'] ? '<span class="badge bg-success">On</span>' : '<span class="badge bg-secondary">Off</span>';
                                    $email_status = $row['IN_EMAIL'] ? '<span class="badge bg-success">On</span>' : '<span class="badge bg-secondary">Off</span>';
                                    echo "<td>" . $sms_status . "</td>";
                                    echo "<td>" . $email_status . "</td>";
                                    $estado_cliente = $row['activo'] ? '<span class="badge bg-success">' . __('active') . '</span>' : '<span class="badge bg-danger">' . __('inactive') . '</span>';
                                    echo "<td>" . $estado_cliente . "</td>";
                                    echo '<td>
                                            <a href="clientes_editar.php?id=' . $row['id_cliente'] . '&lang=' . $lang . '" class="btn btn-sm btn-warning">' . __('edit') . '</a>';
                                    if ($row['activo']) { // Solo mostrar el botón de desactivar si el cliente está activo
                                        echo '<form action="clientes_eliminar.php" method="POST" style="display:inline-block;" onsubmit="return confirm(\'' . __('clients_confirm_deactivate') . '\');">
                                                <input type="hidden" name="id_cliente" value="' . $row['id_cliente'] . '">
                                                <button type="submit" class="btn btn-sm btn-danger">' . __('deactivate') . '</button>
                                            </form>';
                                    }
                                    echo '</td>';
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center'>" . __('clients_no_clients') . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const paisSelect = document.getElementById('id_pais');
        const estadoSelect = document.getElementById('id_estado');
        const codigoTelefonoSelect = document.getElementById('country_code');

        function cargarEstados(idPais, idEstadoSeleccionado = null) {
            if (!idPais) {
                estadoSelect.innerHTML = '<option value="">Seleccione un país primero</option>';
                estadoSelect.disabled = true;
                return;
            }

            fetch(`api_estados.php?id_pais=${idPais}`)
                .then(response => response.json())
                .then(data => {
                    estadoSelect.innerHTML = '<option value="">Seleccione un estado...</option>';
                    data.forEach(estado => {
                        const option = document.createElement('option');
                        option.value = estado.id_estado;
                        option.textContent = estado.nombre_estado;
                        if (idEstadoSeleccionado && estado.id_estado == idEstadoSeleccionado) {
                            option.selected = true;
                        }
                        estadoSelect.appendChild(option);
                    });
                    estadoSelect.disabled = false;
                });
        }

        paisSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            codigoTelefonoSelect.value = selectedOption.getAttribute('data-codigo-telefono');
            cargarEstados(this.value);
        });

        // Carga inicial de estados si ya hay un país seleccionado (para formularios de edición)
        if (paisSelect.value) {
            cargarEstados(paisSelect.value);
        }
    });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>