<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-06-2025). Refactorizado para carga modular.

// Este endpoint no requiere sesión, ya que se usa en la pantalla de login.
session_start();
header('Content-Type: application/json');

// 1. Determinar el idioma
$lang = 'es';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['es', 'en'])) {
    $lang = $_GET['lang'];
} elseif (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
} elseif (isset($_COOKIE['lang'])) {
    $lang = $_COOKIE['lang'];
}

// 2. Determinar el módulo solicitado (ej: 'owner', 'client')
$module = $_GET['module'] ?? 'common'; // Por defecto, solo los comunes

$translations = [];

// 3. Cargar los archivos de idioma necesarios
$common_translations = require __DIR__ . '/common.php';
$translations = $common_translations[$lang];

if (file_exists(__DIR__ . "/{$module}.php")) {
    $module_translations = require __DIR__ . "/{$module}.php";
    $translations = array_merge($translations, $module_translations[$lang]);
}

echo json_encode($translations);
?>