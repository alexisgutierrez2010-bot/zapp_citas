<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
require_once 'auth_check.php';
require_once 'config.php';

$es_administrador = (isset($rol_session) && strcasecmp(trim($rol_session), 'Administrador') == 0);
if (!$es_administrador) {
    header("Location: dashboard.php?status=error&message=" . urlencode("Acceso no autorizado."));
    exit;
}

$id_negocio_a_editar = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_negocio_a_editar <= 0) {
    header("Location: negocios_configuracion.php?status=error&message=" . urlencode("ID de negocio no válido."));
    exit();
}

$stmt_config = $conn->prepare("SELECT * FROM j102_negocios WHERE id_negocio = ?");
$stmt_config->bind_param("i", $id_negocio_a_editar);
$stmt_config->execute();
$result_config = $stmt_config->get_result();
if ($result_config->num_rows !== 1) {
    header("Location: negocios_configuracion.php?status=error&message=" . urlencode("Negocio no encontrado."));
    exit();
}
$config = $result_config->fetch_assoc();
$stmt_config->close();

$paises = [];
$paises_result = $conn->query("SELECT id_pais, nombre_pais, codigo_telefono FROM j110_paises ORDER BY nombre_pais ASC");
while ($row = $paises_result->fetch_assoc()) {
    $paises[] = $row;
}

$categorias = [];
$categorias_result = $conn->query("SELECT id_categoria, nombre_categoria FROM j103_categorias WHERE activo = 1 ORDER BY nombre_categoria ASC");
while ($row = $categorias_result->fetch_assoc()) {
    $categorias[] = $row;
}

$dias_trabajo_activos = isset($config['dias_trabajo']) ? explode(',', $config['dias_trabajo']) : [];
$dias_semana = ['1' => 'Lunes', '2' => 'Martes', '3' => 'Miércoles', '4' => 'Jueves', '5' => 'Viernes', '6' => 'Sábado', '7' => 'Domingo'];

$telefono_negocio_full = $config['telefono'] ?? '';
$telefono_local_part = '';
$selected_country_code = '';

foreach ($paises as $pais_option) {
    // COMPATIBILITY FIX: Use substr() for PHP < 8.0 instead of str_starts_with()
    if (!empty($pais_option['codigo_telefono']) && substr($telefono_negocio_full, 0, strlen($pais_option['codigo_telefono'])) === $pais_option['codigo_telefono']) {
        $selected_country_code = $pais_option['codigo_telefono'];
        $telefono_local_part = trim(substr($telefono_negocio_full, strlen($pais_option['codigo_telefono'])));
        break;
    }
}
if (empty($selected_country_code) && !empty($paises)) {
    $selected_country_code = $paises[0]['codigo_telefono'];
    $telefono_local_part = $telefono_negocio_full;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Negocio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: <?php echo $daily_bg_color; ?>;">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><h3>Editando: <?php echo htmlspecialchars($config['nombre_negocio']); ?></h3></div>
                    <div class="card-body">
                        <?php if (isset($_GET['message'])): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars(urldecode($_GET['message'])); ?></div>
                        <?php endif; ?>
                        <form action="negocios_actualizar.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id_negocio" value="<?php echo htmlspecialchars($id_negocio_a_editar); ?>">
                            
                            <div class="mb-3"><label for="nombre_negocio" class="form-label">Nombre del Negocio</label>
                                <input type="text" class="form-control" id="nombre_negocio" name="nombre_negocio" value="<?php echo htmlspecialchars($config['nombre_negocio'] ?? ''); ?>">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <div class="input-group">
                                        <select class="form-select" id="country_code" name="country_code" style="max-width: 150px;" required>
                                            <?php foreach ($paises as $pais_option): ?>
                                                <option value="<?php echo htmlspecialchars($pais_option['codigo_telefono']); ?>" <?php echo ($selected_country_code == $pais_option['codigo_telefono']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($pais_option['nombre_pais'] . ' (' . $pais_option['codigo_telefono'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="tel" class="form-control" id="telefono_local" name="telefono_local" value="<?php echo htmlspecialchars($telefono_local_part); ?>" placeholder="Número local" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email de Contacto</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($config['email'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="mb-3"><label for="id_categoria_negocio" class="form-label">Categoría del Negocio</label>
                                <select class="form-select" id="id_categoria_negocio" name="id_categoria_negocio">
                                    <option value="">-- Sin Categoría --</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id_categoria']; ?>" <?php echo (isset($config['id_categoria_negocio']) && $config['id_categoria_negocio'] == $categoria['id_categoria']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['nombre_categoria']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3"><label for="activo" class="form-label">Estado del Negocio</label>
                                <select class="form-select" id="activo" name="activo">
                                    <option value="1" <?php echo (isset($config['activo']) && $config['activo'] == 1) ? 'selected' : ''; ?>>Activo</option>
                                    <option value="2" <?php echo (isset($config['activo']) && $config['activo'] == 2) ? 'selected' : ''; ?>>Suspendido</option>
                                    <option value="4" <?php echo (isset($config['activo']) && $config['activo'] == 4) ? 'selected' : ''; ?>>Pendiente por Aprobar</option>
                                    <?php if (isset($config['activo']) && $config['activo'] == 3): ?>
                                    <option value="3" selected>Eliminado</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="mb-3"><label for="direccion1" class="form-label">Dirección 1</label>
                                <input type="text" class="form-control" id="direccion1" name="direccion1" value="<?php echo htmlspecialchars($config['direccion1'] ?? ''); ?>" maxlength="128">
                            </div>
                            <div class="mb-3"><label for="direccion2" class="form-label">Dirección 2 (Opcional)</label>
                                <input type="text" class="form-control" id="direccion2" name="direccion2" value="<?php echo htmlspecialchars($config['direccion2'] ?? ''); ?>" maxlength="128">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="id_pais" class="form-label">País</label>
                                    <select class="form-select" id="id_pais" name="id_pais" required>
                                        <option value="">Seleccione un país...</option>
                                        <?php foreach ($paises as $pais): ?>
                                            <option value="<?php echo $pais['id_pais']; ?>" <?php echo (isset($config['id_pais']) && $config['id_pais'] == $pais['id_pais']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($pais['nombre_pais']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="id_estado" class="form-label">Estado / Provincia</label>
                                    <select class="form-select" id="id_estado" name="id_estado" required disabled>
                                        <option value="">Cargando...</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="ciudad" class="form-label">Ciudad</label>
                                    <input type="text" class="form-control" id="ciudad" name="ciudad" value="<?php echo htmlspecialchars($config['ciudad'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="zip_code" class="form-label">Código Postal</label>
                                    <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($config['zip_code'] ?? ''); ?>">
                                </div>
                            </div>

                            <hr>
                            <h5 class="mt-4">Horario de Trabajo</h5>

                            <div class="mb-3"><label class="form-label">Días de Trabajo</label>
                                <div>
                                    <?php foreach ($dias_semana as $num => $dia): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="dias_trabajo[]" value="<?php echo $num; ?>" id="dia_<?php echo $num; ?>" <?php echo in_array((string)$num, $dias_trabajo_activos, true) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="dia_<?php echo $num; ?>"><?php echo $dia; ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="hora_inicio" class="form-label">Hora de Inicio</label>
                                    <input type="time" class="form-control" id="hora_inicio" name="hora_inicio" value="<?php echo htmlspecialchars($config['hora_inicio'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="hora_cierre" class="form-label">Hora de Cierre</label>
                                    <input type="time" class="form-control" id="hora_cierre" name="hora_cierre" value="<?php echo htmlspecialchars($config['hora_cierre'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="intervalo_minutos" class="form-label">Intervalo (minutos)</label>
                                    <input type="number" class="form-control" id="intervalo_minutos" name="intervalo_minutos" value="<?php echo htmlspecialchars($config['intervalo_minutos'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <hr>
                            <h5 class="mt-4">Imagen de Fondo</h5>
                            <div class="mb-3"><label for="background_image" class="form-label">Subir nueva imagen de fondo (opcional)</label>
                                <input class="form-control" type="file" id="background_image" name="background_image" accept="image/jpeg, image/png">
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="negocios_configuracion.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Configuración</button>
                            </div>
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
    });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>