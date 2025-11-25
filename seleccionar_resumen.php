<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
require_once 'Auth_check.php';
require_once 'config.php';

$documentos_dir = __DIR__ . '/documentos/';
$archivos = glob($documentos_dir . '*.{txt,pdf}', GLOB_BRACE); // CORRECCIÓN: Buscar archivos .txt y .pdf

// Ordenar archivos por fecha, del más reciente al más antiguo
rsort($archivos);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos de la Aplicación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Documentos de la Aplicación</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        if (isset($_GET['status'])) {
                            $status_type = strpos($_GET['status'], 'success') !== false ? 'success' : 'danger';
                            $message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Acción completada.';
                            echo "<div class='alert alert-{$status_type}'>{$message}</div>";
                        }
                        ?>

                        <p>Selecciona un documento para ver su contenido o enviarlo por correo electrónico.</p>

                        <?php if (empty($archivos)): ?>
                            <div class="alert alert-warning">No se encontraron archivos de resumen en el directorio <code>/documentos/</code>.</div>
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
                                        <span><?php echo htmlspecialchars($titulo_limpio); ?></span>
                                        <div class="btn-group">
                                            <a href="documentos/<?php echo urlencode($nombre_archivo); ?>" class="btn btn-sm btn-info" target="_blank">Ver</a>
                                            <?php if ($extension === 'txt'): // Solo mostrar Editar para archivos .txt ?>
                                                <a href="documento_editar.php?file=<?php echo urlencode($nombre_archivo); ?>" class="btn btn-sm btn-warning">Editar</a>
                                            <?php endif; ?>
                                            <?php
                                            // Lógica de enlace de envío condicional
                                            if ($extension === 'txt') {
                                                $enviar_url = "enviar_resumen.php?file=" . urlencode($nombre_archivo);
                                                $enviar_confirm_msg = "¿Estás seguro de que quieres enviar este documento por correo?";
                                            } else { // Para PDF
                                                $enviar_url = "documento_enviar_pdf.php?file=" . urlencode($nombre_archivo);
                                                $enviar_confirm_msg = ""; // No se necesita confirmación JS, va a otra página
                                            }
                                            ?>
                                            <a href="<?php echo $enviar_url; ?>" class="btn btn-sm btn-primary" <?php if(!empty($enviar_confirm_msg)) echo "onclick=\"return confirm('{$enviar_confirm_msg}');\""; ?>>Enviar</a>
                                            <form action="documento_eliminar.php" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este documento de forma permanente?');">
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
                                <label for="documento_subir" class="form-label">Seleccionar archivo (.txt o .pdf)</label>
                                <input class="form-control" type="file" id="documento_subir" name="documento_subir" accept=".txt,.pdf" required>
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