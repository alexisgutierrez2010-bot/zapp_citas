<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).

// --- SOLUCIÓN DEFINITIVA: Cargar el autoloader de Composer ANTES de iniciar la sesión. ---
// Este archivo es el punto de entrada de seguridad para el panel de admin, es el lugar ideal para cargar las dependencias.
require_once 'vendor/autoload.php';

session_start();

define('SESSION_TIMEOUT', 300); // 300 segundos = 5 minutos

// Si el usuario no ha iniciado sesión, redirigirlo a la página de login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: sesion_iniciar.php");
    exit;
}

// Guardamos los datos de la sesión en variables para fácil acceso
// --- SOLUCIÓN DEFINITIVA: Lectura directa de la sesión ---
// Se lee directamente el id_negocio y el rol que fueron establecidos correctamente en procesar_login.php.
// Se elimina la lógica condicional que sobrescribía el id_negocio para usuarios no-Master.
$id_negocio_session = $_SESSION['id_negocio'] ?? null;
$rol_session = $_SESSION['rol'] ?? null;

// --- LÓGICA DE EXPIRACIÓN DE SESIÓN POR INACTIVIDAD ---
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    // La última actividad fue hace más tiempo que el timeout
    require_once 'config.php';
    require_once 'audit_log.php';
    registrar_auditoria($conn, $_SESSION['id_usuario'], $_SESSION['id_negocio'], 'SESSION_TIMEOUT', "La sesión del usuario '{$_SESSION['nombre_usuario']}' expiró por inactividad.");
    
    session_unset();     // Eliminar todas las variables de sesión
    session_destroy();   // Destruir la sesión

    header("location: sesion_iniciar.php?error=" . urlencode("Tu sesión ha expirado por inactividad. Por favor, inicia sesión de nuevo."));
    exit;
}
$_SESSION['last_activity'] = time(); // Actualizar la hora de la última actividad
// --- FIN DE LA LÓGICA DE EXPIRACIÓN ---

// --- CONFIGURACIÓN DE ZONA HORARIA DINÁMICA ---
// Obtenemos la zona horaria del negocio actual y la establecemos para toda la sesión.
// Esto asegura que todas las operaciones de fecha/hora sean consistentes.
require_once 'config.php'; // Correcto
$stmt_tz = $conn->prepare("SELECT p.timezone 
                           FROM j102_negocios n
                           JOIN j110_paises p ON n.id_pais = p.id_pais
                           WHERE n.id_negocio = ?");
$stmt_tz->bind_param("i", $id_negocio_session);
$stmt_tz->execute();
$result_tz = $stmt_tz->get_result()->fetch_assoc();
$timezone_negocio = $result_tz['timezone'] ?? 'America/Chicago'; // Usar un fallback razonable
$stmt_tz->close();

date_default_timezone_set($timezone_negocio);

?>