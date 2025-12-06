<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
session_start();
require_once 'config.php';
require_once 'audit_log.php'; // Reactivado

// Registrar auditoría ANTES de destruir la sesión
if (isset($_SESSION['owner_loggedin']) && $_SESSION['owner_loggedin'] === true) {
    registrar_auditoria($conn, $_SESSION['owner_id_usuario'], $_SESSION['owner_id_negocio'], 'OWNER_SPA_LOGOUT', "Propietario '{$_SESSION['owner_nombre_usuario']}' cerró sesión en la SPA."); // Reactivado
}
// Destruir todas las variables de sesión específicas del propietario
unset($_SESSION['owner_loggedin']);
unset($_SESSION['owner_id_usuario']);
unset($_SESSION['owner_nombre_usuario']);
unset($_SESSION['owner_id_negocio']);
unset($_SESSION['owner_nombre_negocio']);

echo json_encode(['success' => true, 'message' => 'Sesión cerrada.']);
?>