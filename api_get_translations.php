<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-06-2025).

// Este endpoint devuelve las traducciones para las SPAs (Single Page Applications).

header('Content-Type: application/json');

// Determinar el idioma solicitado, por defecto 'es'
$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['es', 'en']) ? $_GET['lang'] : 'es';

// Cargar el archivo de idiomas. La variable $translations estará disponible desde este archivo.
require_once 'languages.php';

// Verificar si el array de traducciones y el idioma específico existen
if (isset($translations) && isset($translations[$lang])) {
    echo json_encode($translations[$lang]);
} else {
    // Si hay un error, devolver un objeto JSON vacío con un código de error HTTP
    http_response_code(500);
    echo json_encode(['error' => 'Language file or translations not found.']);
}