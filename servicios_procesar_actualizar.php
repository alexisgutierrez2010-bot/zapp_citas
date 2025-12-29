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
    $eliminar_foto = isset($_POST['eliminar_foto']) ? 1 : 0;

    if ($id_servicio <= 0 || empty($nombre_servicio) || $duracion_valor <= 0) {
        header("Location: servicios_lista.php?status=error&message=Datos inválidos.");
        exit();
    }

    $es_administrador = (isset($rol_session) && strcasecmp(trim($rol_session), 'Administrador') == 0);

    // Construir la cláusula WHERE
    $where_sql = " WHERE id_servicio = ?";
    $where_params = [$id_servicio];
    $where_types = "i";
    if (!$es_administrador) {
        $where_sql .= " AND id_negocio = ?";
        $where_params[] = $id_negocio_session;
        $where_types .= "i";
    }

    // Manejo de la foto y construcción de la consulta
    if ($eliminar_foto) {
        $sql = "UPDATE j104_servicios SET nombre_servicio=?, duracion_valor=?, duracion_unidad=?, precio=?, activo=?, foto_servicio=NULL" . $where_sql;
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisdi" . $where_types, $nombre_servicio, $duracion_valor, $duracion_unidad, $precio, $activo, ...$where_params);
    } elseif (isset($_FILES['foto_servicio']) && $_FILES['foto_servicio']['error'] === UPLOAD_ERR_OK) {
        $foto_blob = resize_image_to_blob($_FILES['foto_servicio']['tmp_name'], 300, 300);
        $sql = "UPDATE j104_servicios SET nombre_servicio=?, duracion_valor=?, duracion_unidad=?, precio=?, activo=?, foto_servicio=?" . $where_sql;
        $stmt = $conn->prepare($sql);
        $null_val = NULL;
        $params = array_merge([$nombre_servicio, $duracion_valor, $duracion_unidad, $precio, $activo, $null_val], $where_params);
        $stmt->bind_param("sisdib" . $where_types, ...$params);
        $stmt->send_long_data(5, $foto_blob); // El índice 5 corresponde al 6to '?' (foto_servicio)
    } else {
        // No se subió foto nueva, no actualizamos el campo BLOB
        $sql = "UPDATE j104_servicios SET nombre_servicio=?, duracion_valor=?, duracion_unidad=?, precio=?, activo=?" . $where_sql;
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisdi" . $where_types, $nombre_servicio, $duracion_valor, $duracion_unidad, $precio, $activo, ...$where_params);
    }

    if ($stmt->execute()) {
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'UPDATE_SERVICE', "Se actualizó el servicio '{$nombre_servicio}' (ID: {$id_servicio}).");
        header("Location: servicios_lista.php?status=success&message=Servicio actualizado con éxito.");
    } else {
        header("Location: servicios_editar.php?id={$id_servicio}&status=error&message=" . urlencode("Error al actualizar: " . $stmt->error));
    }
    $stmt->close();
    exit();
}