<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
require_once 'Auth_check.php';
require_once 'config.php';

$id_pais = isset($_GET['id_pais']) ? (int)$_GET['id_pais'] : 0;
if ($id_pais <= 0) {
    header("Location: paises_lista.php?status=error&message=" . urlencode("País no especificado."));
    exit();
}

// Obtener nombre del país para mostrarlo
$stmt_pais = $conn->prepare("SELECT nombre_pais FROM j110_paises WHERE id_pais = ?");
$stmt_pais->bind_param("i", $id_pais);
$stmt_pais->execute();
$pais = $stmt_pais->get_result()->fetch_assoc();
$stmt_pais->close();
if (!$pais) {
    header("Location: paises_lista.php?status=error&message=" . urlencode("País no encontrado."));
    exit();
}

$estados_result = $conn->query("SELECT * FROM j111_estados WHERE id_pais = $id_pais ORDER BY nombre_estado ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Estados para <?php echo htmlspecialchars($pais['nombre_pais']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <a href="paises_lista.php" class="btn btn-secondary mb-3">← Volver a Países</a>
        <h2>Gestión de Estados para: <strong><?php echo htmlspecialchars($pais['nombre_pais']); ?></strong></h2>

        <div class="row mt-4">
            <!-- Formulario para agregar estado -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h4>Registrar Nuevo Estado</h4></div>
                    <div class="card-body">
                        <?php
                        if (isset($_GET['status'])) {
                            $status_type = strpos($_GET['status'], 'success') !== false ? 'success' : 'danger';
                            $message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Acción completada.';
                            echo "<div class='alert alert-{$status_type}'>{$message}</div>";
                        }
                        ?>
                        <form action="estados_crear.php" method="POST">
                            <input type="hidden" name="id_pais" value="<?php echo $id_pais; ?>">
                            <div class="mb-3">
                                <label for="nombre_estado" class="form-label">Nombre del Estado / Provincia</label>
                                <input type="text" class="form-control" id="nombre_estado" name="nombre_estado" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Guardar Estado</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lista de estados -->
            <div class="col-md-8">
                <h4>Lista de Estados</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Nombre del Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 1;
                            if ($estados_result->num_rows > 0):
                                while($estado = $estados_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo htmlspecialchars($estado['nombre_estado']); ?></td>
                                        <td>
                                            <a href="estados_editar.php?id=<?php echo $estado['id_estado']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                            <form action="estados_eliminar.php" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro?');">
                                                <input type="hidden" name="id_estado" value="<?php echo $estado['id_estado']; ?>">
                                                <input type="hidden" name="id_pais" value="<?php echo $id_pais; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr><td colspan="3" class="text-center">No hay estados registrados para este país.</td></tr>
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