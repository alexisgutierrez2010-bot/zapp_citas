<?php
// diagnostico_general.php
// Update :Dec-25-2025).
// Script para verificar el entorno de ZApp Citas según requisitos de leeme.txt

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Diagnóstico de Arranque - ZApp Citas</h1>";
echo "<hr>";

// 1. Verificar Versión de PHP
echo "<h3>1. Versión de PHP</h3>";
$phpVersion = phpversion();
echo "Versión actual: <b>$phpVersion</b><br>";
if (version_compare($phpVersion, '7.4.0', '>=')) {
    echo "<span style='color:green'>&#10004; OK (Cumple requisito >= 8.0)</span>";
    if (version_compare($phpVersion, '8.0.0', '<')) {
        echo "<br><span style='color:orange'>&#9888; Aviso: Estás usando PHP 7.4. Se recomienda PHP 8.0, pero el sistema intentará funcionar.</span>";
    }
} else {
    echo "<span style='color:red'>&#10008; ERROR: Se requiere PHP 8.0 o superior.</span>";
}

// 2. Verificar Extensiones Críticas
echo "<h3>2. Extensiones de PHP</h3>";
$extensions = ['mysqli', 'json', 'mbstring'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "Extensión <b>$ext</b>: <span style='color:green'>&#10004; Instalada</span><br>";
    } else {
        echo "Extensión <b>$ext</b>: <span style='color:red'>&#10008; NO Instalada (Requerida)</span><br>";
    }
}

// 3. Verificar Autoload de Composer
echo "<h3>3. Dependencias (Composer)</h3>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<span style='color:green'>&#10004; vendor/autoload.php encontrado.</span>";
} else {
    echo "<span style='color:red'>&#10008; vendor/autoload.php NO encontrado. Ejecuta 'composer install'.</span>";
}

// 4. Verificar Conexión a Base de Datos
echo "<h3>4. Conexión a Base de Datos (config.php)</h3>";
if (file_exists(__DIR__ . '/config.php')) {
    include __DIR__ . '/config.php';
    // Verificamos si las variables existen (según el formato de leeme.txt)
    if (isset($servername, $username, $password, $dbname)) {
        try {
            // Intentar conexión
            $conn = new mysqli($servername, $username, $password, $dbname);
            if ($conn->connect_error) {
                throw new Exception($conn->connect_error);
            }
            echo "<span style='color:green'>&#10004; Conexión Exitosa a la base de datos '<b>$dbname</b>'.</span>";
            $conn->close();
        } catch (Exception $e) {
            echo "<span style='color:red'>&#10008; Error de conexión: " . $e->getMessage() . "</span><br>";
            echo "<small>Verifica tus credenciales en config.php</small>";
        }
    } else {
        echo "<span style='color:orange'>&#9888; El archivo config.php existe pero no define las variables esperadas (\$servername, \$dbname, etc).</span>";
    }
} else {
    echo "<span style='color:red'>&#10008; El archivo config.php NO existe en la raíz.</span>";
}

// 5. Verificar Sesiones
echo "<h3>5. Sesiones PHP</h3>";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['diagnostico_test'] = 'ok';
if (isset($_SESSION['diagnostico_test']) && $_SESSION['diagnostico_test'] == 'ok') {
    echo "<span style='color:green'>&#10004; Las sesiones están funcionando correctamente.</span>";
} else {
    echo "<span style='color:red'>&#10008; ERROR: No se pueden guardar variables de sesión. Revisa permisos de carpeta tmp.</span>";
}

echo "<hr>";
echo "<p>Si todo lo anterior está en verde, el problema reside en el Frontend (JavaScript) o en la lógica de sesión.</p>";
?>