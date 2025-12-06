<?php
// Elaborado por GEMINI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update: Dec-02-2025.

require_once 'auth_check.php';
require_once 'vendor/autoload.php'; // Cargar Parsedown

$file = $_GET['file'] ?? '';
$documentos_dir = __DIR__ . '/documentos/';

// --- Medidas de Seguridad ---
// 1. Evitar Path Traversal: asegurarse de que no se pueda acceder a archivos fuera del directorio 'documentos'
//    basename() extrae solo el nombre del archivo, eliminando cualquier intento de navegar a otros directorios (ej. ../../)
$safe_filename = basename($file);

// 2. Construir la ruta completa y verificar que el archivo existe y es un archivo regular.
$file_path = $documentos_dir . $safe_filename;
if (empty($safe_filename) || !is_file($file_path)) {
    http_response_code(404);
    die("Error: Documento no encontrado o acceso no permitido.");
}

// 3. Validar que la extensión sea .md
$extension = pathinfo($safe_filename, PATHINFO_EXTENSION);
if (strtolower($extension) !== 'md') {
    http_response_code(400);
    die("Error: Tipo de archivo no válido. Este visor solo es para archivos Markdown (.md).");
}

$markdownContent = file_get_contents($file_path);

if ($markdownContent === false) {
    http_response_code(500);
    die("Error: No se pudo leer el contenido del documento.");
}

// 4. Instanciar Parsedown y convertir el contenido a HTML
$Parsedown = new Parsedown();
$htmlContent = $Parsedown->text($markdownContent);

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('documents_viewer_title'); ?>: <?php echo htmlspecialchars($safe_filename); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/github-markdown.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-4">
        <div class="card"><div class="card-body markdown-body"><?php echo $htmlContent; ?></div></div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>