<?php
// Revisado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM en fecha Nov/22/2025 //
require_once 'config.php';
require_once 'audit_log.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once 'vendor/autoload.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$id_cita = (int)($input['id_cita'] ?? 0);
$id_cliente = (int)($input['id_cliente'] ?? 0);
$accion = trim($input['accion'] ?? '');

if ($id_cita <= 0 || $id_cliente <= 0 || empty($accion)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos.']);
    exit;
}

// 1. Verificar que la cita pertenece al cliente (¡muy importante por seguridad!)
$sql_check = "SELECT id_cita FROM j108_citas WHERE id_cita = ? AND id_cliente = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $id_cita, $id_cliente);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows === 0) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'No tienes permiso para modificar esta cita.']);
    exit;
}
$stmt_check->close();

// 2. Determinar el nuevo estado basado en la acción
$nuevo_estado = '';
if ($accion === 'cancelar') {
    $nuevo_estado = 'Cancelada';
} elseif ($accion === 'confirmar') {
    $nuevo_estado = 'Confirmada';
}else {
    http_response_code(400);
    echo json_encode(['error' => 'Acción no válida.']);
    exit;
}

// 3. Actualizar el estado de la cita
$sql_update = "UPDATE j108_citas SET estado_cita = ? WHERE id_cita = ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("si", $nuevo_estado, $id_cita);

if ($stmt_update->execute()) {
    // Registrar auditoría según la acción
    $audit_action = ($accion === 'confirmar') ? 'CLIENT_CONFIRM_APPOINTMENT' : 'CLIENT_CANCEL_APPOINTMENT';
    $audit_message = "Cliente ID {$id_cliente} {$accion}ó la cita ID {$id_cita}.";
    registrar_auditoria($conn, null, null, $audit_action, $audit_message);

    // Enviar correo de notificación
    try {
        // Obtener detalles para el correo
        $sql_details = "SELECT 
                            c.fecha_hora_inicio,
                            cl.nombre_completo AS nombre_cliente,
                            cl.correo_electronico AS email_cliente,
                            n.nombre_negocio,
                            n.email AS email_negocio
                        FROM j108_citas c
                        JOIN j106_clientes cl ON c.id_cliente = cl.id_cliente
                        JOIN j102_negocios n ON c.id_negocio = n.id_negocio
                        WHERE c.id_cita = ?";
        $stmt_details = $conn->prepare($sql_details);
        $stmt_details->bind_param("i", $id_cita);
        $stmt_details->execute();
        $details = $stmt_details->get_result()->fetch_assoc();
        $stmt_details->close();

        if ($details) {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = (SMTP_SECURE == 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(SMTP_USERNAME, 'ZApp Citas');
            
            // Enviar a ambos, cliente y negocio
            $mail->addAddress($details['email_cliente'], $details['nombre_cliente']);
            $mail->addBCC($details['email_negocio'], 'Notificación a Negocio');

            $mail->isHTML(true);
            $mail->Subject = "Tu cita ha sido {$nuevo_estado}";
            $mail->Body    = "Hola " . htmlspecialchars($details['nombre_cliente']) . ",<br><br>Te informamos que el estado de tu cita del " . date('d/m/Y \a \l\a\s H:i', strtotime($details['fecha_hora_inicio'])) . " ha sido actualizado a: <strong>{$nuevo_estado}</strong>.<br><br>Gracias.";
            $mail->send();
        }
    } catch (Exception $e) {
        // El correo falló, pero la operación principal fue exitosa.
        // No detenemos el proceso, pero podríamos registrar este error específico.
    }

    $success_message = ($accion === 'confirmar') ? 'Cita confirmada con éxito.' : 'Cita cancelada con éxito.';
    echo json_encode(['success' => true, 'message' => $success_message]);

} else {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo actualizar el estado de la cita.']);
}
$stmt_update->close();
?>