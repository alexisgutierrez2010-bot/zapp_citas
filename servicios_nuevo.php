<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
require_once 'auth_check.php';
require_once 'config.php';

// Solo el rol Administrador puede acceder a esta página.
if (strcasecmp(trim($rol_session ?? ''), 'Administrador') != 0) {
    header("Location: dashboard.php?status=error&message=" . urlencode("Acceso no autorizado."));
    exit;
}

// Obtener la lista de negocios para el selector
$todos_los_negocios = [];
$result_todos_negocios = $conn->query("SELECT id_negocio, nombre_negocio FROM j102_negocios WHERE activo = 1 ORDER BY nombre_negocio ASC");
if ($result_todos_negocios) {
    while ($row = $result_todos_negocios->fetch_assoc()) {
        $todos_los_negocios[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Nuevo Servicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: <?php echo $daily_bg_color; ?>;">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Registrar Nuevo Servicio</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['status'])): ?>
                            <div class="alert alert-<?php echo $_GET['status'] == 'success' ? 'success' : 'danger'; ?>">
                                <?php echo htmlspecialchars($_GET['message']); ?>
                            </div>
                        <?php endif; ?>
                        <form action="servicios_crear.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="nombre_servicio" class="form-label">Nombre del Servicio</label>
                                <input type="text" class="form-control" id="nombre_servicio" name="nombre_servicio" required>
                            </div>
                            <div class="mb-3">
                                <label for="id_negocio" class="form-label">Asignar al Negocio</label>
                                <select name="id_negocio" id="id_negocio" class="form-select" required>
                                    <option value="">-- Seleccione un Negocio --</option>
                                    <?php foreach ($todos_los_negocios as $negocio): ?>
                                        <option value="<?php echo $negocio['id_negocio']; ?>">
                                            <?php echo htmlspecialchars($negocio['nombre_negocio']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="foto_servicio" class="form-label">Foto del Servicio (Opcional)</label>
                                <input class="form-control" type="file" id="foto_servicio" name="foto_servicio" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Duración</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="duracion_valor" value="30" required>
                                    <select class="form-select" name="duracion_unidad">
                                        <option value="Minutos" selected>Minutos</option>
                                        <option value="Horas">Horas</option>
                                        <option value="Dias">Días</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="precio" class="form-label">Precio (opcional)</label>
                                <input type="number" step="0.01" class="form-control" id="precio" name="precio">
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="servicios_lista.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Servicio</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>