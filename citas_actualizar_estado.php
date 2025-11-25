<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-20-2025).
require_once 'auth_check.php';
require_once 'audit_log.php';
require_once 'config.php';

// 1. Recoger y validar los datos de la URL
$id_cita = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$nuevo_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

if ($id_cita <= 0) {
    header("Location: citas_lista.php?status=error&message=" . urlencode("ID de cita inválido."));
    exit();
}

// 2. Validar que el estado sea uno de los permitidos
$estados_permitidos = ['Completada', 'Cancelada', 'Pospuesta', 'Pendiente', 'Confirmada', 'No Asistió', 'Vencida'];
if (!in_array($nuevo_estado, $estados_permitidos)) {
    header("Location: citas_lista.php?status=error&message=" . urlencode("Estado no válido."));
    exit();
}

// 3. Preparar la consulta SQL de actualización
$sql = "UPDATE j108_citas SET estado_cita = ? WHERE id_cita = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("si", $nuevo_estado, $id_cita);

    if ($stmt->execute()) {
        $descripcion_audit = "Se actualizó el estado de la cita (ID: {$id_cita}) a '{$nuevo_estado}'.";
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'UPDATE_APPOINTMENT_STATUS', $descripcion_audit);

        // Aquí, en el futuro, podríamos actualizar el evento en Google Calendar
        $stmt->close();
        
        // Si el estado es Cancelada o Completada, enviamos un correo.
        if ($nuevo_estado == 'Cancelada' || $nuevo_estado == 'Completada') {
            $accion_correo = ($nuevo_estado == 'Cancelada') ? 'CANCELADA' : 'COMPLETADA';
            header("Location: citas_confirmar_envio.php?id_cita=" . $id_cita . "&accion=" . $accion_correo);
        } else {
            header("Location: citas_lista.php?status=success_status");
        }
        exit();
    } else {
        header("Location: citas_lista.php?status=error&message=" . urlencode($stmt->error));
    }
    $stmt->close();
} else {
    header("Location: citas_lista.php?status=error&message=" . urlencode($conn->error));
}

exit();
?>