<?php
// Archivo de prueba para verificar la instalación de PHPMailer.
// Elaborado por Gemini Code Assist.

// 1. Activar la visualización de todos los errores para un diagnóstico completo.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Verificación de PHPMailer</h1>";

// 2. Intentar cargar el autoloader de Composer.
$autoloader_path = __DIR__ . '/vendor/autoload.php';
echo "<p>Intentando cargar el autoloader desde: <code>" . htmlspecialchars($autoloader_path) . "</code></p>";

if (!file_exists($autoloader_path)) {
    echo "<p style='color: red; font-weight: bold;'>ERROR CRÍTICO: El archivo 'vendor/autoload.php' no se encuentra. Asegúrate de haber ejecutado 'composer install'.</p>";
    exit;
}

require_once $autoloader_path;
echo "<p style='color: green;'>Éxito: El archivo 'vendor/autoload.php' fue cargado correctamente.</p>";

// 3. Intentar usar la clase PHPMailer.
echo "<p>Intentando reconocer la clase <code>PHPMailer\\PHPMailer\\PHPMailer</code>...</p>";

use PHPMailer\PHPMailer\PHPMailer;

// 4. Verificar si la clase ahora existe en la memoria de PHP.
if (class_exists(PHPMailer::class)) {
    echo "<h2 style='color: green; font-weight: bold;'>¡VERIFICACIÓN EXITOSA!</h2>";
    echo "<p>PHPMailer está instalado y es accesible para PHP. Cualquier error futuro en el envío de correos probablemente se deba a la lógica del script que lo llama o a la configuración SMTP.</p>";
} else {
    echo "<h2 style='color: red; font-weight: bold;'>¡VERIFICACIÓN FALLIDA!</h2>";
    echo "<p>Aunque el autoloader fue encontrado, la clase PHPMailer no pudo ser cargada. Revisa el archivo `composer.json` dentro de `vendor/phpmailer/phpmailer/` para asegurarte de que la configuración de autoloading sea correcta.</p>";
}

?>