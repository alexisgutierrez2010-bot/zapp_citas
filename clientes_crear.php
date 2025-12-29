<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
require_once 'auth_check.php';
require_once 'audit_log.php';
require_once 'config.php';

// Solo el rol Administrador puede crear clientes desde este panel.
if (strcasecmp(trim($rol_session ?? ''), 'Administrador') != 0) {
    header("Location: dashboard.php?status=error&message=" . urlencode("Acceso no autorizado."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recoger y limpiar los datos del formulario
    $nombre = trim($_POST['nombre_completo']);
    $country_code = $_POST['country_code'];
    $phone_number = preg_replace('/[^0-9]/', '', $_POST['numero_celular']);
    $email = trim($_POST['correo_electronico']);
    $direccion1 = trim($_POST['direccion1']);
    $direccion2 = trim($_POST['direccion2']); // Nuevo
    $ciudad = trim($_POST['ciudad']);
    $id_pais = isset($_POST['id_pais']) ? (int)$_POST['id_pais'] : null; // Nuevo
    $id_estado = isset($_POST['id_estado']) ? (int)$_POST['id_estado'] : null; // Nuevo
    $zip_code = trim($_POST['zip_code']);
    $notas = trim($_POST['notas_adicionales']); // Nuevo
    $in_sms = isset($_POST['in_sms']) ? 1 : 0; // Nuevo
    $in_email = isset($_POST['in_email']) ? 1 : 0; // Nuevo
    $in_whatsapp = isset($_POST['in_whatsapp']) ? 1 : 0; // Nuevo
    $id_negocio = (int)$_POST['id_negocio']; // Obtener del formulario
    $celular = ''; // Inicializar

    if (empty($nombre) || empty($phone_number) || empty($email) || $id_negocio <= 0) {
        header("Location: clientes_nuevo.php?status=error&message=" . urlencode("Nombre, teléfono, email y negocio son obligatorios."));
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: clientes_nuevo.php?status=error&message=" . urlencode("Email inválido."));
        exit();
    }
    
    $celular = $country_code . ' ' . $phone_number;

    // Verificar duplicados
    $sql_check = "SELECT id_cliente FROM j106_clientes WHERE (correo_electronico = ? OR numero_celular = ?) AND id_negocio = ?";
    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("ssi", $email, $celular, $id_negocio);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            header("Location: clientes_nuevo.php?status=error&message=" . urlencode("El cliente ya existe en este negocio (email o teléfono duplicado)."));
            exit();
        }
        $stmt_check->close();
    }

    $sql = "INSERT INTO j106_clientes (nombre_completo, numero_celular, correo_electronico, direccion1, direccion2, ciudad, id_pais, id_estado, zip_code, notas_adicionales, id_negocio, in_sms, in_email, in_whatsapp, activo, fecha_registro) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssssssiissiii", $nombre, $celular, $email, $direccion1, $direccion2, $ciudad, $id_pais, $id_estado, $zip_code, $notas, $id_negocio, $in_sms, $in_email, $in_whatsapp);

        if ($stmt->execute()) {
            $id_nuevo_cliente = $stmt->insert_id;
            $descripcion_audit = "Se creó el cliente '{$nombre}' (ID: {$id_nuevo_cliente}).";
            registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio, 'CREATE_CLIENT', $descripcion_audit);
            header("Location: clientes_lista.php?status=success&message=" . urlencode("Cliente creado con éxito."));
        } else {
            header("Location: clientes_nuevo.php?status=error&message=" . urlencode("Error al crear: " . $stmt->error));
        }
        $stmt->close();
    } else {
        header("Location: clientes_nuevo.php?status=error&message=" . urlencode($conn->error));
    }
    exit();
}