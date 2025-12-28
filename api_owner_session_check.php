<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Iniciar sesión solo si no hay una activa.
}

// SOLUCIÓN DEFINITIVA: Cargar la configuración aquí, una sola vez.
if (!defined('SMTP_HOST')) {
    require_once __DIR__ . '/config.php';
}

// FIX CRÍTICO: Crear la conexión a la BD si no existe.
// Esto es necesario porque config.php solo trae variables, no el objeto $conn.
if (!isset($conn)) {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Error de conexión a BD: ' . $conn->connect_error]));
    }
    $conn->set_charset("utf8mb4");
}

define('SESSION_TIMEOUT', 300); // 300 segundos = 5 minutos

if (!isset($_SESSION['owner_loggedin']) || $_SESSION['owner_loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

if (isset($_SESSION['owner_last_activity']) && (time() - $_SESSION['owner_last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();

    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Tu sesión ha expirado por inactividad.']);
    exit;
}

$_SESSION['owner_last_activity'] = time(); // Actualizar la hora de la última actividad