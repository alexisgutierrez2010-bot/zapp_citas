<?php
// Elaborado por GEMINI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update: Dec-02-2025.

// --- NOTA DE SEGURIDAD ---
// Este es un programa de diagnóstico y no requiere inicio de sesión. Elimínelo después de su uso.

header('Content-Type: text/html; charset=utf-8');

function print_status($message, $success = true) {
    $color = $success ? 'green' : 'red';
    $icon = $success ? '✅' : '❌';
    echo "<p style='font-family: sans-serif; font-size: 1.1rem; color: $color; font-weight: bold;'>$icon $message</p>";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico de Composer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header"><h1>Diagnóstico de Composer y Dependencias</h1></div>
            <div class="card-body">
                <?php
                // Paso 1: Verificar si composer.json existe
                echo "<h4>Paso 1: Verificando <code>composer.json</code></h4>";
                $composer_json_path = __DIR__ . '/composer.json';
                if (file_exists($composer_json_path)) {
                    print_status("El archivo <code>composer.json</code> existe.");

                    // Paso 2: Verificar si el JSON es válido
                    $json_content = file_get_contents($composer_json_path);
                    json_decode($json_content);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        print_status("El contenido de <code>composer.json</code> es un JSON válido.");
                    } else {
                        print_status("¡ERROR! El archivo <code>composer.json</code> contiene JSON inválido. Error: " . json_last_error_msg(), false);
                        echo "<pre style='background-color: #ffecec; border: 1px solid red; padding: 10px;'>" . htmlspecialchars($json_content) . "</pre>";
                        echo "<p><strong>Solución:</strong> Reemplace el contenido del archivo con el código que le proporcioné anteriormente y ejecute <code>composer install</code> de nuevo.</p>";
                        exit;
                    }
                } else {
                    print_status("¡ERROR! El archivo <code>composer.json</code> no se encontró en la raíz del proyecto.", false);
                    echo "<p><strong>Solución:</strong> Cree el archivo <code>composer.json</code> en <code>c:\\xampp\\htdocs\\zapp_citas\\</code> y ejecute <code>composer install</code>.</p>";
                    exit;
                }

                echo "<hr>";

                // Paso 3: Verificar si vendor/autoload.php existe
                echo "<h4>Paso 2: Verificando el Autoloader de Composer</h4>";
                $autoloader_path = __DIR__ . '/vendor/autoload.php';
                if (file_exists($autoloader_path)) {
                    print_status("El archivo <code>vendor/autoload.php</code> existe.");
                    require_once $autoloader_path;
                    print_status("El archivo <code>vendor/autoload.php</code> se ha cargado correctamente.");
                } else {
                    print_status("¡ERROR! El archivo <code>vendor/autoload.php</code> no existe. Esto significa que Composer no ha instalado las dependencias.", false);
                    echo "<p><strong>Solución:</strong> Abra una terminal en la carpeta del proyecto y ejecute el comando: <code>composer install</code>.</p>";
                    exit;
                }

                echo "<hr>";

                // Paso 4: Verificar si las clases se pueden encontrar
                echo "<h4>Paso 3: Verificando la carga de clases</h4>";
                print_status("Verificando la clase 'Parsedown'...", class_exists('Parsedown'));
                print_status("Verificando la clase 'PHPMailer\\PHPMailer\\PHPMailer'...", class_exists('PHPMailer\\PHPMailer\\PHPMailer'));
                ?>
            </div>
        </div>
    </div>
</body>
</html>