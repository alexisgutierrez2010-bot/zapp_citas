<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Aplicada la internacionalización (i18n).
require_once 'auth_check.php';
require_once 'config.php';

$es_administrador = (isset($rol_session) && strcasecmp(trim($rol_session), 'Administrador') == 0);

// 1. Verificar que se ha proporcionado un ID válido
$id_cita = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_cita <= 0) {
    header("Location: citas_lista.php?status=error&message=" . urlencode("ID de cita no válido."));
    exit();
}

// 2. Obtener los datos actuales de la cita
if ($es_administrador) {
    $stmt_cita = $conn->prepare("SELECT * FROM j108_citas WHERE id_cita = ?");
    $stmt_cita->bind_param("i", $id_cita);
} else {
    $stmt_cita = $conn->prepare("SELECT * FROM j108_citas WHERE id_cita = ? AND id_negocio = ?");
    $stmt_cita->bind_param("ii", $id_cita, $id_negocio_session);
}

$stmt_cita->execute();
$result_cita = $stmt_cita->get_result();
if ($result_cita->num_rows === 1) {
    $cita = $result_cita->fetch_assoc();
} else {
    header("Location: citas_lista.php?status=error&message=" . urlencode("Cita no encontrada."));
    exit();
}
$stmt_cita->close();

$fecha_seleccionada = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d', strtotime($cita['fecha_hora_inicio']));

$id_negocio_cita = $cita['id_negocio']; // Usar el negocio de la cita, no el de la sesión

// Si la cita es una Reunión, obtener la lista de invitados
$invitados = [];
if ($cita['tipo_cita'] === 'Reunion') {
    $stmt_invitados = $conn->prepare("SELECT * FROM j109_invitados_cita WHERE id_cita = ?");
    $stmt_invitados->bind_param("i", $id_cita);
    $stmt_invitados->execute();
    $result_invitados = $stmt_invitados->get_result();
    while ($row = $result_invitados->fetch_assoc()) {
        $invitados[] = $row;
    }
    $stmt_invitados->close();
}

// Obtener citas existentes para el día seleccionado para verificar colisiones, EXCLUYENDO la actual
$citas_existentes = [];
$stmt_citas_colision = $conn->prepare("SELECT fecha_hora_inicio, fecha_hora_fin FROM j108_citas WHERE id_negocio = ? AND DATE(fecha_hora_inicio) = ? AND id_cita != ? AND estado_cita != 'Cancelada'");
$stmt_citas_colision->bind_param("isi", $id_negocio_cita, $fecha_seleccionada, $id_cita);
$stmt_citas_colision->execute();
$res_citas_colision = $stmt_citas_colision->get_result();
while ($row = $res_citas_colision->fetch_assoc()) {
    $citas_existentes[] = [
        'start' => new DateTime($row['fecha_hora_inicio']),
        'end' => new DateTime($row['fecha_hora_fin'])
    ];
}
$stmt_citas_colision->close();

// 3. Obtener listas de clientes y servicios para los desplegables
$stmt_clientes = $conn->prepare("SELECT id_cliente, nombre_completo FROM j106_clientes WHERE id_negocio = ? ORDER BY nombre_completo ASC");
$stmt_clientes->bind_param("i", $id_negocio_cita);
$stmt_clientes->execute();
$clientes_result = $stmt_clientes->get_result();

$stmt_servicios = $conn->prepare("SELECT id_servicio, nombre_servicio FROM j104_servicios WHERE id_negocio = ? ORDER BY nombre_servicio ASC");
$stmt_servicios->bind_param("i", $id_negocio_cita);
$stmt_servicios->execute();
$servicios_result = $stmt_servicios->get_result();

// 4. Obtener la configuración para los horarios
$stmt_config = $conn->prepare("SELECT hora_inicio, hora_cierre, intervalo_minutos FROM j102_negocios WHERE id_negocio = ?");
$stmt_config->bind_param("i", $id_negocio_cita);
$stmt_config->execute();
$config = $stmt_config->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: <?php echo $daily_bg_color; ?>;">
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3>Editando Cita</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        if (isset($_GET['message_key'])) {
                            echo "<div class='alert alert-danger'>" . htmlspecialchars($_GET['message_key']) . "</div>";
                        } elseif (isset($_GET['status']) && $_GET['status'] == 'error') {
                            // Fallback para mensajes antiguos
                            $errorMessage = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Error en la operación.';
                            echo '<div class="alert alert-danger">' . $errorMessage . '</div>';
                        }
                        ?>
                        <form action="citas_actualizar.php" method="POST">
                            <input type="hidden" name="id_cita" value="<?php echo $cita['id_cita']; ?>">
                            <input type="hidden" name="tipo_cita" value="<?php echo $cita['tipo_cita']; ?>">
                            <input type="hidden" name="id_negocio" value="<?php echo $id_negocio_cita; ?>">

                            <div class="mb-3">
                                <label class="form-label">Tipo de Cita</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($cita['tipo_cita']); ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label for="descripcion_trabajo" class="form-label" id="label_descripcion">
                                    <?php echo ($cita['tipo_cita'] === 'Reunion') ? 'Asunto de la Reunión' : 'Descripción Adicional'; ?>
                                </label>
                                <textarea class="form-control" id="descripcion_trabajo" name="descripcion_trabajo" rows="2"><?php echo htmlspecialchars($cita['descripcion_trabajo']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="id_cliente" class="form-label">Cliente</label>
                                <select class="form-select" id="id_cliente" name="id_cliente" required>
                                    <?php while($cliente = $clientes_result->fetch_assoc()): ?>
                                        <option value="<?php echo $cliente['id_cliente']; ?>" <?php echo ($cliente['id_cliente'] == $cita['id_cliente']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cliente['nombre_completo']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3" id="campo_servicio" style="<?php echo ($cita['tipo_cita'] === 'Reunion') ? 'display: none;' : ''; ?>">
                                <label for="id_servicio" class="form-label">Servicio</label>
                                <select class="form-select" id="id_servicio" name="id_servicio" required>
                                    <?php while($servicio = $servicios_result->fetch_assoc()): ?>
                                        <option value="<?php echo $servicio['id_servicio']; ?>" <?php echo ($servicio['id_servicio'] == $cita['id_servicio']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($servicio['nombre_servicio']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="fecha_cita" class="form-label">Fecha</label>
                                <input type="date" class="form-control" id="fecha_cita" name="fecha_cita" value="<?php echo htmlspecialchars($fecha_seleccionada); ?>" required onchange="actualizarFecha(this.value)">
                            </div>

                            <div class="mb-3">
                                <label for="hora_cita" class="form-label">Hora</label>
                                <select class="form-select" id="hora_cita" name="hora_cita" required>
                                    <?php
                                    if (isset($config)) {
                                        $start = new DateTime($config['hora_inicio']);
                                        $end = new DateTime($config['hora_cierre']);
                                        $interval = new DateInterval('PT' . $config['intervalo_minutos'] . 'M');
                                        $slots = new DatePeriod($start, $interval, $end);
                                        $hora_cita_actual = date('H:i', strtotime($cita['fecha_hora_inicio']));

                                        foreach ($slots as $slot) {
                                            $slot_format = $slot->format('H:i');
                                            $slot_start_dt = new DateTime($fecha_seleccionada . ' ' . $slot_format);
                                            $ocupado = false;
                                            foreach ($citas_existentes as $cita_existente) {
                                                if ($slot_start_dt >= $cita_existente['start'] && $slot_start_dt < $cita_existente['end']) {
                                                    $ocupado = true;
                                                    break;
                                                }
                                            }
                                            $selected = ($slot_format == $hora_cita_actual && $fecha_seleccionada == date('Y-m-d', strtotime($cita['fecha_hora_inicio']))) ? 'selected' : '';
                                            $disabled = $ocupado ? 'disabled' : '';
                                            $display_text = $ocupado ? ' (Ocupado)' : '';
                                            echo '<option value="' . $slot_format . '" ' . $selected . ' ' . $disabled . '>' . $slot->format('h:i A') . $display_text . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Sección de Invitados (solo para Reuniones) -->
                            <?php if ($cita['tipo_cita'] === 'Reunion'): ?>
                            <div id="seccion_invitados">
                                <hr>
                                <h5>Invitados a la Reunión</h5>
                                <div id="lista_invitados">
                                    <?php foreach ($invitados as $index => $invitado): ?>
                                        <div class="row g-2 mb-2 align-items-center">
                                            <input type="hidden" name="invitado_id[]" value="<?php echo $invitado['id_invitado']; ?>">
                                            <div class="col-sm-4"><input type="text" name="invitado_nombre[]" class="form-control form-control-sm" placeholder="Nombre Invitado" value="<?php echo htmlspecialchars($invitado['nombre_invitado']); ?>" required></div>
                                            <div class="col-sm-4"><input type="email" name="invitado_email[]" class="form-control form-control-sm" placeholder="Email Invitado" value="<?php echo htmlspecialchars($invitado['correo_electronico_invitado']); ?>" required></div>
                                            <div class="col-sm-3"><input type="tel" name="invitado_telefono[]" class="form-control form-control-sm" placeholder="Teléfono (Opcional)" value="<?php echo htmlspecialchars($invitado['numero_celular_invitado']); ?>"></div>
                                            <div class="col-sm-1"><button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.parentElement.remove()">X</button></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btn_anadir_invitado">
                                    Añadir Invitado
                                </button>
                                <hr>
                            </div>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-success w-100">Actualizar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnAnadirInvitado = document.getElementById('btn_anadir_invitado');
        if (btnAnadirInvitado) {
            const listaInvitados = document.getElementById('lista_invitados');
            let contadorNuevos = 0;

            btnAnadirInvitado.addEventListener('click', function() {
                contadorNuevos++;
                const divInvitado = document.createElement('div');
                divInvitado.classList.add('row', 'g-2', 'mb-2', 'align-items-center');
                divInvitado.innerHTML = `
                    <input type="hidden" name="invitado_id[]" value="0"> <!-- ID 0 para nuevos invitados -->
                    <div class="col-sm-4"><input type="text" name="invitado_nombre[]" class="form-control form-control-sm" placeholder="Nuevo Invitado ${contadorNuevos}" required></div>
                    <div class="col-sm-4"><input type="email" name="invitado_email[]" class="form-control form-control-sm" placeholder="Email Nuevo Invitado" required></div>
                    <div class="col-sm-3"><input type="tel" name="invitado_telefono[]" class="form-control form-control-sm" placeholder="Teléfono (Opcional)"></div>
                    <div class="col-sm-1"><button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.parentElement.remove()">X</button></div>
                `;
                listaInvitados.appendChild(divInvitado);
            });
        }
    });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>