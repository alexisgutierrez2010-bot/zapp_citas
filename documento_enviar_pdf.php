<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
require_once 'Auth_check.php';
require_once 'config.php';

$nombre_archivo = isset($_GET['file']) ? basename($_GET['file']) : '';

// Validaciones de seguridad
if (empty($nombre_archivo) || strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION)) !== 'pdf' || !file_exists(__DIR__ . '/documentos/' . $nombre_archivo)) {
    header("Location: seleccionar_resumen.php?status=error&message=" . urlencode("Archivo PDF no válido o no encontrado."));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar Documento PDF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3>Enviar Documento PDF</h3>
                    </div>
                    <div class="card-body">
                        <p>Estás a punto de enviar el siguiente documento como archivo adjunto:</p>
                        <div class="alert alert-info">
                            <strong>Archivo:</strong> <?php echo htmlspecialchars($nombre_archivo); ?>
                        </div>

                        <form action="enviar_resumen.php" method="POST">
                            <input type="hidden" name="file" value="<?php echo htmlspecialchars($nombre_archivo); ?>">
                            
                            <div class="mb-3">
                                <label for="email_to" class="form-label"><strong>Correo del Destinatario (TO):</strong></label>
                                <input type="email" class="form-control" id="email_to" name="email_to" value="alexisgutierrez2010@gmail.com" required>
                            </div>

                            <div class="mb-3">
                                <label for="subject" class="form-label">Asunto del Correo:</label>
                                <input type="text" class="form-control" id="subject" name="subject" value="Envío de Documento: <?php echo htmlspecialchars($nombre_archivo); ?>" required>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="seleccionar_resumen.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">
                                    Enviar Correo con Adjunto
                                </button>
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