<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Aplicada la internacionalización (i18n).

// Cargar sistema de internacionalización (i18n)
session_start(); // Necesario para que el selector de idioma funcione
require_once __DIR__ . '/languages.php';

?><!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo __('forgot_password_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container vh-100 d-flex justify-content-center align-items-center">
        <div class="card" style="width: 24rem;">
            <div class="card-header text-center">
                <h3><?php echo __('forgot_password_title'); ?></h3>
            </div>
            <div class="card-body">
                <?php
                if (isset($_GET['message_key'])) {
                    $message = __($_GET['message_key']);
                    $alert_type = strpos($_GET['status'] ?? '', 'error') === false ? 'success' : 'danger';
                    echo "<div class='alert alert-{$alert_type}'>" . htmlspecialchars($message) . "</div>";
                } elseif (isset($_GET['message'])) { // Fallback
                    $alert_type = strpos($_GET['status'] ?? '', 'error') === false ? 'success' : 'danger';
                    echo "<div class='alert alert-{$alert_type}'>" . htmlspecialchars($_GET['message']) . "</div>";
                }
                ?>
                <p class="card-text text-muted"><?php echo __('forgot_password_instructions'); ?></p>
                <form action="procesar_olvide_clave.php" method="post">
                    <input type="hidden" name="lang" value="<?php echo $lang; ?>">
                    <div class="mb-3">
                        <label for="correo_electronico" class="form-label"><?php echo __('users_form_email'); ?></label>
                        <input type="email" name="correo_electronico" class="form-control" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary"><?php echo __('forgot_password_button'); ?></button>
                        <a href="sesion_iniciar.php?lang=<?php echo $lang; ?>" class="btn btn-secondary"><?php echo __('forgot_password_back_to_login'); ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>    
    <?php include 'footer.php'; ?>
</body>
</html>