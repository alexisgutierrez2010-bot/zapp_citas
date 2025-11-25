<?php
// Revisado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM en fecha Nov/17/2025 //
require_once 'auth_check.php';
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Auditoría</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php 
    include 'navbar.php';
    ?>

    <div class="container mt-4">
        <?php
        // --- Lógica de Filtros ---
        $filtro_usuario = isset($_GET['filtro_usuario']) ? (int)$_GET['filtro_usuario'] : 0;
        $filtro_negocio = isset($_GET['filtro_negocio']) ? (int)$_GET['filtro_negocio'] : 0;
        $filtro_fecha_desde = isset($_GET['filtro_fecha_desde']) && !empty($_GET['filtro_fecha_desde']) ? $_GET['filtro_fecha_desde'] : '';
        $filtro_fecha_hasta = isset($_GET['filtro_fecha_hasta']) && !empty($_GET['filtro_fecha_hasta']) ? $_GET['filtro_fecha_hasta'] : '';

        $where_clauses = [];
        $params = [];
        $types = '';
        $query_string_parts = [];

        if ($filtro_usuario > 0) {
            $where_clauses[] = "a.id_usuario = ?";
            $params[] = $filtro_usuario;
            $types .= 'i';
            $query_string_parts[] = "filtro_usuario=" . $filtro_usuario;
        }
        if ($filtro_negocio > 0) {
            $where_clauses[] = "a.id_negocio = ?";
            $params[] = $filtro_negocio;
            $types .= 'i';
            $query_string_parts[] = "filtro_negocio=" . $filtro_negocio;
        }
        if (!empty($filtro_fecha_desde)) {
            $where_clauses[] = "DATE(a.fecha_hora) >= ?";
            $params[] = $filtro_fecha_desde;
            $types .= 's';
            $query_string_parts[] = "filtro_fecha_desde=" . urlencode($filtro_fecha_desde);
        }
        if (!empty($filtro_fecha_hasta)) {
            $where_clauses[] = "DATE(a.fecha_hora) <= ?";
            $params[] = $filtro_fecha_hasta;
            $types .= 's';
            $query_string_parts[] = "filtro_fecha_hasta=" . urlencode($filtro_fecha_hasta);
        }

        $where_sql = "";
        if (!empty($where_clauses)) {
            $where_sql = " WHERE " . implode(" AND ", $where_clauses);
        }
        $query_string = implode('&', $query_string_parts);

        // --- Paginación ---
        $registros_por_pagina = 50;
        $pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $offset = ($pagina_actual - 1) * $registros_por_pagina;

        // Contar el total de registros para la paginación (considerando los filtros)
        $sql_total = "SELECT COUNT(*) AS total FROM j099_auditorias a" . $where_sql;
        $stmt_total = $conn->prepare($sql_total);
        if (!empty($types)) {
            $stmt_total->bind_param($types, ...$params);
        }
        $stmt_total->execute();
        $total_registros = $stmt_total->get_result()->fetch_assoc()['total'];
        $stmt_total->close();
        
        $total_paginas = ceil($total_registros / $registros_por_pagina);

        // --- Consulta de Auditoría ---
        $sql = "SELECT 
                    a.fecha_hora,
                    a.accion,
                    a.descripcion,
                    a.ip_address,
                    COALESCE(u.nombre_usuario, 'Sistema/Eliminado') AS nombre_usuario,
                    COALESCE(n.nombre_negocio, 'N/A') AS nombre_negocio
                FROM j099_auditorias a
                LEFT JOIN j100_usuarios u ON a.id_usuario = u.id_usuario
                LEFT JOIN j102_negocios n ON a.id_negocio = n.id_negocio"
                . $where_sql .
                " ORDER BY a.id_audit DESC
                LIMIT ?, ?";

        // Añadir los parámetros de paginación al final
        $params_paginacion = $params;
        $params_paginacion[] = $offset;
        $params_paginacion[] = $registros_por_pagina;
        $types_paginacion = $types . 'ii';

        $stmt_audit = $conn->prepare($sql);
        $stmt_audit->bind_param($types_paginacion, ...$params_paginacion);
        $stmt_audit->execute();
        $result = $stmt_audit->get_result();

        // Obtener datos para los desplegables de filtros
        $usuarios_filtro = $conn->query("SELECT id_usuario, nombre_usuario FROM j100_usuarios ORDER BY nombre_usuario ASC");
        $negocios_filtro = $conn->query("SELECT id_negocio, nombre_negocio FROM j102_negocios ORDER BY nombre_negocio ASC");
        ?>

        <div class="card mb-4">
            <div class="card-header">
                <h4>Filtrar Registros</h4>
            </div>
            <div class="card-body">
                <form action="auditoria_reporte.php" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="filtro_usuario" class="form-label">Usuario</label>
                        <select name="filtro_usuario" id="filtro_usuario" class="form-select">
                            <option value="0">Todos</option>
                            <?php while($u = $usuarios_filtro->fetch_assoc()): ?>
                                <option value="<?php echo $u['id_usuario']; ?>" <?php if($filtro_usuario == $u['id_usuario']) echo 'selected'; ?>><?php echo htmlspecialchars($u['nombre_usuario']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filtro_negocio" class="form-label">Negocio</label>
                        <select name="filtro_negocio" id="filtro_negocio" class="form-select">
                            <option value="0">Todos</option>
                            <?php while($n = $negocios_filtro->fetch_assoc()): ?>
                                <option value="<?php echo $n['id_negocio']; ?>" <?php if($filtro_negocio == $n['id_negocio']) echo 'selected'; ?>><?php echo htmlspecialchars($n['nombre_negocio']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filtro_fecha_desde" class="form-label">Desde</label>
                        <input type="date" name="filtro_fecha_desde" id="filtro_fecha_desde" class="form-control" value="<?php echo htmlspecialchars($filtro_fecha_desde); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="filtro_fecha_hasta" class="form-label">Hasta</label>
                        <input type="date" name="filtro_fecha_hasta" id="filtro_fecha_hasta" class="form-control" value="<?php echo htmlspecialchars($filtro_fecha_hasta); ?>">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="auditoria_reporte.php" class="btn btn-secondary mt-1">Limpiar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Reporte de Auditoría (Audit Trail)</h3>
            <span class="badge bg-info text-dark">Mostrando <?php echo $result->num_rows; ?> de <?php echo $total_registros; ?> Registros</span>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Usuario</th>
                        <th>Negocio</th>
                        <th>Acción</th>
                        <th>Descripción</th>
                        <th>Dirección IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($row['fecha_hora'])); ?></td>
                                <td><?php echo htmlspecialchars($row['nombre_usuario']); ?></td>
                                <td><?php echo htmlspecialchars($row['nombre_negocio']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['accion']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">No hay registros de auditoría que coincidan con los filtros.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Navegación de Paginación -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?php if ($i == $pagina_actual) echo 'active'; ?>">
                        <a class="page-link" href="auditoria_reporte.php?pagina=<?php echo $i; ?>&<?php echo $query_string; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <!-- No incluye footer.php, así que añadimos el script de Bootstrap aquí -->
    <?php include 'footer.php'; ?>
</body>
</html>