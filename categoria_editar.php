<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
require_once 'auth_check.php';
require_once 'config.php';

$id_categoria = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_categoria <= 0) {
    header("Location: categorias_lista.php?status=error&message=" . urlencode("ID de categoría no válido."));
    exit();
}

$stmt = $conn->prepare("SELECT * FROM j103_categorias WHERE id_categoria = ?");
$stmt->bind_param("i", $id_categoria);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    header("Location: categorias_lista.php?status=error&message=" . urlencode("Categoría no encontrada."));
    exit();
}
$categoria = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Categoría</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: <?php echo $daily_bg_color; ?>;">
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h3>Editando: <?php echo htmlspecialchars($categoria['nombre_categoria']); ?></h3></div>
                    <div class="card-body">
                        <form action="categoria_actualizar.php" method="POST">
                            <input type="hidden" name="id_categoria" value="<?php echo $categoria['id_categoria']; ?>">
                            <div class="mb-3">
                                <label for="nombre_categoria" class="form-label">Nombre de la Categoría</label>
                                <input type="text" class="form-control" id="nombre_categoria" name="nombre_categoria" value="<?php echo htmlspecialchars($categoria['nombre_categoria']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($categoria['descripcion']); ?></textarea>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" <?php echo ($categoria['activo'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="activo">Categoría Activa</label>
                            </div>
                            <div class="mb-3">
                                <label for="fecha_registro" class="form-label">Fecha de Registro</label>
                                <input type="text" class="form-control" id="fecha_registro" value="<?php echo !empty($categoria['fecha_registro']) ? date('d/m/Y H:i', strtotime($categoria['fecha_registro'])) : 'No registrada'; ?>" readonly>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="categorias_lista.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Actualizar Categoría</button>
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