<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-27-2025).
require_once 'auth_check.php';
require_once 'audit_log.php';
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Recoger y limpiar los datos del formulario
    $id_servicio = isset($_POST['id_servicio']) ? (int)$_POST['id_servicio'] : 0;
    $nombre = trim($_POST['nombre_servicio']);
    $duracion_valor = (int)$_POST['duracion_valor'];
    $duracion_unidad = $_POST['duracion_unidad'];
    $precio = !empty($_POST['precio']) ? (float)$_POST['precio'] : NULL;

    if ($id_servicio <= 0) {
        header("Location: servicios_lista.php?status=error&message=" . urlencode("ID de servicio inválido."));
        exit();
    }

    // 2. VERIFICACIÓN DE DUPLICADOS: Revisar si el nuevo nombre ya pertenece a OTRO servicio
    $sql_check = "SELECT id_servicio FROM j104_servicios WHERE nombre_servicio = ? AND id_servicio != ? AND id_negocio = ?";
    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("sii", $nombre, $id_servicio, $id_negocio_session);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            header("Location: servicios_editar.php?id=" . $id_servicio . "&status=error&message=" . urlencode("El nombre '$nombre' ya está en uso por otro servicio."));
            exit();
        }
        $stmt_check->close();
    }

    // 3. Preparar la consulta SQL de actualización
    $sql = "UPDATE j104_servicios SET nombre_servicio = ?, duracion_valor = ?, duracion_unidad = ?, precio = ? WHERE id_servicio = ? AND id_negocio = ?";

    if ($stmt = $conn->prepare($sql)) {
        // Vincular los parámetros: s = string, i = int, s = string, d = double, i = int, i = int
        $stmt->bind_param("sisdii", $nombre, $duracion_valor, $duracion_unidad, $precio, $id_servicio, $id_negocio_session);

        if ($stmt->execute()) {
            $descripcion_audit = "Se actualizó el servicio '{$nombre}' (ID: {$id_servicio}).";
            registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'UPDATE_SERVICE', $descripcion_audit); // CORRECCIÓN: Ya estaba, pero se confirma.

            header("Location: servicios_lista.php?status=success_edit");
        } else {
            header("Location: servicios_editar.php?id=" . $id_servicio . "&status=error&message=" . urlencode($stmt->error));
        }
        $stmt->close();
    } else {
        header("Location: servicios_editar.php?id=" . $id_servicio . "&status=error&message=" . urlencode($conn->error));
    }

    exit();
}
?>