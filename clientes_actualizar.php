<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-27-2025).
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
    $activo = isset($_POST['activo']) ? 1 : 0; // Nuevo campo
    $celular = ''; // Inicializar

    if ($id_cliente <= 0) {
        header("Location: clientes_lista.php?status=error&message=" . urlencode("ID de cliente inválido."));
        exit();
    }

    // --- NUEVA VALIDACIÓN ---
    // Validar el formato del correo electrónico
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: clientes_editar.php?id=" . $id_cliente . "&status=error&message=" . urlencode("El formato del correo electrónico no es válido."));
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
            header("Location: clientes_editar.php?id=" . $id_cliente . "&status=error&message=" . urlencode("El correo electrónico '$email' ya está registrado para otro cliente."));
            exit();
        }
        $stmt_check->close();
    }

    // 3. Preparar la consulta SQL de actualización
    $sql = "UPDATE j106_clientes SET nombre_completo = ?, numero_celular = ?, correo_electronico = ?, direccion1 = ?, direccion2 = ?, ciudad = ?, id_pais = ?, id_estado = ?, zip_code = ?, notas_adicionales = ?, IN_SMS = ?, IN_EMAIL = ?, activo = ? WHERE id_cliente = ?";

    if ($stmt = $conn->prepare($sql)) {
        // Vincular los parámetros
        $stmt->bind_param("ssssssiissiiii", $nombre, $celular, $email, $direccion1, $direccion2, $ciudad, $id_pais, $id_estado, $zip_code, $notas, $in_sms, $in_email, $activo, $id_cliente);

        // Ejecutar la sentencia
        if ($stmt->execute()) {
            $descripcion_audit = "Se actualizó el cliente '{$nombre}' (ID: {$id_cliente}).";
            registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'UPDATE_CLIENT', $descripcion_audit);

            // Si todo va bien, redirigir a index.php con un mensaje de éxito
            header("Location: clientes_lista.php?status=success_edit");
        } else {
            header("Location: clientes_editar.php?id=" . $id_cliente . "&status=error&message=" . urlencode($stmt->error));
        }
        $stmt->close();
    } else {
        header("Location: clientes_editar.php?id=" . $id_cliente . "&status=error&message=" . urlencode($conn->error));
    }

    exit();
}
?>