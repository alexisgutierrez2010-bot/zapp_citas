<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// SOLUCIÓN DE SEGURIDAD: Prevenir que el navegador guarde en caché la verificación de sesión.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// SOLUCIÓN: Cargar config y crear conexión a BD, igual que en el guardián del owner.
if (!defined('SMTP_HOST')) {
    require_once __DIR__ . '/config.php';
}

if (!isset($conn)) {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        http_response_code(500);
        die(json_encode(['error' => 'Error de conexión a BD: ' . $conn->connect_error]));
    }
    $conn->set_charset("utf8mb4");
}

define('SESSION_TIMEOUT', 300); // 300 segundos = 5 minutos

if (!isset($_SESSION['client_loggedin']) || $_SESSION['client_loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

if (isset($_SESSION['client_last_activity']) && (time() - $_SESSION['client_last_activity'] > SESSION_TIMEOUT)) {
    // La conexión ya existe gracias al bloque superior, solo llamamos a la auditoría.
    require_once 'audit_log.php';
    registrar_auditoria(
        $conn, 
        null, // Los clientes no son usuarios del sistema, no tienen id_usuario.
        $_SESSION['client_id_negocio'] ?? null,
        'SESSION_TIMEOUT', 
        // SOLUCIÓN: Usar el operador de fusión de null para evitar un PHP Notice si la variable de sesión no existe.
        "La sesión del cliente '" . ($_SESSION['client_nombre_completo'] ?? 'Desconocido') . "' en la SPA expiró por inactividad."
    );
    
    session_unset();
    session_destroy();

    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Tu sesión ha expirado por inactividad.']);
    exit;
}

$_SESSION['client_last_activity'] = time(); // Actualizar la hora de la última actividad

// --- SOLUCIÓN DEFINITIVA ---
// Si este script es llamado directamente por el frontend para verificar la sesión,
// y la sesión es válida (es decir, no ha salido en los `exit` anteriores),
// debe devolver una respuesta JSON de éxito para que el `fetch().json()` no falle.
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Session is active.',
        'client' => [
            'id_cliente' => $_SESSION['client_id'] ?? 0,
            'nombre_completo' => $_SESSION['client_nombre_completo'] ?? 'Cliente',
            'id_negocio' => $_SESSION['client_id_negocio'] ?? 0
        ]
    ]);
}