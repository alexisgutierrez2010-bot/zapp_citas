<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
require_once 'auth_check.php';
require_once 'audit_log.php';
require_once 'config.php';
require_once 'image_utils.php'; // Incluir el nuevo script

// Solo el rol Administrador puede crear servicios desde este panel.
if (strcasecmp(trim($rol_session ?? ''), 'Administrador') != 0) {
    header("Location: dashboard.php?status=error&message=" . urlencode("Acceso no autorizado."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recoger y limpiar los datos del formulario
    $nombre = trim($_POST['nombre_servicio']);
    $duracion_valor = (int)$_POST['duracion_valor'];
    $duracion_unidad = $_POST['duracion_unidad'];
    $precio = !empty($_POST['precio']) ? (float)$_POST['precio'] : NULL;
    $id_negocio = (int)$_POST['id_negocio']; // Obtener el negocio del formulario

    // VERIFICACIÓN DE DUPLICADOS: Revisar si el nombre del servicio ya existe
    $sql_check = "SELECT id_servicio FROM j104_servicios WHERE nombre_servicio = ? AND id_negocio = ?";
    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("si", $nombre, $id_negocio);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            // Si encontramos un resultado, el servicio ya existe.
            header("Location: servicios_nuevo.php?status=error&message=" . urlencode("Ya existe un servicio con el nombre '$nombre'."));
            exit();
        }
        $stmt_check->close();
    }

    // --- MANEJO DE FOTO DE SERVICIO ---
    $foto_blob = null;
    if (isset($_FILES['foto_servicio']) && $_FILES['foto_servicio']['error'] === UPLOAD_ERR_OK) {
        $foto_blob = resize_image_to_blob($_FILES['foto_servicio']['tmp_name'], 300, 300);
    }

    // Preparar la consulta SQL para evitar inyecciones SQL
    $sql = "INSERT INTO j104_servicios (nombre_servicio, duracion_valor, duracion_unidad, precio, id_negocio, foto_servicio, activo, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())";

    if ($stmt = $conn->prepare($sql)) {
        // Vincular los parámetros: s = string, i = integer, s = string, d = double, i = integer
        $null_val = NULL;
        $stmt->bind_param("sisdib", $nombre, $duracion_valor, $duracion_unidad, $precio, $id_negocio, $null_val);

        if($foto_blob !== null) {
            $stmt->send_long_data(5, $foto_blob); // El índice 5 corresponde al 6to '?' (foto_servicio)
        }

        // Ejecutar la sentencia
        if ($stmt->execute()) {
            $id_nuevo_servicio = $stmt->insert_id;
            $descripcion_audit = "Se creó el servicio '{$nombre}' (ID: {$id_nuevo_servicio}).";
            // Usar el id_negocio del formulario para la auditoría, no el de la sesión
            registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio, 'CREATE_SERVICE', $descripcion_audit);

            // Redirigir a servicios.php con un mensaje de éxito
            header("Location: servicios_lista.php?status=success&message=" . urlencode("Servicio creado con éxito."));
        } else {
            header("Location: servicios_nuevo.php?status=error&message=" . urlencode($stmt->error));
        }

        $stmt->close();
    } else {
        header("Location: servicios_nuevo.php?status=error&message=" . urlencode($conn->error));
    }

    exit(); // Es buena práctica terminar el script después de una redirección
}