<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Aplicada la internacionalización (i18n).
require_once 'auth_check.php';

// Solo un Master puede crear nuevos negocios
/*
if ($rol_session != 'Master') {
    header("Location: dashboard.php?status=error&message=" . urlencode("No tienes permiso para esta acción."));
    exit;
}
*/
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('businesses_create_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo __('businesses_create_title'); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php
                        if (isset($_GET['message_key'])) {
                            $message = __($_GET['message_key']);
                            echo "<div class='alert alert-danger'>" . htmlspecialchars($message) . "</div>";
                        }
                        ?>
                        <form action="negocios_crear.php" method="POST">
                            <p class="text-muted"><?php echo __('businesses_create_instructions'); ?></p>
                            <div class="mb-3">
                                <label for="nombre_negocio" class="form-label"><?php echo __('businesses_form_name'); ?></label>
                                <input type="text" class="form-control" id="nombre_negocio" name="nombre_negocio" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label"><?php echo __('businesses_form_email'); ?></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <hr>
                            <h5 class="mt-3"><?php echo __('businesses_create_owner_title'); ?></h5>
                            <p class="text-muted"><?php echo __('businesses_create_owner_instructions'); ?></p>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="admin_user" class="form-label"><?php echo __('businesses_create_owner_user'); ?></label>
                                    <input type="text" class="form-control" id="admin_user" name="admin_user" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="admin_pass" class="form-label"><?php echo __('businesses_create_owner_password'); ?></label>
                                    <input type="password" class="form-control" id="admin_pass" name="admin_pass" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="admin_email" class="form-label"><?php echo __('businesses_create_owner_email'); ?></label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary"><?php echo __('businesses_create_button'); ?></button>
                                <a href="negocios_configuracion.php?lang=<?php echo $lang; ?>" class="btn btn-secondary"><?php echo __('cancel'); ?></a>
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