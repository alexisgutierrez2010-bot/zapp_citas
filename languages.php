<?php
// Elaborado por GEMINI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update: Dec-06-2025. Sistema modular de internacionalización (i18n).

// NOTA: session_start() se elimina de aquí.
// El script que incluya este archivo (ej. auth_check.php o sesion_iniciar.php)
// es responsable de iniciar la sesión ANTES de incluirlo.

// 1. Determinar el idioma actual
// Prioridad: 1. Parámetro en URL (?lang=en), 2. Cookie, 3. Sesión, 4. Por defecto 'es'
$lang = 'es'; // Idioma por defecto
if (isset($_GET['lang']) && in_array($_GET['lang'], ['es', 'en'])) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, time() + (3600 * 24 * 30), "/"); // Guardar por 30 días
} elseif (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
} elseif (isset($_COOKIE['lang'])) {
    $lang = $_COOKIE['lang'];
}

// 2. Cargar los módulos de idioma necesarios para el panel de administración
$translations = [];

// Cargar textos comunes
$common_translations = require __DIR__ . '/common.php';
$translations['es'] = $common_translations['es'];
$translations['en'] = $common_translations['en'];

// Cargar textos del panel de administración
$admin_translations = require __DIR__ . '/admin.php';
$translations['es'] = array_merge($translations['es'], $admin_translations['es']);
$translations['en'] = array_merge($translations['en'], $admin_translations['en']);

// 3. Función helper para obtener traducciones
function __($key) {
    global $lang, $translations;
    return $translations[$lang][$key] ?? $key;
}

?>