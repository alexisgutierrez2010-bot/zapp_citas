<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
require_once 'config.php';
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Clave</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container vh-100 d-flex justify-content-center align-items-center">
        <div class="card" style="width: 24rem;">
            <div class="card-header text-center">
                <h3>Recuperar Clave</h3>
            </div>
            <div class="card-body">
                <?php
                if (isset($_GET['status'])) {
                    $status_type = $_GET['status'] == 'success' ? 'success' : 'danger';
                    $message = htmlspecialchars($_GET['message']);
                    echo "<div class='alert alert-{$status_type}'>{$message}</div>";
                }
                ?>
                <p class="card-text text-muted">Ingresa tu correo electrónico y te enviaremos una nueva contraseña temporal si la cuenta existe.</p>
                <form action="procesar_olvide_clave.php" method="post">
                    <div class="mb-3">
                        <label for="correo_electronico" class="form-label">Correo Electrónico</label>
                        <input type="email" name="correo_electronico" class="form-control" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Enviar</button>
                        <a href="sesion_iniciar.php" class="btn btn-secondary">Volver al Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>    
    <?php include 'footer.php'; ?>
</body>
</html>