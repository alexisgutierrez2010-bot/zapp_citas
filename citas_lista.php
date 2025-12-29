<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Aplicada la internacionalización (i18n).
require_once 'auth_check.php'; // Asegura que el usuario ha iniciado sesión y obtiene $id_negocio_session
require_once 'config.php';

// --- LÓGICA DE ROLES Y FILTRADO ---
$es_administrador = (isset($rol_session) && strcasecmp(trim($rol_session), 'Administrador') == 0);

$id_negocio_filtro = 0;
$todos_los_negocios = [];
if ($es_administrador) {
    $id_negocio_filtro = isset($_GET['id_negocio_filtro']) ? (int)$_GET['id_negocio_filtro'] : 0;
    $result_todos_negocios = $conn->query("SELECT id_negocio, nombre_negocio FROM j102_negocios WHERE activo = 1 ORDER BY nombre_negocio ASC");
    if ($result_todos_negocios) {
        while ($row = $result_todos_negocios->fetch_assoc()) {
            $todos_los_negocios[] = $row;
        }
    }
}
// --- FIN LÓGICA DE ROLES Y FILTRADO ---

// 4. Lógica de filtrado para Administrador
$fecha_filtro = isset($_GET['fecha_filtro']) && !empty($_GET['fecha_filtro']) ? $_GET['fecha_filtro'] : date('Y-m-d');

// 5. Calcular fechas para los botones de navegación
$fecha_actual_obj = new DateTime($fecha_filtro);
$url_anterior = 'citas_lista.php?fecha_filtro=' . (clone $fecha_actual_obj)->modify('-1 day')->format('Y-m-d');
$url_siguiente = 'citas_lista.php?fecha_filtro=' . (clone $fecha_actual_obj)->modify('+1 day')->format('Y-m-d');


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Citas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: <?php echo $daily_bg_color; ?>;">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Columna para la lista de citas -->
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3>Lista de Citas</h3>
                    <div class="text-end">
                        <span class="fs-5 text-muted">🗓️ <?php echo (new DateTime())->format('d/m/Y'); ?></span><br>
                        <span class="fs-4 fw-bold"><?php echo (new DateTime())->format('H:i:s'); ?></span>
                    </div>
                </div>
                
                <!-- Formulario de filtro por fecha -->
                <form action="citas_lista.php" method="GET" class="mb-3 bg-light p-3 rounded">
                    <div class="row g-2 align-items-end">
                        <?php if (strcasecmp(trim($rol_session ?? ''), 'Administrador') == 0): ?>
                        <div class="col-md-4">
                            <label for="id_negocio_filtro" class="form-label fw-bold">Filtrar por Negocio:</label>
                            <select name="id_negocio_filtro" id="id_negocio_filtro" class="form-select">
                                <option value="0">-- Todos los Negocios --</option>
                                <?php foreach ($todos_los_negocios as $negocio): ?>
                                    <option value="<?php echo $negocio['id_negocio']; ?>" <?php echo ($id_negocio_filtro == $negocio['id_negocio']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($negocio['nombre_negocio']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-5">
                            <label for="fecha_filtro" class="form-label fw-bold">Ver Día:</label>
                            <input type="date" class="form-control" id="fecha_filtro" name="fecha_filtro" value="<?php echo htmlspecialchars($fecha_filtro); ?>">
                        </div>
                        <div class="col-md-3 d-grid">
                            <button type="submit" class="btn btn-info">Filtrar</button>
                        </div>
                    </div>
                </form>
                <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
                    <a href="<?php echo $url_anterior; ?>" class="btn btn-outline-secondary">« Día Anterior</a>
                    <a href="citas_nuevo.php<?php echo ($id_negocio_filtro > 0) ? '?id_negocio=' . $id_negocio_filtro : ''; ?>" class="btn btn-success fw-bold"><i class="bi bi-plus-circle"></i> Crear Nueva Cita</a>
                    <a href="<?php echo $url_siguiente; ?>" class="btn btn-outline-secondary">Día Siguiente »</a>
                </div>

                <?php
                // Modificamos la consulta para filtrar por la fecha seleccionada
                $sql_citas = "SELECT j108_citas.id_cita, j108_citas.fecha_hora_inicio, j108_citas.fecha_hora_fin, j108_citas.estado_cita, j108_citas.tipo_cita, j108_citas.in_email, j108_citas.in_sms, j106_clientes.nombre_completo, j104_servicios.nombre_servicio, j104_servicios.duracion_valor, j104_servicios.duracion_unidad, j102_negocios.nombre_negocio
                                FROM j108_citas
                                JOIN j106_clientes ON j108_citas.id_cliente = j106_clientes.id_cliente
                                LEFT JOIN j104_servicios ON j108_citas.id_servicio = j104_servicios.id_servicio
                                JOIN j102_negocios ON j108_citas.id_negocio = j102_negocios.id_negocio";
                
                $where_clauses = ["DATE(j108_citas.fecha_hora_inicio) <= ? AND DATE(j108_citas.fecha_hora_fin) >= ?"];
                $params = [$fecha_filtro, $fecha_filtro];
                $types = "ss";

                if (strcasecmp(trim($rol_session ?? ''), 'Administrador') == 0) {
                    if ($id_negocio_filtro > 0) {
                        $where_clauses[] = "j108_citas.id_negocio = ?";
                        $params[] = $id_negocio_filtro;
                        $types .= "i";
                    }
                } else {
                    $where_clauses[] = "j108_citas.id_negocio = ?";
                    $params[] = $id_negocio_session;
                    $types .= "i";
                }
                $sql_citas .= " WHERE " . implode(' AND ', $where_clauses) . " ORDER BY j108_citas.fecha_hora_inicio ASC";

                $stmt_citas = $conn->prepare($sql_citas);
                $stmt_citas->bind_param($types, ...$params);
                $stmt_citas->execute();
                $citas_result_list = $stmt_citas->get_result();

                if ($citas_result_list->num_rows > 0) {
                    $status_colors = [
                        'Pendiente' => 'bg-info text-dark', 'Completada' => 'bg-success',
                        'Cancelada' => 'bg-danger', 'Pospuesta' => 'bg-warning text-dark',
                        'No Asistió' => 'bg-secondary', 'Confirmada' => 'bg-primary',
                        'Vencida' => 'bg-dark',
                    ];

                    while($cita = $citas_result_list->fetch_assoc()) {
                        $estado = htmlspecialchars($cita["estado_cita"]);
                        $color_clase = $status_colors[$estado] ?? 'bg-light text-dark';
                        $es_reunion = $cita['tipo_cita'] === 'Reunion';
                        ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($cita["nombre_completo"]); ?></h5>
                                        <p class="card-text text-muted mb-0">
                                            <?php echo ($es_reunion ? '<strong>Reunión</strong>' : htmlspecialchars($cita["nombre_servicio"])); ?>
                                        </p>
                                        <?php if ($es_administrador): ?>
                                            <p class="card-text"><small class="text-muted"><?php echo htmlspecialchars($cita["nombre_negocio"]); ?></small></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3">
                                        <strong><?php echo date('h:i A', strtotime($cita["fecha_hora_inicio"])) . " - " . date('h:i A', strtotime($cita["fecha_hora_fin"])); ?></strong><br>
                                        <small>Duración: <?php echo htmlspecialchars($cita["duracion_valor"]) . " " . htmlspecialchars($cita["duracion_unidad"]); ?></small>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <span class='badge rounded-pill <?php echo $color_clase; ?> fs-6'><?php echo $estado; ?></span>
                                    </div>
                                    <div class="col-md-1 text-center">
                                        <span class='badge rounded-pill bg-dark' title="Emails enviados">📧 <?php echo htmlspecialchars($cita["in_email"]); ?></span>
                                        <span class='badge rounded-pill bg-dark' title="SMS enviados">📱 <?php echo htmlspecialchars($cita["in_sms"]); ?></span>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <a class="btn btn-sm btn-warning" href="citas_editar.php?id=<?php echo $cita['id_cita']; ?>">✏️ Editar</a>
                                        <a class="btn btn-sm btn-info" href="citas_confirmar_envio.php?id_cita=<?php echo $cita['id_cita']; ?>&accion=NUEVA">📧 Reenviar</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-light d-flex flex-wrap gap-2 justify-content-start align-items-center">
                                <strong class="me-2">Acciones:</strong>
                                <?php
                                if ($estado === 'Pendiente') {
                                    echo '<a class="btn btn-sm btn-primary" href="citas_actualizar_estado.php?id=' . $cita['id_cita'] . '&estado=Confirmada">👍 Confirmar</a>';
                                }
                                if ($estado === 'Confirmada') {
                                    echo '<a class="btn btn-sm btn-success" href="citas_actualizar_estado.php?id=' . $cita['id_cita'] . '&estado=Completada">✅ Completar</a>';
                                    echo '<a class="btn btn-sm btn-secondary" href="citas_actualizar_estado.php?id=' . $cita['id_cita'] . '&estado=No Asistió">👤 No Asistió</a>';
                                }
                                if ($estado !== 'Cancelada') {
                                    echo '<a class="btn btn-sm btn-danger" href="citas_actualizar_estado.php?id=' . $cita['id_cita'] . '&estado=Cancelada" onclick="return confirm(\'¿Estás seguro de que quieres cancelar esta cita?\');">❌ Cancelar</a>';
                                }
                                if ($estado === 'Cancelada' || $estado === 'Completada' || $estado === 'No Asistió' || $estado === 'Vencida') {
                                    echo '<a class="btn btn-sm btn-dark" href="citas_actualizar_estado.php?id=' . $cita['id_cita'] . '&estado=Pendiente">🔄 Marcar Pendiente</a>';
                                }
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="alert alert-secondary text-center">No hay citas agendadas para el día ' . date('d/m/Y', strtotime($fecha_filtro)) . '.</div>';
                }
                ?>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>