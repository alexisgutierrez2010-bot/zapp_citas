<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
require_once 'auth_check.php';
require_once 'config.php';

$documentos_dir = __DIR__ . '/documentos/';
$archivos = glob($documentos_dir . '*.{txt,pdf,md,sql}', GLOB_BRACE); // CORRECCIÓN: Buscar archivos .txt, .pdf, .md y .sql

// Ordenar archivos por fecha, del más reciente al más antiguo
rsort($archivos);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Documentos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Gestión de Documentos</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        if (isset($_GET['status']) || isset($_GET['message_key'])) {
                            $status = $_GET['status'] ?? '';
                            $message_key = $_GET['message_key'] ?? '';
                            $message = '';

                            if (!empty($message_key)) {
                                $message = htmlspecialchars($message_key);
                            } elseif (strpos($status, 'success') !== false) {
                                $message = 'Operación realizada con éxito.';
                            }
                            if (!empty($message)) {
                                $alert_type = strpos($status, 'error') === false ? 'success' : 'danger';
                                echo "<div class='alert alert-{$alert_type}'>" . htmlspecialchars($message) . "</div>";
                            }
                        }
                        ?>

                        <p>Seleccione un documento para ver, editar o enviar. También puede subir nuevos documentos.</p>

                        <?php if (empty($archivos)): ?>
                            <div class="alert alert-warning">No se encontraron archivos en el directorio <code>/documentos/</code>.</div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($archivos as $archivo_path): 
                                    $nombre_archivo = basename($archivo_path);
                                    $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
                                    // Lógica mejorada para mostrar un título más limpio
                                    $titulo_limpio = pathinfo(strtolower($nombre_archivo), PATHINFO_FILENAME); // Quita la extensión y convierte a minúsculas
                                    $titulo_limpio = str_replace('_', ' ', $titulo_limpio); // Reemplaza guiones bajos por espacios
                                    $titulo_limpio = ucwords($titulo_limpio); // Pone en mayúscula la primera letra de cada palabra
                                ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <?php echo htmlspecialchars($titulo_limpio); ?> <span class="badge bg-secondary align-middle"><?php echo strtoupper($extension); ?></span>
                                        </span>
                                        <div class="btn-group">                                            
                                            <?php
                                            // CORRECCIÓN: Se reestructura la lógica para generar la URL de forma explícita y evitar errores de truncamiento.
                                            if ($extension === 'md') {
                                                echo '<a href="documento_ver.php?file=' . urlencode($nombre_archivo) . '" class="btn btn-sm btn-info" target="_blank">Ver</a>';
                                            } else {
                                                echo '<a href="documentos/' . urlencode($nombre_archivo) . '" class="btn btn-sm btn-info" target="_blank">Ver</a>';
                                            }
                                            ?>
                                            <?php if ($extension === 'txt'): // Solo mostrar Editar para archivos .txt ?>
                                                <a href="documento_editar.php?file=<?php echo urlencode($nombre_archivo); ?>" class="btn btn-sm btn-warning">Editar</a>
                                            <?php endif; ?>
                                            <?php // Lógica de enlace de envío condicional ?>
                                            <?php if ($extension === 'txt' || $extension === 'md'): ?>
                                                <a href="enviar_resumen.php?file=<?php echo urlencode($nombre_archivo); ?>" 
                                                   class="btn btn-sm btn-primary" 
                                                   onclick="return confirm('¿Está seguro de que desea enviar este documento por correo?');">
                                                   Enviar
                                                </a>
                                            <?php elseif ($extension === 'pdf' || $extension === 'sql'): ?>
                                                <a href="documento_enviar_pdf.php?file=<?php echo urlencode($nombre_archivo); ?>" 
                                                   class="btn btn-sm btn-primary">
                                                   Enviar
                                                </a>
                                            <?php endif; ?>
                                            <form action="documento_eliminar.php" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de que desea eliminar este documento?');">
                                                <input type="hidden" name="file" value="<?php echo urlencode($nombre_archivo); ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Nueva tarjeta para subir documentos -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h4>Subir Nuevo Documento</h4>
                    </div>
                    <div class="card-body">
                        <form action="documento_subir.php" method="POST" enctype="multipart/form-data">                            
                            <div class="mb-3">
                                <label for="documento_subir" class="form-label">Seleccionar archivo (.txt, .pdf, .md, .sql)</label>
                                <input class="form-control" type="file" id="documento_subir" name="documento_subir" accept=".txt,.pdf,.md,.sql" required>
                            </div>
                            <button type="submit" class="btn btn-success">Subir Documento</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>