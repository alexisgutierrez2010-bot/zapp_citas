<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Aplicada la internacionalización (i18n).
require_once 'auth_check.php'; // Asegura que el usuario ha iniciado sesión y obtiene $id_negocio_session
require_once 'config.php';

// --- OBTENER DATOS ---
// 1. Obtener la configuración del negocio
$stmt_config = $conn->prepare("SELECT * FROM j102_negocios WHERE id_negocio = ?");
$stmt_config->bind_param("i", $id_negocio_session);
$stmt_config->execute();
$config = $stmt_config->get_result()->fetch_assoc();
$stmt_config->close();

// 2. Obtener todos los clientes para el menú desplegable
$stmt_clientes = $conn->prepare("SELECT id_cliente, nombre_completo FROM j106_clientes WHERE id_negocio = ? AND activo = 1 ORDER BY nombre_completo ASC");
$stmt_clientes->bind_param("i", $id_negocio_session);
$stmt_clientes->execute();
$clientes_result = $stmt_clientes->get_result();

// 3. Obtener todos los servicios activos para el menú desplegable
$stmt_servicios = $conn->prepare("SELECT id_servicio, nombre_servicio FROM j104_servicios WHERE activo = TRUE AND id_negocio = ? ORDER BY nombre_servicio ASC");
$stmt_servicios->bind_param("i", $id_negocio_session);
$stmt_servicios->execute();
$servicios_result = $stmt_servicios->get_result();


// 4. Determinar la fecha para filtrar las citas. Por defecto, es el día de hoy.
$fecha_filtro = isset($_GET['fecha_filtro']) && !empty($_GET['fecha_filtro']) ? $_GET['fecha_filtro'] : date('Y-m-d');

// 5. Calcular fechas para los botones de navegación
$fecha_actual_obj = new DateTime($fecha_filtro);
$url_anterior = 'citas_lista.php?fecha_filtro=' . (clone $fecha_actual_obj)->modify('-1 day')->format('Y-m-d');
$url_siguiente = 'citas_lista.php?fecha_filtro=' . (clone $fecha_actual_obj)->modify('+1 day')->format('Y-m-d');


?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('appointments_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Columna para el formulario de agendar cita -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo __('appointments_schedule_new'); ?></h3>
                    </div>
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
                        <form action="citas_crear.php" method="POST">
                            <!-- Selector de Tipo de Cita -->
                            <div class="mb-3">
                                <label class="form-label"><?php echo __('appointments_type'); ?></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_cita" id="tipo_servicio" value="Servicio" checked>
                                    <label class="form-check-label" for="tipo_servicio"><?php echo __('appointments_type_service'); ?></label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_cita" id="tipo_reunion" value="Reunion">
                                    <label class="form-check-label" for="tipo_reunion"><?php echo __('appointments_type_meeting'); ?></label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="id_cliente" class="form-label"><?php echo __('appointments_client'); ?></label>
                                <select class="form-select" id="id_cliente" name="id_cliente" required>
                                    <option value=""><?php echo __('appointments_select_client'); ?></option>
                                    <?php while($cliente = $clientes_result->fetch_assoc()): ?>
                                        <option value="<?php echo $cliente['id_cliente']; ?>"><?php echo htmlspecialchars($cliente['nombre_completo']); ?></option>
                                    <?php endwhile; ?>
                                    <?php mysqli_data_seek($clientes_result, 0); // Reiniciar puntero para reutilizar ?>
                                </select>
                            </div>

                            <!-- Campo de Servicio (se muestra/oculta con JS) -->
                            <div class="mb-3" id="campo_servicio">
                                <label for="id_servicio" class="form-label"><?php echo __('appointments_service'); ?></label>
                                <select class="form-select" id="id_servicio" name="id_servicio">
                                    <option value=""><?php echo __('appointments_select_service'); ?></option>
                                    <?php while($servicio = $servicios_result->fetch_assoc()): ?>
                                        <option value="<?php echo $servicio['id_servicio']; ?>"><?php echo htmlspecialchars($servicio['nombre_servicio']); ?></option>
                                    <?php endwhile; ?>
                                    <?php mysqli_data_seek($servicios_result, 0); // Reiniciar puntero ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="fecha_cita" class="form-label"><?php echo __('appointments_date'); ?></label>
                                <input type="date" class="form-control" id="fecha_cita" name="fecha_cita" required>
                            </div>
                            <div class="mb-3">
                                <label for="hora_cita" class="form-label"><?php echo __('appointments_time'); ?></label>
                                <select class="form-select" id="hora_cita" name="hora_cita" required>
                                    <option value=""><?php echo __('appointments_select_time'); ?></option>
                                    <?php
                                    // SOLUCIÓN: Generar los intervalos dinámicamente desde la configuración del negocio.
                                    // Esto asegura que los horarios mostrados coincidan con el intervalo definido.
                                    $start = new DateTime($config['hora_inicio']);
                                    $end = new DateTime($config['hora_cierre']);
                                    $interval = new DateInterval('PT' . $config['intervalo_minutos'] . 'M');
                                    $slots = new DatePeriod($start, $interval, $end);
 
                                    foreach ($slots as $slot) {
                                        echo '<option value="' . $slot->format('H:i') . '">' . $slot->format('h:i A') . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="descripcion_trabajo" class="form-label" id="label_descripcion"><?php echo __('appointments_additional_desc'); ?></label>
                                <textarea class="form-control" id="descripcion_trabajo" name="descripcion_trabajo" rows="2"></textarea>
                            </div>

                            <!-- Sección de Invitados (se muestra/oculta con JS) -->
                            <div id="seccion_invitados" style="display: none;">
                                <hr>
                                <h5><?php echo __('appointments_meeting_guests'); ?></h5>
                                <div id="lista_invitados">
                                    <!-- Los invitados se añadirán aquí dinámicamente -->
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btn_anadir_invitado">
                                    <?php echo __('appointments_add_guest'); ?>
                                </button>
                                <hr>
                            </div>

                            <div class="d-grid gap-2 d-sm-flex">
                                <button type="submit" class="btn btn-primary flex-grow-1"><?php echo __('appointments_schedule_button'); ?></button>
                                <button type="reset" class="btn btn-secondary flex-grow-1"><?php echo __('services_form_clear'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Columna para la lista de citas -->
            <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3><?php echo __('appointments_list_title'); ?></h3>
                    <div class="text-end">
                        <span class="fs-5 text-muted">🗓️ <?php echo (new DateTime())->format('d/m/Y'); ?></span><br>
                        <span class="fs-4 fw-bold"><?php echo (new DateTime())->format('H:i:s'); ?></span>
                    </div>
                </div>
                <h5 class="text-muted mb-3">Para: <?php echo htmlspecialchars($config['nombre_negocio'] ?? 'Negocio Desconocido'); ?></h5>
                
                <!-- Formulario de filtro por fecha -->
                <form action="citas_lista.php" method="GET" class="d-flex flex-wrap justify-content-between align-items-center mb-3 bg-light p-3 rounded gap-2">
                    <a href="<?php echo $url_anterior; ?>" class="btn btn-secondary"><?php echo __('appointments_prev_day'); ?></a>
                    <div class="d-flex align-items-center flex-grow-1 justify-content-center">
                        <label for="fecha_filtro" class="col-form-label fw-bold me-2"><?php echo __('appointments_filter_day'); ?></label>
                        <input type="date" class="form-control me-2" id="fecha_filtro" name="fecha_filtro" value="<?php echo htmlspecialchars($fecha_filtro); ?>">
                        <button type="submit" class="btn btn-info"><?php echo __('appointments_filter_button'); ?></button>
                    </div>
                    <div class="col-auto">
                        <a href="<?php echo $url_siguiente; ?>" class="btn btn-secondary"><?php echo __('appointments_next_day'); ?></a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th><?php echo __('appointments_col_schedule'); ?></th>
                                <th><?php echo __('clients'); ?></th>
                                <th><?php echo __('services'); ?></th>
                                <th><?php echo __('appointments_col_duration'); ?></th>
                                <th><?php echo __('status'); ?></th>
                                <th><abbr title="Contador de Emails Enviados">📧</abbr></th>
                                <th><abbr title="Contador de SMS Enviados">📱</abbr></th>
                                <th><?php echo __('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Modificamos la consulta para filtrar por la fecha seleccionada
                            $sql_citas = "SELECT j108_citas.id_cita, j108_citas.fecha_hora_inicio, j108_citas.fecha_hora_fin, j108_citas.estado_cita, j108_citas.tipo_cita, j108_citas.IN_EMAIL, j108_citas.IN_SMS, j106_clientes.nombre_completo, j104_servicios.nombre_servicio, j104_servicios.duracion_valor, j104_servicios.duracion_unidad
                                          FROM j108_citas
                                          JOIN j106_clientes ON j108_citas.id_cliente = j106_clientes.id_cliente
                                          LEFT JOIN j104_servicios ON j108_citas.id_servicio = j104_servicios.id_servicio
                                          WHERE DATE(j108_citas.fecha_hora_inicio) <= ? AND DATE(j108_citas.fecha_hora_fin) >= ? AND j108_citas.id_negocio = ?
                                          ORDER BY j108_citas.fecha_hora_inicio ASC";
                            
                            $stmt_citas = $conn->prepare($sql_citas);
                            $stmt_citas->bind_param("ssi", $fecha_filtro, $fecha_filtro, $id_negocio_session);
                            $stmt_citas->execute();
                            $citas_result_list = $stmt_citas->get_result();

                            $i = 1;
                            if ($citas_result_list->num_rows > 0) {
                                $status_colors = [
                                    'Pendiente' => 'bg-info text-dark',
                                    'Completada' => 'bg-success',
                                    'Cancelada' => 'bg-danger',
                                    'Pospuesta' => 'bg-warning text-dark',
                                    'No Asistió' => 'bg-secondary',
                                    'Confirmada' => 'bg-primary', // Azul
                                    'Vencida' => 'bg-dark', // Gris oscuro
                                ];

                                while($cita = $citas_result_list->fetch_assoc()) {
                                    $estado = htmlspecialchars($cita["estado_cita"]);
                                    $color_clase = $status_colors[$estado] ?? 'bg-light text-dark';
                                    $es_reunion = $cita['tipo_cita'] === 'Reunion';

                                    // --- Lógica de Acciones Dinámicas ---
                                    $acciones_html = '<li><a class="dropdown-item" href="citas_editar.php?id=' . $cita['id_cita'] . '">✏️ Editar Cita</a></li>';
                                    $acciones_html .= '<li><a class="dropdown-item text-danger" href="#" onclick="document.getElementById(\'delete-form-' . $cita['id_cita'] . '\').submit(); return false;">🗑️ Eliminar</a></li>';
                                    $acciones_html .= '<li><hr class="dropdown-divider"></li>';
                                    $acciones_html .= '<li><a class="dropdown-item" href="citas_confirmar_envio.php?id_cita=' . $cita['id_cita'] . '&accion=NUEVA">📧 Reenviar Email</a></li>';
                                    
                                    $acciones_estado_html = '';
                                    if ($estado === 'Pendiente') {
                                        $acciones_estado_html .= '<li><a class="dropdown-item" href="citas_actualizar_estado.php?id=' . $cita['id_cita'] . '&estado=Confirmada">👍 Confirmar</a></li>';
                                        $acciones_estado_html .= '<li><a class="dropdown-item" href="citas_actualizar_estado.php?id=' . $cita['id_cita'] . '&estado=Cancelada">❌ Cancelar</a></li>';
                                    }
                                    if ($estado === 'Confirmada') {
                                        $acciones_estado_html .= '<li><a class="dropdown-item" href="citas_actualizar_estado.php?id=' . $cita['id_cita'] . '&estado=Completada">✅ Completar</a></li>';
                                        $acciones_estado_html .= '<li><a class="dropdown-item" href="citas_actualizar_estado.php?id=' . $cita['id_cita'] . '&estado=No Asistió">👤 No Asistió</a></li>';
                                        $acciones_estado_html .= '<li><a class="dropdown-item" href="citas_actualizar_estado.php?id=' . $cita['id_cita'] . '&estado=Cancelada">❌ Cancelar</a></li>';
                                    }
                                    if ($estado === 'Cancelada' || $estado === 'Completada' || $estado === 'No Asistió' || $estado === 'Vencida') {
                                        $acciones_estado_html .= '<li><a class="dropdown-item" href="citas_actualizar_estado.php?id=' . $cita['id_cita'] . '&estado=Pendiente">🔄 Marcar Pendiente</a></li>';
                                    }

                                    echo "<tr>";
                                    echo "<td>" . $i++ . "</td>";
                                    echo "<td>" . date('h:i A', strtotime($cita["fecha_hora_inicio"])) . " - " . date('h:i A', strtotime($cita["fecha_hora_fin"])) . "</td>";
                                    echo "<td>" . htmlspecialchars($cita["nombre_completo"]) . "</td>";
                                    echo "<td>" . ($es_reunion ? '<strong>Reunión</strong>' : htmlspecialchars($cita["nombre_servicio"])) . "</td>";
                                    echo "<td>" . htmlspecialchars($cita["duracion_valor"]) . " " . htmlspecialchars($cita["duracion_unidad"]) . "</td>";
                                    echo "<td><span class='badge rounded-pill " . $color_clase . "'>" . $estado . "</span></td>";
                                    echo "<td><span class='badge rounded-pill bg-dark'>" . htmlspecialchars($cita["IN_EMAIL"]) . "</span></td>";
                                    echo "<td><span class='badge rounded-pill bg-dark'>" . htmlspecialchars($cita["IN_SMS"]) . "</span></td>";
                                    echo '<td>
                                            <div class="btn-group dropend">
                                                <button type="button" class="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Acciones
                                                </button>
                                                <ul class="dropdown-menu">
                                                    ' . $acciones_estado_html . '
                                                    <hr>
                                                    ' . $acciones_html . '
                                                </ul>
                                            </div>
                                            <form id="delete-form-' . $cita['id_cita'] . '" action="citas_eliminar.php" method="POST" style="display:none;" onsubmit="return confirm(\'¿Estás seguro de que quieres eliminar esta cita?\');">
                                                <input type="hidden" name="id_cita" value="' . $cita['id_cita'] . '">
                                            </form>
                                          </td>';
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='9' class='text-center'>" . __('appointments_no_appointments_for_day') . " " . date('d/m/Y', strtotime($fecha_filtro)) . ".</td></tr>";
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
        const tipoServicioRadio = document.getElementById('tipo_servicio');
        const tipoReunionRadio = document.getElementById('tipo_reunion');
        const campoServicio = document.getElementById('campo_servicio');
        const selectServicio = document.getElementById('id_servicio');
        const seccionInvitados = document.getElementById('seccion_invitados');
        const labelDescripcion = document.getElementById('label_descripcion');

        function toggleCampos() {
            if (tipoReunionRadio.checked) {
                campoServicio.style.display = 'none';
                selectServicio.required = false;
                seccionInvitados.style.display = 'block';
                labelDescripcion.textContent = 'Tema de la Reunión';
            } else {
                campoServicio.style.display = 'block';
                selectServicio.required = true;
                seccionInvitados.style.display = 'none';
                labelDescripcion.textContent = 'Descripción Adicional';
            }
        }

        tipoServicioRadio.addEventListener('change', toggleCampos);
        tipoReunionRadio.addEventListener('change', toggleCampos);

        // Lógica para añadir invitados dinámicamente
        const btnAnadirInvitado = document.getElementById('btn_anadir_invitado');
        const listaInvitados = document.getElementById('lista_invitados');
        let contadorInvitados = 0;

        btnAnadirInvitado.addEventListener('click', function() {
            contadorInvitados++;
            const divInvitado = document.createElement('div');
            divInvitado.classList.add('row', 'g-2', 'mb-2', 'align-items-center');
            divInvitado.innerHTML = `
                <div class="col-sm-4"><input type="text" name="invitado_nombre[]" class="form-control form-control-sm" placeholder="Nombre Invitado ${contadorInvitados}" required></div>
                <div class="col-sm-4"><input type="email" name="invitado_email[]" class="form-control form-control-sm" placeholder="Email Invitado ${contadorInvitados}" required></div>
                <div class="col-sm-3"><input type="tel" name="invitado_telefono[]" class="form-control form-control-sm" placeholder="Teléfono (Opcional)"></div>
                <div class="col-sm-1"><button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.parentElement.remove()">X</button></div>
            `;
            listaInvitados.appendChild(divInvitado);
        });

        // Inicializar el estado del formulario
        toggleCampos();
    });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>