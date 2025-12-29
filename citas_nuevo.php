<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
require_once 'auth_check.php';
require_once 'config.php';

$es_administrador = (isset($rol_session) && strcasecmp(trim($rol_session), 'Administrador') == 0);

$fecha_seleccionada = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

// Determinar el ID del negocio a usar
$id_negocio_seleccionado = $id_negocio_session;
if ($es_administrador) {
    // Si es admin, usa el GET, o el de sesión por defecto, o el primero activo si no hay sesión válida
    if (isset($_GET['id_negocio']) && $_GET['id_negocio'] > 0) {
        $id_negocio_seleccionado = (int)$_GET['id_negocio'];
    } elseif ($id_negocio_seleccionado <= 0) {
        // Fallback: obtener el primer negocio activo
        $res = $conn->query("SELECT id_negocio FROM j102_negocios WHERE activo = 1 LIMIT 1");
        if ($row = $res->fetch_assoc()) {
            $id_negocio_seleccionado = $row['id_negocio'];
        }
    }
}

// Obtener listas para los selectores basadas en el negocio seleccionado
$clientes = [];
$servicios = [];
$negocios = [];

if ($es_administrador) {
    $res_neg = $conn->query("SELECT id_negocio, nombre_negocio FROM j102_negocios WHERE activo = 1 ORDER BY nombre_negocio");
    while ($row = $res_neg->fetch_assoc()) $negocios[] = $row;
}

if ($id_negocio_seleccionado > 0) {
    $stmt_cli = $conn->prepare("SELECT id_cliente, nombre_completo FROM j106_clientes WHERE id_negocio = ? AND activo = 1 ORDER BY nombre_completo");
    $stmt_cli->bind_param("i", $id_negocio_seleccionado);
    $stmt_cli->execute();
    $res_cli = $stmt_cli->get_result();
    while ($row = $res_cli->fetch_assoc()) $clientes[] = $row;
    $stmt_cli->close();

    $stmt_serv = $conn->prepare("SELECT id_servicio, nombre_servicio, duracion_valor, duracion_unidad FROM j104_servicios WHERE id_negocio = ? AND activo = 1 ORDER BY nombre_servicio");
    $stmt_serv->bind_param("i", $id_negocio_seleccionado);
    $stmt_serv->execute();
    $res_serv = $stmt_serv->get_result();
    while ($row = $res_serv->fetch_assoc()) $servicios[] = $row;
    $stmt_serv->close();
    
    // Obtener configuración de horarios para el select de horas
    $stmt_conf = $conn->prepare("SELECT hora_inicio, hora_cierre, intervalo_minutos FROM j102_negocios WHERE id_negocio = ?");
    $stmt_conf->bind_param("i", $id_negocio_seleccionado);
    $stmt_conf->execute();
    $config_negocio = $stmt_conf->get_result()->fetch_assoc();

    // Obtener citas existentes para el día seleccionado para verificar colisiones
    $citas_existentes = [];
    $stmt_citas = $conn->prepare("SELECT fecha_hora_inicio, fecha_hora_fin FROM j108_citas WHERE id_negocio = ? AND DATE(fecha_hora_inicio) = ? AND estado_cita != 'Cancelada'");
    $stmt_citas->bind_param("is", $id_negocio_seleccionado, $fecha_seleccionada);
    $stmt_citas->execute();
    $res_citas = $stmt_citas->get_result();
    while ($row = $res_citas->fetch_assoc()) {
        $citas_existentes[] = [
            'start' => new DateTime($row['fecha_hora_inicio']),
            'end' => new DateTime($row['fecha_hora_fin'])
        ];
    }
    $stmt_conf->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Nueva Cita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: <?php echo $daily_bg_color; ?>;">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><h3>Agendar Nueva Cita</h3></div>
                    <div class="card-body">
                        <?php if (isset($_GET['message'])): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars(urldecode($_GET['message'])); ?></div>
                        <?php endif; ?>
                        
                        <form action="citas_crear.php" method="POST">
                            <?php if ($es_administrador): ?>
                                <div class="mb-3">
                                    <label for="id_negocio" class="form-label fw-bold">Negocio</label>
                                    <select name="id_negocio" id="id_negocio" class="form-select" onchange="window.location.href='citas_nuevo.php?id_negocio='+this.value">
                                        <?php foreach ($negocios as $neg): ?>
                                            <option value="<?php echo $neg['id_negocio']; ?>" <?php echo ($neg['id_negocio'] == $id_negocio_seleccionado) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($neg['nombre_negocio']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <hr>
                            <?php else: ?>
                                <input type="hidden" name="id_negocio" value="<?php echo $id_negocio_seleccionado; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label">Tipo de Cita</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="tipo_cita" id="tipo_servicio" value="Servicio" checked onchange="toggleTipoCita()">
                                        <label class="form-check-label" for="tipo_servicio">Servicio</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="tipo_cita" id="tipo_reunion" value="Reunion" onchange="toggleTipoCita()">
                                        <label class="form-check-label" for="tipo_reunion">Reunión</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="id_cliente" class="form-label">Cliente</label>
                                <select name="id_cliente" id="id_cliente" class="form-select" required>
                                    <option value="">-- Seleccione Cliente --</option>
                                    <?php foreach ($clientes as $cli): ?>
                                        <option value="<?php echo $cli['id_cliente']; ?>"><?php echo htmlspecialchars($cli['nombre_completo']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3" id="div_servicio">
                                <label for="id_servicio" class="form-label">Servicio</label>
                                <select name="id_servicio" id="id_servicio" class="form-select">
                                    <option value="">-- Seleccione Servicio --</option>
                                    <?php foreach ($servicios as $serv): ?>
                                        <option value="<?php echo $serv['id_servicio']; ?>">
                                            <?php echo htmlspecialchars($serv['nombre_servicio']) . " (" . $serv['duracion_valor'] . " " . $serv['duracion_unidad'] . ")"; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="fecha_cita" class="form-label">Fecha</label>
                                    <input type="date" name="fecha_cita" id="fecha_cita" class="form-control" value="<?php echo htmlspecialchars($fecha_seleccionada); ?>" required onchange="actualizarFecha(this.value)">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="hora_cita" class="form-label">Hora</label>
                                    <select name="hora_cita" id="hora_cita" class="form-select" required>
                                        <option value="">-- Seleccione Hora --</option>
                                        <?php
                                        if (isset($config_negocio)) {
                                            $start = new DateTime($config_negocio['hora_inicio']);
                                            $end = new DateTime($config_negocio['hora_cierre']);
                                            $interval = new DateInterval('PT' . $config_negocio['intervalo_minutos'] . 'M');
                                            $slots = new DatePeriod($start, $interval, $end);

                                            foreach ($slots as $slot) {
                                                $slot_start_str = $slot->format('H:i');
                                                $slot_start_dt = new DateTime($fecha_seleccionada . ' ' . $slot_start_str);
                                                $ocupado = false;
                                                foreach ($citas_existentes as $cita_existente) {
                                                    if ($slot_start_dt >= $cita_existente['start'] && $slot_start_dt < $cita_existente['end']) {
                                                        $ocupado = true;
                                                        break;
                                                    }
                                                }
                                                $disabled = $ocupado ? 'disabled' : '';
                                                $display_text = $ocupado ? ' (Ocupado)' : '';
                                                echo '<option value="' . $slot_start_str . '" ' . $disabled . '>' . $slot->format('h:i A') . $display_text . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="descripcion_trabajo" class="form-label" id="lbl_descripcion">Notas / Descripción</label>
                                <textarea name="descripcion_trabajo" id="descripcion_trabajo" class="form-control" rows="2"></textarea>
                            </div>

                            <div id="div_invitados" style="display:none;">
                                <hr>
                                <h5>Invitados</h5>
                                <div id="lista_invitados"></div>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="agregarInvitado()">+ Añadir Invitado</button>
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">Agendar Cita</button>
                                <a href="citas_lista.php" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function actualizarFecha(nuevaFecha) {
            const url = new URL(window.location.href);
            url.searchParams.set('fecha', nuevaFecha);
            window.location.href = url.toString();
        }

        function toggleTipoCita() {
            const esReunion = document.getElementById('tipo_reunion').checked;
            document.getElementById('div_servicio').style.display = esReunion ? 'none' : 'block';
            document.getElementById('div_invitados').style.display = esReunion ? 'block' : 'none';
            document.getElementById('id_servicio').required = !esReunion;
            document.getElementById('lbl_descripcion').innerText = esReunion ? 'Tema de la Reunión' : 'Notas / Descripción';
        }

        function agregarInvitado() {
            const div = document.createElement('div');
            div.className = 'row g-2 mb-2';
            div.innerHTML = `
                <div class="col-5"><input type="text" name="invitado_nombre[]" class="form-control form-control-sm" placeholder="Nombre" required></div>
                <div class="col-5"><input type="email" name="invitado_email[]" class="form-control form-control-sm" placeholder="Email" required></div>
                <div class="col-2"><button type="button" class="btn btn-sm btn-danger w-100" onclick="this.parentElement.parentElement.remove()">X</button></div>
            `;
            document.getElementById('lista_invitados').appendChild(div);
        }
        // Init
        toggleTipoCita();
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>