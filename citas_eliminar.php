<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
require_once 'auth_check.php';
require_once 'audit_log.php';
require_once 'config.php';

// CORRECCIÓN: Añadir las declaraciones 'use' para que PHPMailer sea reconocido.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$es_administrador = (isset($rol_session) && strcasecmp(trim($rol_session), 'Administrador') == 0);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Recoger y validar el ID de la cita
    $id_cita = isset($_POST['id_cita']) ? (int)$_POST['id_cita'] : 0;

    if ($id_cita <= 0) {
        header("Location: citas_lista.php?status=error&message=" . urlencode("ID de cita inválido."));
        exit();
    }

    // Notificar al cliente ANTES de eliminar
    $_POST['id_cita'] = $id_cita;
    $_POST['accion'] = 'CANCELADA';
    // ob_start();
    // Incluimos el script de envío pero capturamos su salida para que no redirija
    // include 'enviar_email.php'; // SUSPENDIDO TEMPORALMENTE
    // ob_end_clean(); // Descartamos la salida (la redirección de enviar_email.php)

    // 2. Preparar la consulta SQL de eliminación
    if ($es_administrador) {
        $sql = "UPDATE j108_citas SET estado_cita = 'Cancelada' WHERE id_cita = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_cita);
    } else {
        $sql = "UPDATE j108_citas SET estado_cita = 'Cancelada' WHERE id_cita = ? AND id_negocio = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id_cita, $id_negocio_session);
    }

    if ($stmt) {
        if ($stmt->execute()) {
            $descripcion_audit = "Se canceló la cita (ID: {$id_cita}) desde el panel de administración.";
            registrar_auditoria($conn, $_SESSION['id_usuario'], null, 'CANCEL_APPOINTMENT', $descripcion_audit);

            header("Location: citas_lista.php?status=success_delete");
        } else {
            header("Location: citas_lista.php?status=error&message=" . urlencode("Error al cancelar la cita: " . $stmt->error));
        }
        $stmt->close();
    } else {
        header("Location: citas_lista.php?status=error&message=" . urlencode($conn->error));
    }

    exit();