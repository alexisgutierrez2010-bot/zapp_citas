<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
require_once 'Auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

// Guardar los datos de la sesión antes de destruirla
$id_usuario_logout = $_SESSION['id_usuario'];
$id_negocio_logout = $_SESSION['id_negocio'];
$nombre_usuario_logout = $_SESSION['nombre_usuario'];

// Registrar la auditoría de cierre de sesión
registrar_auditoria($conn, $id_usuario_logout, $id_negocio_logout, 'LOGOUT', "El usuario '{$nombre_usuario_logout}' ha cerrado sesión.");

// Destruir la sesión
session_destroy();

// Redirigir a la página de login
header("location: sesion_iniciar.php");
exit;
?>