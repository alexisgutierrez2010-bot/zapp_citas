<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
require_once 'auth_check.php';
require_once 'audit_log.php';
require_once 'config.php';

// Solo un Master puede eliminar negocios
/*
if ($rol_session != 'Master') {
    header("Location: dashboard.php?status=error&message=" . urlencode("Acceso no autorizado."));
    exit;
}
*/

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_negocio_eliminar = isset($_POST['id_negocio']) ? (int)$_POST['id_negocio'] : 0;

    // No se puede eliminar la configuración principal (ID 1) o la propia
    if ($id_negocio_eliminar <= 1 || $id_negocio_eliminar == $id_negocio_session) {
        header("Location: negocios_configuracion.php?status=error&message=" . urlencode("No puedes eliminar el negocio principal o el negocio en el que has iniciado sesión."));
        exit();
    }

    // Obtener nombre para auditoría antes de borrar
    $stmt_info = $conn->prepare("SELECT nombre_negocio FROM j102_negocios WHERE id_negocio = ?");
    $stmt_info->bind_param("i", $id_negocio_eliminar);
    $stmt_info->execute();
    $info = $stmt_info->get_result()->fetch_assoc();
    $nombre_negocio_eliminado = $info['nombre_negocio'] ?? 'Desconocido';
    $stmt_info->close();

    // La base de datos está configurada con ON DELETE CASCADE, por lo que al eliminar una configuración,
    // se eliminarán en cascada los usuarios, clientes y citas asociados.
    $sql = "UPDATE j102_negocios SET activo = 3 WHERE id_negocio = ?"; // Borrado lógico
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_negocio_eliminar);

    if ($stmt->execute()) {
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'DELETE_BUSINESS', "Se marcó como eliminado el negocio '{$nombre_negocio_eliminado}' (ID: {$id_negocio_eliminar}).");
        header("Location: negocios_configuracion.php?status=success&message=" . urlencode("Negocio eliminado con éxito."));
    } else {
        header("Location: negocios_configuracion.php?status=error&message=" . urlencode("Error al eliminar el negocio: " . $stmt->error));
    }
    $stmt->close();
    exit();
}