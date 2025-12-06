<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025). CORRECCIÓN DEFINITIVA DEL FLUJO DE LOGOUT.

session_start(); // Iniciar la sesión para poder acceder a las variables y luego destruirla.

require_once 'config.php';
require_once 'audit_log.php';

// Guardar los datos de la sesión antes de destruirla
$id_usuario_logout = $_SESSION['id_usuario'] ?? null;
$id_negocio_logout = $_SESSION['id_negocio'] ?? null;
$nombre_usuario_logout = $_SESSION['nombre_usuario'] ?? 'Usuario Desconocido';

// Registrar la auditoría de cierre de sesión
registrar_auditoria($conn, $id_usuario_logout, $id_negocio_logout, 'LOGOUT', "El usuario '{$nombre_usuario_logout}' ha cerrado sesión.");

// --- SECUENCIA DE CIERRE DE SESIÓN CORRECTA ---
// 1. Limpiar todas las variables de la sesión actual.
session_unset();

// 2. Destruir la sesión en el servidor.
session_destroy();

// Redirigir a la página de login
header("location: sesion_iniciar.php");
exit;
?>