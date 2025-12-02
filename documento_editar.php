<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

$documentos_dir = __DIR__ . '/documentos/';
$nombre_archivo = isset($_GET['file']) ? basename($_GET['file']) : '';

// Validaciones de seguridad
if (empty($nombre_archivo) || !file_exists($documentos_dir . $nombre_archivo)) {
    header("Location: seleccionar_resumen.php?status=error&message=" . urlencode("Archivo no válido o no encontrado."));
    exit;
}

$file_path = $documentos_dir . $nombre_archivo;

// Lógica para separar cabeceras del cuerpo
$lines = file($file_path, FILE_IGNORE_NEW_LINES);
$headers = [];
$body_lines = [];
$is_header = true;
foreach ($lines as $line) {
    if ($is_header && (strpos(strtoupper($line), 'FROM:') === 0 || strpos(strtoupper($line), 'TO:') === 0 || strpos(strtoupper($line), 'SUBJECT:') === 0 || trim($line) === '')) {
        $headers[] = $line;
    } else {
        $is_header = false;
        $body_lines[] = $line;
    }
}
$header_content = implode("\n", $headers);
$body_content = implode("\n", $body_lines);
// --- FIN LÓGICA ANTERIOR ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_contenido_html = $_POST['contenido']; // Contenido viene del editor TinyMCE

    // Combinar las cabeceras originales con el nuevo contenido del cuerpo
    $contenido_final = rtrim($header_content) . "\n\n" . $nuevo_contenido_html;

    if (file_put_contents($file_path, $contenido_final) === false) {
        header("Location: documento_editar.php?file={$nombre_archivo}&status=error&message=" . urlencode("No se pudo guardar el archivo."));
        exit;
    }

    registrar_auditoria($conn, $_SESSION['id_usuario'], null, 'DOC_EDIT', "Se editó el documento {$nombre_archivo}.");
    header("Location: seleccionar_resumen.php?status=success&message=" . urlencode("Documento '{$nombre_archivo}' actualizado con éxito."));
    exit;
}

// Para mostrar en el editor, si el contenido no parece HTML, lo convertimos.
if (strip_tags($body_content) === $body_content) {
    // Es texto plano, lo envolvemos en <pre> para conservar formato
    $body_for_editor = '<pre>' . htmlspecialchars($body_content) . '</pre>';
} else {
    // Ya es HTML, lo usamos directamente
    $body_for_editor = $body_content;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Documento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Incluir TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: 'textarea#editor',
            plugins: 'lists link image table code help wordcount',
            toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | indent outdent | bullist numlist | code | help'
        });
    </script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h3>Editando: <?php echo htmlspecialchars($nombre_archivo); ?></h3>
                <p class="text-muted mb-0">Nota: Las cabeceras (FROM, TO, SUBJECT) no son editables desde aquí.</p>
            </div>
            <div class="card-body">
                <?php
                if (isset($_GET['status']) && $_GET['status'] == 'error') {
                    echo "<div class='alert alert-danger'>" . htmlspecialchars($_GET['message']) . "</div>";
                }
                ?>
                <form action="documento_editar.php?file=<?php echo urlencode($nombre_archivo); ?>" method="POST">
                    <div class="mb-3">
                        <textarea id="editor" name="contenido" rows="20">
                            <?php echo $body_for_editor; ?>
                        </textarea>
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