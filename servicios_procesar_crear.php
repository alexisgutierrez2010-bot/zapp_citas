<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';
require_once 'image_utils.php'; // Incluir el nuevo script

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_servicio = trim($_POST['nombre_servicio']);
    $duracion_valor = (int)$_POST['duracion_valor'];
    $duracion_unidad = $_POST['duracion_unidad'];
    $precio = !empty($_POST['precio']) ? (float)$_POST['precio'] : null;

    if (empty($nombre_servicio) || $duracion_valor <= 0) {
        header("Location: servicios_lista.php?status=error&message=Datos inválidos.");
        exit();
    }

    $foto_blob = null;
    if (isset($_FILES['foto_servicio']) && $_FILES['foto_servicio']['error'] === UPLOAD_ERR_OK) {
        $foto_blob = resize_image_to_blob($_FILES['foto_servicio']['tmp_name'], 300, 300);
    }

    $sql = "INSERT INTO j104_servicios (id_negocio, nombre_servicio, duracion_valor, duracion_unidad, precio, foto_servicio, activo) VALUES (?, ?, ?, ?, ?, ?, 1)";
    $stmt = $conn->prepare($sql);
    
    // 'b' para BLOB, que se enviará con send_long_data
    $null_val = NULL;
    // CORRECCIÓN: Los tipos para duracion_unidad (s) y precio (d) estaban invertidos.
    $stmt->bind_param("isisdb", $id_negocio_session, $nombre_servicio, $duracion_valor, $duracion_unidad, $precio, $null_val);
    
    if($foto_blob !== null) {
        $stmt->send_long_data(5, $foto_blob); // El índice 5 corresponde al 6to '?' (foto_servicio)
    }

    if ($stmt->execute()) {
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'CREATE_SERVICE', "Se creó el servicio '{$nombre_servicio}'.");
        header("Location: servicios_lista.php?status=success&message=Servicio creado con éxito.");
    } else {
        header("Location: servicios_lista.php?status=error&message=Error al crear el servicio: " . $stmt->error);
    }
    $stmt->close();
    exit();
}