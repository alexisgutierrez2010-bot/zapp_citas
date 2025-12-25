<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
require_once 'auth_check.php';
require_once 'config.php';

$id_resena = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_resena <= 0) {
    header("Location: resenas_lista.php?status=error&message=ID de reseña no válido.");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM j112_resenas WHERE id_resena = ?");
$stmt->bind_param("i", $id_resena);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    header("Location: resenas_lista.php?status=error&message=Reseña no encontrada.");
    exit();
}
$resena = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Reseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: <?php echo $daily_bg_color; ?>;">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><h3>Editar Reseña</h3></div>
                    <div class="card-body">
                        <form action="resenas_procesar_actualizar.php" method="POST">
                            <input type="hidden" name="id_resena" value="<?php echo $resena['id_resena']; ?>">
                            <div class="mb-3">
                                <label for="puntuacion" class="form-label">Puntuación (de 1.0 a 5.0)</label>
                                <input type="number" class="form-control" id="puntuacion" name="puntuacion" step="0.1" min="1.0" max="5.0" value="<?php echo htmlspecialchars($resena['puntuacion']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="comentario" class="form-label">Comentario</label>
                                <textarea class="form-control" id="comentario" name="comentario" rows="5"><?php echo htmlspecialchars($resena['comentario']); ?></textarea>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" <?php echo ($resena['activo'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="activo">Reseña Activa (Visible)</label>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="resenas_lista.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
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