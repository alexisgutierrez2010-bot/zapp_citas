<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Aplicada la internacionalización (i18n).
require_once 'auth_check.php';
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('categories_create_new'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: <?php echo $daily_bg_color; ?>;">
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo __('categories_create_new'); ?></h3>
                    </div>
                    <div class="card-body">
                        <form action="categorias_procesar_crear.php" method="POST">
                            <div class="mb-3">
                                <label for="nombre_categoria" class="form-label"><?php echo __('categories_col_name'); ?></label>
                                <input type="text" class="form-control" id="nombre_categoria" name="nombre_categoria" required>
                            </div>
                            <div class="mb-3">
                                <label for="descripcion" class="form-label"><?php echo __('categories_col_desc'); ?></label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" checked>
                                <label class="form-check-label" for="activo"><?php echo __('categories_form_active'); ?></label>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="categorias_lista.php?lang=<?php echo $lang; ?>" class="btn btn-secondary"><?php echo __('cancel'); ?></a>
                                <button type="submit" class="btn btn-primary"><?php echo __('services_form_save'); ?></button>
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