<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
require_once 'auth_check.php';
require_once 'config.php';

// Filtro por negocio
$id_negocio_filtro = isset($_GET['id_negocio']) ? (int)$_GET['id_negocio'] : 0;

// Obtener lista de negocios para el filtro
$negocios = $conn->query("SELECT id_negocio, nombre_negocio FROM j102_negocios ORDER BY nombre_negocio ASC");

// Construir consulta principal
$sql = "SELECT 
            r.id_resena, r.puntuacion, r.comentario, r.fecha_hora, r.activo,
            c.nombre_completo AS nombre_cliente,
            s.nombre_servicio,
            n.nombre_negocio
        FROM j112_resenas r
        JOIN j106_clientes c ON r.id_cliente = c.id_cliente
        JOIN j102_negocios n ON r.id_negocio = n.id_negocio
        LEFT JOIN j104_servicios s ON r.id_servicio = s.id_servicio";

$params = [];
$types = "";
if ($id_negocio_filtro > 0) {
    $sql .= " WHERE r.id_negocio = ?";
    $params[] = $id_negocio_filtro;
    $types .= "i";
}
$sql .= " ORDER BY r.fecha_hora DESC";

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resenas_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Reseñas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body style="background-color: <?php echo $daily_bg_color; ?>;">
    <?php include 'navbar.php'; ?>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Gestión de Reseñas</h1>
        </div>

        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-<?php echo $_GET['status'] == 'success' ? 'success' : 'danger'; ?>">
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <form method="GET" action="resenas_lista.php" class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <label for="id_negocio" class="form-label">Filtrar por Negocio</label>
                        <select name="id_negocio" id="id_negocio" class="form-select">
                            <option value="0">Todos los Negocios</option>
                            <?php while ($negocio = $negocios->fetch_assoc()): ?>
                                <option value="<?php echo $negocio['id_negocio']; ?>" <?php echo ($id_negocio_filtro == $negocio['id_negocio']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($negocio['nombre_negocio']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Puntuación</th>
                                <th>Comentario</th>
                                <th>Cliente</th>
                                <th>Servicio</th>
                                <th>Negocio</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($resenas_result->num_rows > 0): ?>
                                <?php while ($resena = $resenas_result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="text-nowrap"><span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> <?php echo number_format($resena['puntuacion'], 1); ?></span></td>
                                        <td><?php echo nl2br(htmlspecialchars($resena['comentario'])); ?></td>
                                        <td><?php echo htmlspecialchars($resena['nombre_cliente']); ?></td>
                                        <td><?php echo htmlspecialchars($resena['nombre_servicio'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($resena['nombre_negocio']); ?></td>
                                        <td class="text-nowrap"><?php echo date('d/m/Y H:i', strtotime($resena['fecha_hora'])); ?></td>
                                        <td><span class="badge <?php echo $resena['activo'] ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $resena['activo'] ? 'Activa' : 'Inactiva'; ?></span></td>
                                        <td>
                                            <a href="resenas_editar.php?id=<?php echo $resena['id_resena']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                            <form action="resenas_procesar_eliminar.php" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que quieres eliminar esta reseña?');"><input type="hidden" name="id_resena" value="<?php echo $resena['id_resena']; ?>"><button type="submit" class="btn btn-sm btn-danger">Eliminar</button></form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center">No hay reseñas que coincidan con el filtro.</td></tr>
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