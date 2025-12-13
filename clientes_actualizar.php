<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
require_once 'auth_check.php';
require_once 'audit_log.php';
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Recoger y limpiar los datos del formulario
    $id_cliente = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 0;
    $nombre = trim($_POST['nombre_completo']);
    $country_code = $_POST['country_code'];
    $phone_number = preg_replace('/[^0-9]/', '', $_POST['numero_celular']); // Limpiar número
    $email = trim($_POST['correo_electronico']);
    $direccion1 = trim($_POST['direccion1']);
    $direccion2 = trim($_POST['direccion2']);
    $ciudad = trim($_POST['ciudad']);
    $id_pais = isset($_POST['id_pais']) ? (int)$_POST['id_pais'] : null;
    $id_estado = isset($_POST['id_estado']) ? (int)$_POST['id_estado'] : null;
    $zip_code = trim($_POST['zip_code']);
    $notas = trim($_POST['notas_adicionales']);
    $in_sms = isset($_POST['in_sms']) ? 1 : 0;
    $in_email = isset($_POST['in_email']) ? 1 : 0;
    $in_whatsapp = isset($_POST['in_whatsapp']) ? 1 : 0;
    $activo = isset($_POST['activo']) ? 1 : 0; // Nuevo campo
    $celular = ''; // Inicializar

    if ($id_cliente <= 0) {
        header("Location: clientes_lista.php?status=error&message_key=error_invalid_id");
        exit();
    }

    // --- NUEVA VALIDACIÓN ---
    // Validar el formato del correo electrónico
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: clientes_editar.php?id=" . $id_cliente . "&status=error&message_key=error_invalid_email");
        exit();
    }    
    if (!empty($phone_number)) {
        $celular = $country_code . ' ' . $phone_number;
    }
    // --- FIN DE LA NUEVA VALIDACIÓN ---

    // 2. VERIFICACIÓN DE DUPLICADOS: Revisar si el nuevo correo ya pertenece a OTRO cliente
    $sql_check = "SELECT id_cliente FROM j106_clientes WHERE correo_electronico = ? AND id_cliente != ?";
    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("si", $email, $id_cliente);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            // El correo ya está en uso por otro cliente. Redirigir de vuelta a la página de edición.
            header("Location: clientes_editar.php?id=" . $id_cliente . "&status=error&message_key=error_duplicate_entry");
            exit();
        }
        $stmt_check->close();
    }

    // Iniciar transacción para asegurar la integridad de los datos
    $conn->begin_transaction();

    try {
        // 3. Preparar la consulta SQL de actualización para los datos de texto
        $sql = "UPDATE j106_clientes SET 
                    nombre_completo = ?, numero_celular = ?, correo_electronico = ?, 
                    direccion1 = ?, direccion2 = ?, ciudad = ?, id_pais = ?, id_estado = ?, 
                    zip_code = ?, notas_adicionales = ?, in_sms = ?, in_email = ?, in_whatsapp = ?, activo = ?
                WHERE id_cliente = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssiissiiiii", $nombre, $celular, $email, $direccion1, $direccion2, $ciudad, $id_pais, $id_estado, $zip_code, $notas, $in_sms, $in_email, $in_whatsapp, $activo, $id_cliente);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar los datos del cliente.");
        }
        $stmt->close();

        // 4. MANEJO DE FOTO DE PERFIL (si se subió una nueva)
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == UPLOAD_ERR_OK) {
            $check = getimagesize($_FILES['foto_perfil']['tmp_name']);
            if ($check !== false) {
                $foto_data = file_get_contents($_FILES['foto_perfil']['tmp_name']);
                $foto_tipo = $_FILES['foto_perfil']['type'];
                $sql_img = "UPDATE j106_clientes SET foto_perfil_data = ?, foto_perfil_tipo = ? WHERE id_cliente = ?";
                $stmt_img = $conn->prepare($sql_img);
                $null = NULL; // Necesario para send_long_data
                $stmt_img->bind_param("bsi", $null, $foto_tipo, $id_cliente);
                $stmt_img->send_long_data(0, $foto_data);
                if (!$stmt_img->execute()) {
                    throw new Exception("Error al actualizar la foto de perfil.");
                }
                $stmt_img->close();
            }
        }

        // 5. Si todo fue bien, confirmar la transacción y registrar auditoría
        $conn->commit();
        $descripcion_audit = "Se actualizó el cliente '{$nombre}' (ID: {$id_cliente}).";
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'UPDATE_CLIENT', $descripcion_audit);
        
        // 6. Redirigir con mensaje de éxito
        header("Location: clientes_lista.php?status=success_update");

    } catch (Exception $e) {
        $conn->rollback(); // Revertir cambios si algo falló
        header("Location: clientes_editar.php?id=" . $id_cliente . "&status=error&message_key=operation_error");
    }

    exit();
}
?>