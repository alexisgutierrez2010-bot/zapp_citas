<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

// Solo el rol Administrador puede editar clientes desde este panel.
if (strcasecmp(trim($rol_session ?? ''), 'Administrador') != 0) {
    header("Location: dashboard.php?status=error&message=" . urlencode("Acceso no autorizado."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Recoger y validar datos del formulario
    $id_cliente = (int)$_POST['id_cliente'];
    // Si es admin, toma el id_negocio del form. Si no (aunque no debería llegar aquí), usa el de la sesión.
    $id_negocio = (int)($_POST['id_negocio'] ?? $id_negocio_session); 
    $nombre = trim($_POST['nombre_completo']);
    $country_code = $_POST['country_code'];
    $phone_number = preg_replace('/[^0-9]/', '', $_POST['numero_celular']);
    $email = trim($_POST['correo_electronico']);
    $direccion1 = trim($_POST['direccion1']);
    $direccion2 = trim($_POST['direccion2']);
    $ciudad = trim($_POST['ciudad']);
    $id_pais = !empty($_POST['id_pais']) ? (int)$_POST['id_pais'] : null;
    $id_estado = !empty($_POST['id_estado']) ? (int)$_POST['id_estado'] : null;
    $zip_code = trim($_POST['zip_code']);
    $notas = trim($_POST['notas_adicionales']);
    $in_sms = isset($_POST['in_sms']) ? 1 : 0;
    $in_email = isset($_POST['in_email']) ? 1 : 0;
    $in_whatsapp = isset($_POST['in_whatsapp']) ? 1 : 0;
    $activo = isset($_POST['activo']) ? 1 : 0;
    $celular = '';

    // 2. Validaciones básicas
    if ($id_cliente <= 0) {
        header("Location: clientes_lista.php?status=error&message=" . urlencode("ID de cliente inválido."));
        exit();
    }
    if (empty($nombre) || empty($phone_number) || empty($email)) {
        header("Location: clientes_editar.php?id={$id_cliente}&message=" . urlencode("Nombre, teléfono y email son obligatorios."));
        exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: clientes_editar.php?id={$id_cliente}&message=" . urlencode("Formato de email inválido."));
        exit();
    }

    $celular = $country_code . ' ' . $phone_number;

    // 3. Verificar duplicados (email o teléfono) en el negocio, excluyendo al cliente actual
    $sql_check = "SELECT id_cliente FROM j106_clientes WHERE (correo_electronico = ? OR numero_celular = ?) AND id_negocio = ? AND id_cliente != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ssii", $email, $celular, $id_negocio, $id_cliente);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        header("Location: clientes_editar.php?id={$id_cliente}&message=" . urlencode("Email o teléfono ya existe para otro cliente en este negocio."));
        exit();
    }
    $stmt_check->close();

    // 4. Construir y ejecutar la consulta de actualización
    $sql = "UPDATE j106_clientes SET 
                nombre_completo = ?, 
                numero_celular = ?, 
                correo_electronico = ?, 
                direccion1 = ?, 
                direccion2 = ?, 
                ciudad = ?, 
                id_pais = ?, 
                id_estado = ?, 
                zip_code = ?, 
                notas_adicionales = ?, 
                in_sms = ?, 
                in_email = ?, 
                in_whatsapp = ?, 
                activo = ?,
                id_negocio = ?
            WHERE id_cliente = ?";
    $stmt = $conn->prepare($sql);
    // Tipos: s (nombre), s (celular), s (email), s (dir1), s (dir2), s (ciudad), i (id_pais), i (id_estado), s (zip), s (notas), i (sms), i (email), i (wa), i (activo), i (id_negocio), i (id_cliente)
    $stmt->bind_param("ssssssiissiiiiii", $nombre, $celular, $email, $direccion1, $direccion2, $ciudad, $id_pais, $id_estado, $zip_code, $notas, $in_sms, $in_email, $in_whatsapp, $activo, $id_negocio, $id_cliente);

    if ($stmt->execute()) {
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio, 'UPDATE_CLIENT', "Se actualizó el cliente '{$nombre}' (ID: {$id_cliente}).");
        header("Location: clientes_lista.php?status=success&message=" . urlencode("Cliente actualizado con éxito."));
    } else {
        header("Location: clientes_editar.php?id={$id_cliente}&message=" . urlencode("Error al actualizar: " . $stmt->error));
    }
    $stmt->close();
    exit();
}
?>

