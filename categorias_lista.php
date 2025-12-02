<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-27-2025).
require_once 'auth_check.php';
require_once 'config.php';

$categorias = [];
$result = $conn->query("SELECT * FROM j103_categorias ORDER BY nombre_categoria ASC");
while ($row = $result->fetch_assoc()) {
    $categorias[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Categorías</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Gestión de Categorías de Negocio</h1>
            <a href="categoria_crear.php" class="btn btn-primary">Crear Nueva Categoría</a>
        </div>

        <?php
        if (isset($_GET['status'])) {
            if ($_GET['status'] == 'success') {
                echo '<div class="alert alert-success">Operación realizada con éxito.</div>';
            } elseif ($_GET['status'] == 'error') {
                $errorMessage = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Ocurrió un error.';
                echo '<div class="alert alert-danger">' . $errorMessage . '</div>';
            }
        }
        ?>

        <div class="card">
            <div class="card-body">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre de Categoría</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $categoria): ?>
                        <tr>
                            <td><?php echo $categoria['id_categoria']; ?></td>
                            <td><?php echo htmlspecialchars($categoria['nombre_categoria']); ?></td>
                            <td><?php echo htmlspecialchars($categoria['descripcion']); ?></td>
                            <td>
                                <span class="badge <?php echo $categoria['activo'] ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $categoria['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="categoria_editar.php?id=<?php echo $categoria['id_categoria']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                <form action="categoria_eliminar.php" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta categoría?');">
                                    <input type="hidden" name="id_categoria" value="<?php echo $categoria['id_categoria']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>