<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
require_once 'Auth_check.php';
require_once 'config.php';

$nombre_archivo = isset($_GET['file']) ? basename($_GET['file']) : '';

// Validaciones de seguridad
$allowed_extensions = ['pdf', 'sql'];
if (empty($nombre_archivo) || !in_array(strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION)), $allowed_extensions) || !file_exists(__DIR__ . '/documentos/' . $nombre_archivo)) {
    header("Location: seleccionar_resumen.php?status=error&message=" . urlencode("Archivo no válido o no encontrado."));
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('documents_send_pdf_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo __('documents_send_pdf_title'); ?></h3>
                    </div>
                    <div class="card-body">
                        <p><?php echo __('documents_send_pdf_about_to_send'); ?></p>
                        <div class="alert alert-info">
                            <strong><?php echo __('documents_send_pdf_file'); ?>:</strong> <?php echo htmlspecialchars($nombre_archivo); ?>
                        </div>

                        <form action="enviar_resumen.php" method="POST">
                            <input type="hidden" name="file" value="<?php echo htmlspecialchars($nombre_archivo); ?>">
                            
                            <div class="mb-3">
                                <label for="email_to" class="form-label"><strong><?php echo __('documents_send_pdf_to'); ?></strong></label>
                                <input type="email" class="form-control" id="email_to" name="email_to" value="alexisgutierrez2010@gmail.com" required>
                            </div>

                            <div class="mb-3">
                                <label for="subject" class="form-label"><?php echo __('documents_send_pdf_subject'); ?></label>
                                <input type="text" class="form-control" id="subject" name="subject" value="Envío de Documento: <?php echo htmlspecialchars($nombre_archivo); ?>" required>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="seleccionar_resumen.php?lang=<?php echo $lang; ?>" class="btn btn-secondary"><?php echo __('cancel'); ?></a>
                                <button type="submit" class="btn btn-primary">
                                    <?php echo __('documents_send_pdf_button'); ?>
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