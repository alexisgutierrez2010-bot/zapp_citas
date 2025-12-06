<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
require_once 'auth_check.php';
require_once 'audit_log.php';
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recoger y limpiar los datos del formulario
    $nombre = trim($_POST['nombre_servicio']);
    $duracion_valor = (int)$_POST['duracion_valor'];
    $duracion_unidad = $_POST['duracion_unidad'];
    $precio = !empty($_POST['precio']) ? (float)$_POST['precio'] : NULL;

    // VERIFICACIÓN DE DUPLICADOS: Revisar si el nombre del servicio ya existe
    $sql_check = "SELECT id_servicio FROM j104_servicios WHERE nombre_servicio = ? AND id_negocio = ?";
    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("si", $nombre, $id_negocio_session);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            // Si encontramos un resultado, el servicio ya existe.
            header("Location: servicios_lista.php?status=error&message=" . urlencode("Ya existe un servicio con el nombre '$nombre'."));
            exit();
        }
        $stmt_check->close();
    }

    // Preparar la consulta SQL para evitar inyecciones SQL
    $sql = "INSERT INTO j104_servicios (nombre_servicio, duracion_valor, duracion_unidad, precio, id_negocio) VALUES (?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        // Vincular los parámetros: s = string, i = integer, s = string, d = double, i = integer
        $stmt->bind_param("sisdi", $nombre, $duracion_valor, $duracion_unidad, $precio, $id_negocio_session);

        // Ejecutar la sentencia
        if ($stmt->execute()) {
            $id_nuevo_servicio = $stmt->insert_id;
            $descripcion_audit = "Se creó el servicio '{$nombre}' (ID: {$id_nuevo_servicio}).";
            registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'CREATE_SERVICE', $descripcion_audit);

            // Redirigir a servicios.php con un mensaje de éxito
            header("Location: servicios_lista.php?status=success");
        } else {
            header("Location: servicios_lista.php?status=error&message=" . urlencode($stmt->error));
        }

        $stmt->close();
    } else {
        header("Location: servicios_lista.php?status=error&message=" . urlencode($conn->error));
    }

    exit(); // Es buena práctica terminar el script después de una redirección
}
?>