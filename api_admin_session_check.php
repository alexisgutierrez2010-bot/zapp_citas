<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-28-2025).

define('SESSION_TIMEOUT', 300); // 300 segundos = 5 minutos

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado. Por favor, inicie sesión de nuevo.']);
    exit;
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    require_once 'config.php';
    require_once 'audit_log.php';
    registrar_auditoria($conn, $_SESSION['id_usuario'], $_SESSION['id_negocio'], 'SESSION_TIMEOUT', "La sesión del usuario '{$_SESSION['nombre_usuario']}' en la API de Admin expiró por inactividad.");
    
    session_unset();
    session_destroy();

    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Tu sesión ha expirado por inactividad.']);
    exit;
}

$_SESSION['last_activity'] = time(); // Actualizar la hora de la última actividad