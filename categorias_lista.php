<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Aplicada la internacionalización (i18n).
require_once 'auth_check.php';
require_once 'config.php';

$categorias = [];
$result = $conn->query("SELECT * FROM j103_categorias ORDER BY nombre_categoria ASC");
while ($row = $result->fetch_assoc()) {
    $categorias[] = $row;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('categories_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1><?php echo __('categories_list_title'); ?></h1>
            <a href="categoria_crear.php?lang=<?php echo $lang; ?>" class="btn btn-primary"><?php echo __('categories_create_new'); ?></a>
        </div>

        <?php
        if (isset($_GET['status']) || isset($_GET['message_key'])) {
            $status = $_GET['status'] ?? '';
            $message_key = $_GET['message_key'] ?? '';
            $message = '';

            if (!empty($message_key)) {
                $message = __($message_key);
            } elseif ($status === 'success') {
                $message = __('operation_success');
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
                            <th><?php echo __('categories_col_name'); ?></th>
                            <th><?php echo __('categories_col_desc'); ?></th>
                            <th><?php echo __('status'); ?></th>
                            <th><?php echo __('actions'); ?></th>
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
                                    <?php echo $categoria['activo'] ? __('active') : __('inactive'); ?>
                                </span>
                            </td>
                            <td>
                                <a href="categoria_editar.php?id=<?php echo $categoria['id_categoria']; ?>&lang=<?php echo $lang; ?>" class="btn btn-sm btn-warning"><?php echo __('edit'); ?></a>
                                <form action="categoria_eliminar.php" method="POST" class="d-inline" onsubmit="return confirm('<?php echo __('categories_confirm_delete'); ?>');">
                                    <input type="hidden" name="id_categoria" value="<?php echo $categoria['id_categoria']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><?php echo __('delete'); ?></button>
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