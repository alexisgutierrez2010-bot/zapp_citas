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

$id_resena = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_resena <= 0) {
    header("Location: resenas_lista.php?status=error&message=" . urlencode("ID de reseña no válido."));
    exit();
}

$stmt = $conn->prepare("SELECT r.*, c.nombre_completo, n.nombre_negocio FROM j112_resenas r JOIN j106_clientes c ON r.id_cliente = c.id_cliente JOIN j102_negocios n ON r.id_negocio = n.id_negocio WHERE r.id_resena = ?");
$stmt->bind_param("i", $id_resena);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    header("Location: resenas_lista.php?status=error&message=" . urlencode("Reseña no encontrada."));
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
                    <div class="card-header"><h3>Editando Reseña de <?php echo htmlspecialchars($resena['nombre_completo']); ?></h3></div>
                    <div class="card-body">
                        <?php if (isset($_GET['message'])): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars(urldecode($_GET['message'])); ?></div>
                        <?php endif; ?>
                        <form action="resenas_procesar_actualizar.php" method="POST">
                            <input type="hidden" name="id_resena" value="<?php echo $resena['id_resena']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Cliente</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($resena['nombre_completo']); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Negocio</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($resena['nombre_negocio']); ?>" readonly>
                            </div>
                             <div class="mb-3">
                                <label for="puntuacion" class="form-label">Puntuación</label>
                                <input type="number" class="form-control" id="puntuacion" name="puntuacion" min="1" max="5" step="0.1" value="<?php echo htmlspecialchars($resena['puntuacion']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="comentario" class="form-label">Comentario</label>
                                <textarea class="form-control" id="comentario" name="comentario" rows="4" required><?php echo htmlspecialchars($resena['comentario']); ?></textarea>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" <?php echo ($resena['activo'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="activo">Reseña Activa (Visible para clientes)</label>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="resenas_lista.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Actualizar Reseña</button>
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