<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';
require_once 'image_utils.php'; // Incluir el nuevo script

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_servicio = (int)$_POST['id_servicio'];
    $nombre_servicio = trim($_POST['nombre_servicio']);
    $duracion_valor = (int)$_POST['duracion_valor'];
    $duracion_unidad = $_POST['duracion_unidad'];
    $precio = !empty($_POST['precio']) ? (float)$_POST['precio'] : null;
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($id_servicio <= 0 || empty($nombre_servicio) || $duracion_valor <= 0) {
        header("Location: servicios_lista.php?status=error&message=Datos inválidos.");
        exit();
    }

    // Manejo de la foto
    if (isset($_FILES['foto_servicio']) && $_FILES['foto_servicio']['error'] === UPLOAD_ERR_OK) {
        $foto_blob = resize_image_to_blob($_FILES['foto_servicio']['tmp_name'], 300, 300);
        $sql = "UPDATE j104_servicios SET nombre_servicio=?, duracion_valor=?, duracion_unidad=?, precio=?, foto_servicio=?, activo=? WHERE id_servicio=? AND id_negocio=?";
        $stmt = $conn->prepare($sql);
        $null_val = NULL;
        // CORRECCIÓN: El tipo para foto_servicio debe ser 'b' (blob), no 'i'.
        $stmt->bind_param("sisdbiii", $nombre_servicio, $duracion_valor, $duracion_unidad, $precio, $null_val, $activo, $id_servicio, $id_negocio_session);
        $stmt->send_long_data(4, $foto_blob); // El índice 4 corresponde al 5to '?' (foto_servicio)
    } else {
        // No se subió foto nueva, no actualizamos el campo BLOB
        $sql = "UPDATE j104_servicios SET nombre_servicio=?, duracion_valor=?, duracion_unidad=?, precio=?, activo=? WHERE id_servicio=? AND id_negocio=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisdiii", $nombre_servicio, $duracion_valor, $duracion_unidad, $precio, $activo, $id_servicio, $id_negocio_session);
    }

    if ($stmt->execute()) {
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'UPDATE_SERVICE', "Se actualizó el servicio '{$nombre_servicio}' (ID: {$id_servicio}).");
        header("Location: servicios_lista.php?status=success&message=Servicio actualizado con éxito.");
    } else {
        header("Location: servicios_lista.php?status=error&message=Error al actualizar el servicio: " . $stmt->error);
    }
    $stmt->close();
    exit();
}