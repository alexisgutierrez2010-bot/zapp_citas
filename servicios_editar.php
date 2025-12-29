<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
require_once 'auth_check.php';
require_once 'config.php';

$id_servicio = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_servicio <= 0) {
    header("Location: servicios_lista.php?status=error&message=ID de servicio no válido.");
    exit();
}

// Determinar si es Administrador
$es_administrador = (isset($rol_session) && strcasecmp(trim($rol_session), 'Administrador') == 0);

// SOLUCIÓN: No seleccionar el campo BLOB 'foto_servicio', en su lugar, verificar si existe para evitar errores de memoria.
$sql = "SELECT id_servicio, nombre_servicio, duracion_valor, duracion_unidad, precio, activo, id_negocio, (foto_servicio IS NOT NULL AND LENGTH(foto_servicio) > 0) as tiene_foto FROM j104_servicios WHERE id_servicio = ?";
$params = [$id_servicio];
$types = "i";

// Si no es administrador, debe pertenecer a su negocio.
if (!$es_administrador) {
    $sql .= " AND id_negocio = ?";
    $params[] = $id_negocio_session;
    $types .= "i";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    header("Location: servicios_lista.php?status=error&message=Servicio no encontrado o no tienes permiso para editarlo.");
    exit();
}
$servicio = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Servicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: <?php echo $daily_bg_color; ?>;">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h3>Editando: <?php echo htmlspecialchars($servicio['nombre_servicio']); ?></h3></div>
                    <div class="card-body">
                        <form action="servicios_procesar_actualizar.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id_servicio" value="<?php echo $servicio['id_servicio']; ?>">
                            <div class="mb-3"><label for="nombre_servicio" class="form-label">Nombre del Servicio</label><input type="text" class="form-control" id="nombre_servicio" name="nombre_servicio" value="<?php echo htmlspecialchars($servicio['nombre_servicio']); ?>" required></div>
                            <div class="mb-3">
                                <label for="foto_servicio" class="form-label">Cambiar Foto (Opcional)</label>
                                <input class="form-control" type="file" id="foto_servicio" name="foto_servicio" accept="image/*">
                                <?php if (!empty($servicio['tiene_foto'])): ?>
                                    <div class="mt-2">
                                        <small>Foto Actual:</small><br>
                                        <img src="api_get_service_image.php?id=<?php echo $servicio['id_servicio']; ?>" alt="Foto actual" class="img-thumbnail" style="max-height: 100px;">
                                        <div class="form-check mt-1">
                                            <input class="form-check-input" type="checkbox" name="eliminar_foto" id="eliminar_foto" value="1">
                                            <label class="form-check-label" for="eliminar_foto">Eliminar foto actual</label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3"><label class="form-label">Duración</label><div class="input-group"><input type="number" class="form-control" name="duracion_valor" value="<?php echo htmlspecialchars($servicio['duracion_valor']); ?>" required><select class="form-select" name="duracion_unidad"><option value="Minutos" <?php echo $servicio['duracion_unidad'] == 'Minutos' ? 'selected' : ''; ?>>Minutos</option><option value="Horas" <?php echo $servicio['duracion_unidad'] == 'Horas' ? 'selected' : ''; ?>>Horas</option><option value="Dias" <?php echo $servicio['duracion_unidad'] == 'Dias' ? 'selected' : ''; ?>>Días</option></select></div></div>
                            <div class="mb-3"><label for="precio" class="form-label">Precio (opcional)</label><input type="number" step="0.01" class="form-control" id="precio" name="precio" value="<?php echo htmlspecialchars($servicio['precio']); ?>"></div>
                            <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" <?php echo ($servicio['activo'] ?? 0) ? 'checked' : ''; ?>><label class="form-check-label" for="activo">Servicio Activo</label></div>
                            <div class="d-flex justify-content-between">
                                <a href="servicios_lista.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Actualizar Servicio</button>
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