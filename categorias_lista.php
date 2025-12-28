<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
require_once 'auth_check.php';
require_once 'config.php';

$categorias = [];
$result = $conn->query("SELECT *, DATE_FORMAT(fecha_registro, '%d/%m/%Y') AS fecha_registro_formateada FROM j103_categorias ORDER BY nombre_categoria ASC");
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
            <h1>Gestión de Categorías</h1>
            <a href="categoria_crear.php" class="btn btn-primary">Crear Nueva Categoría</a>
        </div>

        <?php
        if (isset($_GET['status']) || isset($_GET['message_key'])) {
            $status = $_GET['status'] ?? '';
            $message_key = $_GET['message_key'] ?? '';
            $message = '';

            if (!empty($message_key)) {
                $message = htmlspecialchars($message_key);
            } elseif ($status === 'success') {
                $message = 'Operación realizada con éxito.';
            }
            if (!empty($message)) {
                $alert_type = strpos($status, 'error') === false ? 'success' : 'danger';
                echo "<div class='alert alert-{$alert_type}'>" . htmlspecialchars($message) . "</div>";
            }
        }
        ?>

        <div class="card">
            <div class="card-body">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Registro</th>
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
                                    <?php echo $categoria['activo'] ? 'Activa' : 'Inactiva'; ?>
                                </span>
                            </td>
                            <td><?php echo $categoria['fecha_registro_formateada'] ?? 'N/A'; ?></td>
                            </td>
                            <td>
                                <a href="categoria_editar.php?id=<?php echo $categoria['id_categoria']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                <form action="categoria_eliminar.php" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que quieres eliminar esta categoría?');">
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