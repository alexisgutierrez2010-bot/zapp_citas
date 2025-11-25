<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
require_once 'Auth_check.php';
require_once 'config.php';

$id_estado = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_estado <= 0) {
    header("Location: paises_lista.php?status=error&message=" . urlencode("ID de estado no válido."));
    exit();
}

$stmt = $conn->prepare("SELECT * FROM j111_estados WHERE id_estado = ?");
$stmt->bind_param("i", $id_estado);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    header("Location: paises_lista.php?status=error&message=" . urlencode("Estado no encontrado."));
    exit();
}
$estado = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Estado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h3>Editando Estado: <?php echo htmlspecialchars($estado['nombre_estado']); ?></h3></div>
                    <div class="card-body">
                        <form action="estados_actualizar.php" method="POST">
                            <input type="hidden" name="id_estado" value="<?php echo $estado['id_estado']; ?>">
                            <input type="hidden" name="id_pais" value="<?php echo $estado['id_pais']; ?>">
                            <div class="mb-3">
                                <label for="nombre_estado" class="form-label">Nombre del Estado</label>
                                <input type="text" class="form-control" id="nombre_estado" name="nombre_estado" value="<?php echo htmlspecialchars($estado['nombre_estado']); ?>" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success">Actualizar Estado</button>
                                <a href="estados_lista.php?id_pais=<?php echo $estado['id_pais']; ?>" class="btn btn-secondary">Cancelar</a>
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