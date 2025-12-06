<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
require_once 'auth_check.php';
require_once 'config.php';

// 1. Verificar que se ha proporcionado un ID válido
$id_servicio = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_servicio <= 0) {
    header("Location: servicios_lista.php?status=error&message=" . urlencode("ID de servicio no válido."));
    exit();
}

// 2. Obtener los datos actuales del servicio
$sql = "SELECT * FROM j104_servicios WHERE id_servicio = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $id_servicio);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $servicio = $result->fetch_assoc();
    } else {
        header("Location: servicios_lista.php?status=error&message=" . urlencode("Servicio no encontrado."));
        exit();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Servicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3>Editando Servicio: <?php echo htmlspecialchars($servicio['nombre_servicio']); ?></h3>
                    </div>
                    <div class="card-body">
                        <form action="servicios_actualizar.php" method="POST">
                            <input type="hidden" name="id_servicio" value="<?php echo $servicio['id_servicio']; ?>">

                            <div class="mb-3">
                                <label for="nombre_servicio" class="form-label">Nombre del Servicio</label>
                                <input type="text" class="form-control" id="nombre_servicio" name="nombre_servicio" value="<?php echo htmlspecialchars($servicio['nombre_servicio']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Duración</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="duracion_valor" name="duracion_valor" value="<?php echo htmlspecialchars($servicio['duracion_valor']); ?>" required>
                                    <select class="form-select" name="duracion_unidad">
                                        <option value="Minutos" <?php echo ($servicio['duracion_unidad'] == 'Minutos') ? 'selected' : ''; ?>>Minutos</option>
                                        <option value="Horas" <?php echo ($servicio['duracion_unidad'] == 'Horas') ? 'selected' : ''; ?>>Horas</option>
                                        <option value="Dias" <?php echo ($servicio['duracion_unidad'] == 'Dias') ? 'selected' : ''; ?>>Días</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="precio" class="form-label">Precio (opcional)</label>
                                <input type="number" step="0.01" class="form-control" id="precio" name="precio" value="<?php echo htmlspecialchars($servicio['precio']); ?>">
                            </div>
                            <hr>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" <?php echo ($servicio['activo'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="activo">Servicio Activo</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Actualizar Servicio</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'footer.php'; ?>
</body>
</html>