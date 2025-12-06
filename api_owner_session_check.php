<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
// session_start(); // CORRECTO: El guardián NO debe iniciar la sesión.

// SOLUCIÓN DEFINITIVA: Cargar la configuración aquí, una sola vez.
require_once __DIR__ . '/config.php';

define('SESSION_TIMEOUT', 300); // 300 segundos = 5 minutos

if (!isset($_SESSION['owner_loggedin']) || $_SESSION['owner_loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

if (isset($_SESSION['owner_last_activity']) && (time() - $_SESSION['owner_last_activity'] > SESSION_TIMEOUT)) {
    // Reactivando la auditoría para consistencia
    require_once 'audit_log.php';
    registrar_auditoria($conn, $_SESSION['owner_id_usuario'], $_SESSION['owner_id_negocio'], 'SESSION_TIMEOUT', "La sesión del propietario '{$_SESSION['owner_nombre_usuario']}' en la SPA expiró por inactividad.");

    session_unset();
    session_destroy();

    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Tu sesión ha expirado por inactividad.']);
    exit;
}

$_SESSION['owner_last_activity'] = time(); // Actualizar la hora de la última actividad