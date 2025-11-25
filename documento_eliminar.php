<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
require_once 'Auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documentos_dir = __DIR__ . '/documentos/';
    $nombre_archivo = isset($_POST['file']) ? basename(urldecode($_POST['file'])) : '';

    // Validaciones de seguridad
    if (empty($nombre_archivo)) {
        header("Location: seleccionar_resumen.php?status=error&message=" . urlencode("No se especificó ningún archivo para eliminar."));
        exit;
    }

    $file_path = $documentos_dir . $nombre_archivo;

    if (!file_exists($file_path)) {
        header("Location: seleccionar_resumen.php?status=error&message=" . urlencode("El archivo que intentas eliminar no existe."));
        exit;
    }

    // Intentar eliminar el archivo
    if (unlink($file_path)) {
        registrar_auditoria($conn, $_SESSION['id_usuario'], null, 'DOC_DELETE', "Se eliminó el documento '{$nombre_archivo}'.");
        header("Location: seleccionar_resumen.php?status=success&message=" . urlencode("Documento '{$nombre_archivo}' eliminado con éxito."));
    } else {
        header("Location: seleccionar_resumen.php?status=error&message=" . urlencode("No se pudo eliminar el archivo. Verifica los permisos del directorio."));
    }
    exit;
}

header("Location: seleccionar_resumen.php");
exit;
?>