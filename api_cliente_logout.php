<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).

session_start();
require_once 'config.php';
require_once 'audit_log.php';

// Crear conexión a BD para auditoría (config.php solo define variables)
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    // Si falla la conexión, continuamos el logout sin auditoría para no bloquear al usuario
    $conn = null;
}

header('Content-Type: application/json');

// Registrar auditoría antes de destruir la sesión
if (isset($_SESSION['client_id']) && $conn) {
    registrar_auditoria($conn, null, $_SESSION['client_id_negocio'] ?? null, 'CLIENT_LOGOUT', "Cliente ID {$_SESSION['client_id']} cerró sesión.");
}

// Destruir todas las variables de sesión.
$_SESSION = array();

// Si se desea destruir la sesión completamente, borre también la cookie de sesión.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión.
session_destroy();

echo json_encode(['success' => true, 'message' => 'Sesión cerrada correctamente.']);
?>