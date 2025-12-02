<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-27-2025).
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

$documentos_dir = __DIR__ . '/documentos/';
$nombre_archivo = isset($_GET['file']) ? basename($_GET['file']) : '';

// Validaciones de seguridad
if (empty($nombre_archivo) || pathinfo($nombre_archivo, PATHINFO_EXTENSION) !== 'md' || !file_exists($documentos_dir . $nombre_archivo)) {
    header("Location: seleccionar_resumen.php?status=error&message=" . urlencode("Archivo Markdown no válido o no encontrado."));
    exit;
}

$file_path = $documentos_dir . $nombre_archivo;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_contenido = $_POST['contenido'];

    if (file_put_contents($file_path, $nuevo_contenido) === false) {
        header("Location: documento_editar_md.php?file={$nombre_archivo}&status=error&message=" . urlencode("No se pudo guardar el archivo."));
        exit;
    }

    registrar_auditoria($conn, $_SESSION['id_usuario'], null, 'DOC_EDIT_MD', "Se editó el documento Markdown {$nombre_archivo}.");
    header("Location: seleccionar_resumen.php?status=success&message=" . urlencode("Documento '{$nombre_archivo}' actualizado con éxito."));
    exit;
}

$contenido_actual = file_get_contents($file_path);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Documento Markdown</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h3>Editando: <?php echo htmlspecialchars($nombre_archivo); ?></h3>
            </div>
            <div class="card-body">
                <form action="documento_editar_md.php?file=<?php echo urlencode($nombre_archivo); ?>" method="POST">
                    <div class="mb-3">
                        <textarea name="contenido" class="form-control" rows="20" style="font-family: monospace;"><?php echo htmlspecialchars($contenido_actual); ?></textarea>
                    </div>
                    <div class="d-flex justify-content-end">
                        <a href="seleccionar_resumen.php" class="btn btn-secondary me-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>