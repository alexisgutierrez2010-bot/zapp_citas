<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
require_once 'Auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documentos_dir = __DIR__ . '/documentos/';

    // Verificar si se subió un archivo y si no hubo errores
    if (isset($_FILES['documento_subir']) && $_FILES['documento_subir']['error'] === UPLOAD_ERR_OK) {
        
        $file_tmp_path = $_FILES['documento_subir']['tmp_name'];
        $file_name = $_FILES['documento_subir']['name'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // 1. Validación de seguridad: solo permitir archivos .txt
        if (!in_array($file_extension, ['txt', 'pdf'])) {
            header("Location: seleccionar_resumen.php?status=error&message=" . urlencode("Error: Solo se permiten archivos .txt y .pdf."));
            exit;
        }

        // 2. Sanear el nombre del archivo para evitar caracteres problemáticos
        $safe_filename = preg_replace('/[^A-Za-z0-9\._-]/', '_', basename($file_name));

        // 3. Verificar que el nombre del archivo no esté vacío después de sanear
        if (empty($safe_filename)) {
            header("Location: seleccionar_resumen.php?status=error&message=" . urlencode("Error: Nombre de archivo no válido."));
            exit;
        }

        $dest_path = $documentos_dir . $safe_filename;

        // 4. Evitar sobrescribir archivos existentes
        if (file_exists($dest_path)) {
            header("Location: seleccionar_resumen.php?status=error&message=" . urlencode("Error: Ya existe un archivo con el nombre '{$safe_filename}'."));
            exit;
        }

        // 5. Mover el archivo a su destino final
        if (move_uploaded_file($file_tmp_path, $dest_path)) {
            registrar_auditoria($conn, $_SESSION['id_usuario'], null, 'DOC_UPLOAD', "Se subió el documento '{$safe_filename}'.");
            header("Location: seleccionar_resumen.php?status=success&message=" . urlencode("Documento '{$safe_filename}' subido con éxito."));
        } else {
            header("Location: seleccionar_resumen.php?status=error&message=" . urlencode("Error al mover el archivo subido."));
        }

    } else {
        // Manejar errores de subida
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE   => "El archivo excede el tamaño máximo permitido por el servidor.",
            UPLOAD_ERR_FORM_SIZE  => "El archivo excede el tamaño máximo permitido en el formulario.",
            UPLOAD_ERR_PARTIAL    => "El archivo se subió solo parcialmente.",
            UPLOAD_ERR_NO_FILE    => "No se seleccionó ningún archivo.",
        ];
        $error_code = $_FILES['documento_subir']['error'];
        $message = $upload_errors[$error_code] ?? "Ocurrió un error desconocido durante la subida.";
        header("Location: seleccionar_resumen.php?status=error&message=" . urlencode($message));
    }
    exit;
}
?>