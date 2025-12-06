<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
require_once 'auth_check.php';
require_once 'audit_log.php';
require_once 'config.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recoger los datos del formulario
    $id_negocio = (int)$_POST['id_negocio'];
    $nombre_negocio = trim($_POST['nombre_negocio']);
    $country_code = trim($_POST['country_code']);
    $telefono_local = trim($_POST['telefono_local']);
    $telefono = $country_code . ' ' . preg_replace('/[^0-9]/', '', $telefono_local); // Combinar y limpiar
    $email = trim($_POST['email']);
    $id_categoria_negocio = !empty($_POST['id_categoria_negocio']) ? (int)$_POST['id_categoria_negocio'] : null;
    $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;
    $direccion1 = trim($_POST['direccion1']);
    $direccion2 = trim($_POST['direccion2']);
    $ciudad = trim($_POST['ciudad']);
    $id_pais = isset($_POST['id_pais']) ? (int)$_POST['id_pais'] : null;
    $id_estado = isset($_POST['id_estado']) ? (int)$_POST['id_estado'] : null;
    $zip_code = trim($_POST['zip_code']);
    
    // Convertir el array de días de trabajo a un string separado por comas
    $dias_trabajo = !empty($_POST['dias_trabajo']) ? implode(',', $_POST['dias_trabajo']) : '';
    
    $hora_inicio = $_POST['hora_inicio'];
    $hora_cierre = $_POST['hora_cierre'];
    $intervalo_minutos = (int)$_POST['intervalo_minutos'];
    
    // Nuevos campos de período de prueba
    $dias_prueba = (int)$_POST['dias_prueba'];
    $fecha_registro = !empty($_POST['fecha_registro']) ? $_POST['fecha_registro'] : null;
    $fecha_habilitacion = !empty($_POST['fecha_habilitacion']) ? $_POST['fecha_habilitacion'] : null;
    $fecha_desactivacion = !empty($_POST['fecha_desactivacion']) ? $_POST['fecha_desactivacion'] : null;

    /*
    // Seguridad: Un Admin solo puede editar su propia configuración
    if ($rol_session != 'Master' && $id_negocio != $id_negocio_session) {
        header("Location: negocios_configuracion.php?status=error&message=" . urlencode("No tienes permiso para esta acción."));
        exit();
    }
    */

    // Iniciar transacción para asegurar la integridad de los datos
    $conn->begin_transaction();

    try {
        // --- VALIDACIÓN Y FORMATEO ---
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El formato del email de contacto no es válido.");
        }
        // El cálculo de fecha_desactivacion ahora es manejado por un TRIGGER en la base de datos.
        // Ya no es necesario calcularlo en PHP.

        // 1. Preparar la consulta SQL completa para la actualización, incluyendo todos los campos del formulario
        $sql_texto = "UPDATE j102_negocios SET 
                        nombre_negocio = ?, telefono = ?, email = ?, id_categoria_negocio = ?, activo = ?,
                        direccion1 = ?, direccion2 = ?, ciudad = ?, id_pais = ?, id_estado = ?, zip_code = ?, 
                        dias_prueba = ?, fecha_registro = ?, fecha_habilitacion = ?, fecha_desactivacion = ?,
                        dias_trabajo = ?, hora_inicio = ?, hora_cierre = ?, intervalo_minutos = ?
                      WHERE id_negocio = ?";
        $stmt_texto = $conn->prepare($sql_texto);
        if (!$stmt_texto) throw new Exception("Error al preparar la consulta de texto: " . $conn->error);

        $stmt_texto->bind_param("sssisissssiisssssssi", 
            $nombre_negocio, $telefono, $email, $id_categoria_negocio, $activo,
            $direccion1, $direccion2, $ciudad, $id_pais, $id_estado, $zip_code,
            $dias_prueba, $fecha_registro, $fecha_habilitacion, $fecha_desactivacion,
            $dias_trabajo, $hora_inicio, $hora_cierre, $intervalo_minutos,
            $id_negocio
        );
        
        if (!$stmt_texto->execute()) throw new Exception("Error al actualizar los datos del negocio: " . $stmt_texto->error);
        $stmt_texto->close();

        // 2. Si se subió una nueva imagen, actualizarla en una consulta separada
        if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] == UPLOAD_ERR_OK) {
            $image_data = file_get_contents($_FILES['background_image']['tmp_name']);
            $image_type = $_FILES['background_image']['type'];

            $sql_imagen = "UPDATE j102_negocios SET background_image_data = ?, background_image_type = ? WHERE id_negocio = ?";
            $stmt_imagen = $conn->prepare($sql_imagen);
            if (!$stmt_imagen) throw new Exception("Error al preparar la consulta de imagen: " . $conn->error);

            $null = NULL; // Variable para bind_param
            $stmt_imagen->bind_param("bsi", $null, $image_type, $id_negocio);
            $stmt_imagen->send_long_data(0, $image_data); // Enviar el blob
            
            if (!$stmt_imagen->execute()) throw new Exception("Error al actualizar la imagen: " . $stmt_imagen->error);
            $stmt_imagen->close();
        }

        // Si todo fue bien, confirmar la transacción y redirigir
        $conn->commit();
        $descripcion_audit = "Se actualizaron los datos del negocio '{$nombre_negocio}' (ID: {$id_negocio}).";
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio, 'UPDATE_BUSINESS', $descripcion_audit);

        header("Location: negocios_configuracion.php?id=" . $id_negocio . "&status=success");

    } catch (Exception $e) {
        $conn->rollback(); // Revertir los cambios si algo falla
        header("Location: negocios_configuracion.php?id=" . $id_negocio . "&status=error&message=" . urlencode($e->getMessage()));
    }
    exit();
}
?>