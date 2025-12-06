<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Aplicada la internacionalización (i18n).
require_once 'auth_check.php';
require_once 'config.php';

// Si el usuario es Master, obtenemos todas las configuraciones.
$all_configs = [];
$all_configs_result = $conn->query("SELECT id_negocio, nombre_negocio FROM j102_negocios ORDER BY nombre_negocio ASC");
while ($row = $all_configs_result->fetch_assoc()) {
    $all_configs[] = $row;
}

// Determinar qué configuración editar. Si es Master y se pasa un id, se edita esa. Si no, la de la sesión.
// Simplificado: Si se pasa un ID en la URL, se usa ese. Si no, se usa el de la sesión.
$id_negocio_a_editar = isset($_GET['id']) ? (int)$_GET['id'] : $id_negocio_session;

// Obtener la configuración del negocio a editar para el formulario
$stmt_config = $conn->prepare("SELECT * FROM j102_negocios WHERE id_negocio = ?");
$stmt_config->bind_param("i", $id_negocio_a_editar);
$stmt_config->execute();
$result_config = $stmt_config->get_result();
$config = $result_config->fetch_assoc();
$stmt_config->close();

// Obtener la lista de países para los menús desplegables
$paises = [];
$paises_result = $conn->query("SELECT id_pais, nombre_pais, codigo_telefono FROM j110_paises ORDER BY nombre_pais ASC");
while ($row = $paises_result->fetch_assoc()) {
    $paises[] = $row;
}

// Obtener la lista de categorías para el menú desplegable
$categorias = [];
$categorias_result = $conn->query("SELECT id_categoria, nombre_categoria FROM j103_categorias WHERE activo = 1 ORDER BY nombre_categoria ASC");
while ($row = $categorias_result->fetch_assoc()) {
    $categorias[] = $row;
}

// Definir las variables para el formulario
$dias_trabajo_activos = isset($config['dias_trabajo']) ? explode(',', $config['dias_trabajo']) : [];
$dias_semana = [
    '1' => 'Lunes',
    '2' => 'Martes',
    '3' => 'Miércoles',
    '4' => 'Jueves',
    '5' => 'Viernes',
    '6' => 'Sábado',
    '7' => 'Domingo'
];

// Lógica para pre-seleccionar el código de país y el número local
$telefono_negocio_full = $config['telefono'] ?? '';
$telefono_local_part = '';
$selected_country_code = '';

foreach ($paises as $pais_option) {
    // Usamos substr para compatibilidad con PHP < 8.0 en lugar de str_starts_with()
    if (!empty($pais_option['codigo_telefono']) && substr($telefono_negocio_full, 0, strlen($pais_option['codigo_telefono'])) === $pais_option['codigo_telefono']) {
        $selected_country_code = $pais_option['codigo_telefono'];
        $telefono_local_part = trim(substr($telefono_negocio_full, strlen($pais_option['codigo_telefono'])));
        break;
    }
}
// Si no se encontró un código de país, el número completo se considera local y se selecciona el primer país por defecto
if (empty($selected_country_code) && !empty($paises)) {
    $selected_country_code = $paises[0]['codigo_telefono'];
    $telefono_local_part = $telefono_negocio_full;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('businesses_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <?php // if ($rol_session == 'Master'): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h4><?php echo __('businesses_manage_title'); ?></h4>
            </div>
            <div class="card-body">
                <p><?php echo __('businesses_select_or_create'); ?></p>
                <div class="list-group">
                    <?php
                    $estados_negocio = [
                        1 => ['texto' => __('active'), 'clase' => 'success'],
                        2 => ['texto' => __('businesses_status_suspended'), 'clase' => 'warning'],
                        3 => ['texto' => __('businesses_status_deleted'), 'clase' => 'danger']
                    ];
                    $i = 1;
                    foreach ($all_configs as $cfg): 
                    ?>
                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo ($cfg['id_negocio'] == $id_negocio_a_editar) ? 'active' : ''; ?>">
                            <span><?php echo $i++; ?>. <?php echo htmlspecialchars($cfg['nombre_negocio']); ?></span>
                            <div>
                                <a href="negocios_configuracion.php?id=<?php echo $cfg['id_negocio']; ?>&lang=<?php echo $lang; ?>" class="btn btn-sm btn-outline-light"><?php echo __('edit'); ?></a>
                                <?php if ($cfg['id_negocio'] == $id_negocio_a_editar && isset($config['activo'])): ?>
                                    <span class="badge bg-<?php echo $estados_negocio[$config['activo']]['clase']; ?> ms-2"><?php echo $estados_negocio[$config['activo']]['texto']; ?></span>
                                <?php endif; ?>
                                <form action="negocios_eliminar.php" method="POST" class="d-inline" onsubmit="return confirm('¡ADVERTENCIA! Eliminar este negocio borrará TODOS sus usuarios, clientes y citas. ¿Está absolutamente seguro?');">
                                    <input type="hidden" name="id_negocio" value="<?php echo $cfg['id_negocio']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><?php echo __('delete'); ?></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-grid mt-3">
                    <a href="crear_negocio.php?lang=<?php echo $lang; ?>" class="btn btn-success"><?php echo __('businesses_create_new'); ?></a>
                </div>
            </div>
        </div>
        <?php // endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo __('businesses_config_for'); ?>: <?php echo htmlspecialchars($config['nombre_negocio'] ?? 'Nuevo Negocio'); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php
                        if (isset($_GET['status']) || isset($_GET['message_key'])) {
                            $status = $_GET['status'] ?? '';
                            $message_key = $_GET['message_key'] ?? '';
                            $message = '';

                            if (!empty($message_key)) {
                                $message = __($message_key);
                            } elseif ($status === 'success') {
                                $message = __('update_success');
                            }
                            if (!empty($message)) {
                                $alert_type = strpos($status, 'error') === false ? 'success' : 'danger';
                                echo "<div class='alert alert-{$alert_type}'>" . htmlspecialchars($message) . "</div>";
                            }
                        }
                        ?>
                        <form action="negocios_actualizar.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="id_negocio" class="form-label"><?php echo __('businesses_form_id'); ?></label>
                                <input type="text" class="form-control" id="id_negocio" name="id_negocio" value="<?php echo htmlspecialchars($id_negocio_a_editar); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="nombre_negocio" class="form-label"><?php echo __('businesses_form_name'); ?></label>
                                <input type="text" class="form-control" id="nombre_negocio" name="nombre_negocio" value="<?php echo htmlspecialchars($config['nombre_negocio'] ?? ''); ?>">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="telefono" class="form-label"><?php echo __('businesses_form_phone'); ?></label>
                                    <div class="input-group">
                                        <select class="form-select" id="country_code" name="country_code" style="max-width: 150px;" required>
                                            <?php foreach ($paises as $pais_option): ?>
                                                <option value="<?php echo htmlspecialchars($pais_option['codigo_telefono']); ?>" <?php echo ($selected_country_code == $pais_option['codigo_telefono']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($pais_option['nombre_pais'] . ' (' . $pais_option['codigo_telefono'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="tel" class="form-control" id="telefono_local" name="telefono_local" value="<?php echo htmlspecialchars($telefono_local_part); ?>" placeholder="<?php echo __('businesses_form_phone_local'); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label"><?php echo __('businesses_form_email'); ?></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($config['email'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="id_categoria_negocio" class="form-label"><?php echo __('businesses_form_category'); ?></label>
                                <select class="form-select" id="id_categoria_negocio" name="id_categoria_negocio">
                                    <option value=""><?php echo __('businesses_form_no_category'); ?></option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id_categoria']; ?>" <?php echo (isset($config['id_categoria_negocio']) && $config['id_categoria_negocio'] == $categoria['id_categoria']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['nombre_categoria']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="activo" class="form-label"><?php echo __('businesses_form_status'); ?></label>
                                <select class="form-select" id="activo" name="activo">
                                    <option value="1" <?php echo (isset($config['activo']) && $config['activo'] == 1) ? 'selected' : ''; ?>><?php echo __('active'); ?></option>
                                    <option value="2" <?php echo (isset($config['activo']) && $config['activo'] == 2) ? 'selected' : ''; ?>><?php echo __('businesses_status_suspended'); ?></option>
                                    <?php if (isset($config['activo']) && $config['activo'] == 3): ?>
                                    <option value="3" selected><?php echo __('businesses_status_deleted'); ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="direccion1" class="form-label"><?php echo __('businesses_form_address1'); ?></label>
                                <input type="text" class="form-control" id="direccion1" name="direccion1" value="<?php echo htmlspecialchars($config['direccion1'] ?? ''); ?>" maxlength="128">
                            </div>
                            <div class="mb-3">
                                <label for="direccion2" class="form-label"><?php echo __('businesses_form_address2'); ?></label>
                                <input type="text" class="form-control" id="direccion2" name="direccion2" value="<?php echo htmlspecialchars($config['direccion2'] ?? ''); ?>" maxlength="128">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="id_pais" class="form-label"><?php echo __('businesses_form_country'); ?></label>
                                    <select class="form-select" id="id_pais" name="id_pais" required>
                                        <option value=""><?php echo __('businesses_form_select_country'); ?></option>
                                        <?php foreach ($paises as $pais): ?>
                                            <option value="<?php echo $pais['id_pais']; ?>" <?php echo (isset($config['id_pais']) && $config['id_pais'] == $pais['id_pais']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($pais['nombre_pais']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="id_estado" class="form-label"><?php echo __('businesses_form_state'); ?></label>
                                    <select class="form-select" id="id_estado" name="id_estado" required disabled>
                                        <option value=""><?php echo __('businesses_form_loading'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="ciudad" class="form-label"><?php echo __('businesses_form_city'); ?></label>
                                    <input type="text" class="form-control" id="ciudad" name="ciudad" value="<?php echo htmlspecialchars($config['ciudad'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="zip_code" class="form-label"><?php echo __('businesses_form_zip'); ?></label>
                                    <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($config['zip_code'] ?? ''); ?>">
                                </div>
                            </div>

                            <hr>
                            <h5 class="mt-4"><?php echo __('businesses_schedule_title'); ?></h5>

                            <div class="mb-3">
                                <label class="form-label"><?php echo __('businesses_schedule_days'); ?></label>
                                <div>
                                    <?php foreach ($dias_semana as $num => $dia): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="dias_trabajo[]" value="<?php echo $num; ?>" id="dia_<?php echo $num; ?>" <?php echo in_array((string)$num, $dias_trabajo_activos, true) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="dia_<?php echo $num; ?>"><?php echo __("day_{$num}"); ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="hora_inicio" class="form-label"><?php echo __('businesses_schedule_start'); ?></label>
                                    <input type="time" class="form-control" id="hora_inicio" name="hora_inicio" value="<?php echo htmlspecialchars($config['hora_inicio'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="hora_cierre" class="form-label"><?php echo __('businesses_schedule_end'); ?></label>
                                    <input type="time" class="form-control" id="hora_cierre" name="hora_cierre" value="<?php echo htmlspecialchars($config['hora_cierre'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="intervalo_minutos" class="form-label"><?php echo __('businesses_schedule_interval'); ?></label>
                                    <input type="number" class="form-control" id="intervalo_minutos" name="intervalo_minutos" value="<?php echo htmlspecialchars($config['intervalo_minutos'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <hr>
                            <h5 class="mt-4"><?php echo __('businesses_trial_title'); ?></h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="fecha_registro" class="form-label"><?php echo __('businesses_trial_reg_date'); ?></label>
                                    <input type="datetime-local" class="form-control" id="fecha_registro" name="fecha_registro" value="<?php echo !empty($config['fecha_registro']) ? (new DateTime($config['fecha_registro']))->format('Y-m-d\TH:i') : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="fecha_habilitacion" class="form-label"><?php echo __('businesses_trial_start_date'); ?></label>
                                    <input type="datetime-local" class="form-control" id="fecha_habilitacion" name="fecha_habilitacion" value="<?php echo !empty($config['fecha_habilitacion']) ? (new DateTime($config['fecha_habilitacion']))->format('Y-m-d\TH:i') : ''; ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="dias_prueba" class="form-label"><?php echo __('businesses_trial_days'); ?></label>
                                    <input type="number" class="form-control" id="dias_prueba" name="dias_prueba" value="<?php echo htmlspecialchars($config['dias_prueba'] ?? '30'); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="fecha_desactivacion" class="form-label"><?php echo __('businesses_trial_end_date'); ?></label>
                                    <input type="datetime-local" class="form-control" id="fecha_desactivacion" name="fecha_desactivacion" value="<?php echo !empty($config['fecha_desactivacion']) ? (new DateTime($config['fecha_desactivacion']))->format('Y-m-d\TH:i') : ''; ?>">
                                    <small class="form-text text-muted"><?php echo __('businesses_trial_note'); ?></small>
                                </div>
                            </div>
                            <?php
                                if (!empty($config['fecha_desactivacion'])) {
                                    $hoy = new DateTime();
                                    $fin = new DateTime($config['fecha_desactivacion']);
                                    $diferencia = $hoy->diff($fin);
                                    $dias_restantes = $diferencia->format('%r%a');
                                    $clase_alerta = $dias_restantes > 7 ? 'info' : ($dias_restantes > 0 ? 'warning' : 'danger');
                                    echo "<div class='alert alert-{$clase_alerta}'>Quedan <strong>{$dias_restantes} días</strong> de prueba.</div>";
                                }
                            ?>

                            <hr>
                            <h5 class="mt-4"><?php echo __('businesses_bg_image_title'); ?></h5>
                            <div class="mb-3">
                                <label for="background_image" class="form-label"><?php echo __('businesses_bg_image_upload'); ?></label>
                                <input class="form-control" type="file" id="background_image" name="background_image" accept="image/jpeg, image/png">
                            </div>
                            <?php if (!empty($config['background_image_data'])): ?>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('businesses_bg_image_current'); ?></label><br>
                                    <img src="get_image.php" alt="Imagen de fondo actual" class="img-fluid rounded" style="max-height: 150px;">
                                </div>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-primary w-100 mt-3"><?php echo __('businesses_form_save'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const paisSelect = document.getElementById('id_pais');
        const estadoSelect = document.getElementById('id_estado');
        const idEstadoGuardado = <?php echo json_encode($config['id_estado'] ?? null); ?>;

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
            cargarEstados(this.value);
        });

        // Carga inicial de estados para el país ya seleccionado
        cargarEstados(paisSelect.value, idEstadoGuardado);

        // --- LÓGICA PARA RECALCULAR FECHA DE DESACTIVACIÓN ---
        const fechaHabilitacionInput = document.getElementById('fecha_habilitacion');
        const diasPruebaInput = document.getElementById('dias_prueba');
        const fechaDesactivacionInput = document.getElementById('fecha_desactivacion');

        function recalcularFechaDesactivacion() {
            const fechaHabilitacion = fechaHabilitacionInput.value;
            const diasPrueba = parseInt(diasPruebaInput.value, 10);

            if (fechaHabilitacion && !isNaN(diasPrueba) && diasPrueba >= 0) {
                const fechaInicio = new Date(fechaHabilitacion);
                fechaInicio.setDate(fechaInicio.getDate() + diasPrueba);

                // Formatear la fecha para el input datetime-local (YYYY-MM-DDTHH:mm)
                const anio = fechaInicio.getFullYear();
                const mes = String(fechaInicio.getMonth() + 1).padStart(2, '0');
                const dia = String(fechaInicio.getDate()).padStart(2, '0');
                const fechaFormateada = `${anio}-${mes}-${dia}T${fechaHabilitacion.split('T')[1] || '00:00'}`;
                
                fechaDesactivacionInput.value = fechaFormateada;
            }
        }
        diasPruebaInput.addEventListener('input', recalcularFechaDesactivacion);
    });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>
