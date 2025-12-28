<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).

require_once 'auth_check.php';
require_once 'config.php';

// Configuración de paginación
$registros_por_pagina = 50;
$pagina_actual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Construcción de la consulta dinámica según el rol
$where_clause = "";
$params = [];
$types = "";

// Si NO es Master, filtramos para que solo vea la auditoría de su propio negocio
if ($rol_session !== 'Master') {
    $where_clause = "WHERE a.id_negocio = ?";
    $params[] = $id_negocio_session;
    $types .= "i";
}

// Consulta Principal: Unimos con usuarios y negocios para mostrar nombres en vez de IDs
$sql = "SELECT a.id_audit, a.accion, a.descripcion, a.ip_address, a.fecha_hora, 
               u.nombre_usuario, n.nombre_negocio
        FROM j099_auditorias a
        LEFT JOIN j100_usuarios u ON a.id_usuario = u.id_usuario
        LEFT JOIN j102_negocios n ON a.id_negocio = n.id_negocio
        $where_clause
        ORDER BY a.fecha_hora DESC
        LIMIT ? OFFSET ?";

// Añadir parámetros de paginación
$params[] = $registros_por_pagina;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Consulta para contar el total de registros (para la paginación)
$sql_count = "SELECT COUNT(*) as total FROM j099_auditorias a $where_clause";
$stmt_count = $conn->prepare($sql_count);
if ($rol_session !== 'Master') {
    $stmt_count->bind_param("i", $id_negocio_session);
}
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Auditoría</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: <?php echo $daily_bg_color; ?>;">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center bg-dark text-white">
                <h3 class="mb-0">Reporte de Auditoría</h3>
                <span class="badge bg-info text-dark">Total: <?php echo number_format($total_registros); ?> eventos</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm align-middle">
                        <thead class="table-secondary">
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Usuario</th>
                                <th>Negocio</th>
                                <th>Acción</th>
                                <th>Descripción</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td style="white-space: nowrap;"><?php echo date('d/m/Y H:i', strtotime($row['fecha_hora'])); ?></td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($row['nombre_usuario'] ?? 'Sistema'); ?></td>
                                        <td><?php echo htmlspecialchars($row['nombre_negocio'] ?? 'N/A'); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['accion']); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                        <td><small class="text-muted font-monospace"><?php echo htmlspecialchars($row['ip_address']); ?></small></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-4">No hay registros de auditoría disponibles.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación Simple -->
                <?php if ($total_paginas > 1): ?>
                <nav aria-label="Navegación de auditoría" class="mt-3">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $pagina_actual - 1; ?>">Anterior</a>
                        </li>
                        <li class="page-item disabled">
                            <span class="page-link">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
                        </li>
                        <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $pagina_actual + 1; ?>">Siguiente</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
<?php
$stmt->close();
$stmt_count->close();
$conn->close();
?>