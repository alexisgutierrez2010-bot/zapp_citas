<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
require_once 'auth_check.php';
require_once 'config.php';

// --- LÓGICA DE ROLES Y FILTRADO ---
$es_administrador = (isset($rol_session) && strcasecmp(trim($rol_session), 'Administrador') == 0);

if (!$es_administrador) {
    header("Location: dashboard.php?status=error&message=" . urlencode("Acceso no autorizado."));
    exit;
}

// Obtener todos los negocios para la lista
$todos_los_negocios = [];
$result_todos_negocios = $conn->query("SELECT id_negocio, nombre_negocio, telefono, email, activo FROM j102_negocios ORDER BY nombre_negocio ASC");
if ($result_todos_negocios) {
    while ($row = $result_todos_negocios->fetch_assoc()) {
        $todos_los_negocios[] = $row;
    }
}
?>
<!DOCTYPE html><html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Negocios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: <?php echo $daily_bg_color; ?>;">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        Lista de Negocios
                        <span class="badge bg-secondary fs-6"><?php echo htmlspecialchars($rol_session ?? 'Desconocido'); ?></span>
                    </h3>
                    <a href="negocios_nuevo.php" class="btn btn-primary">Crear Nuevo Negocio</a>
                </div>
            </div>
            <div class="card-body">
                <?php if (isset($_GET['message'])): ?>
                    <div class="alert alert-<?php echo $_GET['status'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars(urldecode($_GET['message'])); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Nombre del Negocio</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $estados_negocio = [
                                1 => ['texto' => 'Activo', 'clase' => 'success'],
                                2 => ['texto' => 'Suspendido', 'clase' => 'warning'],
                                3 => ['texto' => 'Eliminado', 'clase' => 'danger'],
                                4 => ['texto' => 'Pendiente', 'clase' => 'info']
                            ];
                            $i = 1;
                            foreach ($todos_los_negocios as $negocio):
                                $estado_info = $estados_negocio[$negocio['activo']] ?? ['texto' => 'Desconocido', 'clase' => 'secondary'];
                            ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($negocio['nombre_negocio']); ?></td>
                                    <td><?php echo htmlspecialchars($negocio['telefono']); ?></td>
                                    <td><?php echo htmlspecialchars($negocio['email']); ?></td>
                                    <td><span class="badge bg-<?php echo $estado_info['clase']; ?>"><?php echo $estado_info['texto']; ?></span></td>
                                    <td>
                                        <a href="negocios_editar.php?id=<?php echo $negocio['id_negocio']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                        <form action="negocios_eliminar.php" method="POST" class="d-inline" onsubmit="return confirm('¡ADVERTENCIA! Desactivar este negocio impedirá el acceso a sus propietarios. ¿Está seguro?');">
                                            <input type="hidden" name="id_negocio" value="<?php echo $negocio['id_negocio']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" <?php echo ($negocio['id_negocio'] == 1) ? 'disabled' : ''; ?>>Desactivar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
