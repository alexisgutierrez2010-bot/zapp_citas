<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Aplicada la internacionalización (i18n).

// Cargar sistema de internacionalización (i18n)
session_start();

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container vh-100 d-flex justify-content-center align-items-center">
        <div class="card" style="width: 24rem;">
            <div class="card-header text-center">
                <h3>Recuperar Contraseña</h3>
            </div>
            <div class="card-body">
                <?php
                if (isset($_GET['message_key'])) {
                    $message = htmlspecialchars($_GET['message_key']); // Mostrar clave directamente
                    $alert_type = strpos($_GET['status'] ?? '', 'error') === false ? 'success' : 'danger';
                    echo "<div class='alert alert-{$alert_type}'>" . htmlspecialchars($message) . "</div>";
                } elseif (isset($_GET['message'])) { // Fallback
                    $alert_type = strpos($_GET['status'] ?? '', 'error') === false ? 'success' : 'danger';
                    echo "<div class='alert alert-{$alert_type}'>" . htmlspecialchars($_GET['message']) . "</div>";
                }
                ?>
                <p class="card-text text-muted">Ingresa tu correo electrónico y te enviaremos una nueva contraseña temporal si la cuenta existe.</p>
                <form action="procesar_olvide_clave.php" method="post">
                    <div class="mb-3">
                        <label for="correo_electronico" class="form-label">Correo Electrónico</label>
                        <input type="email" name="correo_electronico" class="form-control" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Enviar Instrucciones</button>
                        <a href="sesion_iniciar.php" class="btn btn-secondary">Volver al Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>    
    <?php include 'footer.php'; ?>
</body>
</html>