<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-27-2025).
session_start();
require_once 'api_cliente_session_check.php'; // 1. Guardián de sesión y timeout
header('Content-Type: application/json'); // 2. Establecer cabecera
require_once 'config.php'; // 3. Configuración de BD
require_once 'audit_log.php';
require 'vendor/autoload.php'; // Para PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


$input = json_decode(file_get_contents('php://input'), true);

$id_cliente = (int)($input['id_cliente'] ?? 0);
$id_negocio = (int)($input['id_negocio'] ?? 0);
$id_servicio = (int)($input['id_servicio'] ?? 0);
$fecha_hora_inicio_str = trim($input['fecha_hora_inicio'] ?? '');
$descripcion_trabajo = trim($input['descripcion_trabajo'] ?? '');
 
if ($id_cliente <= 0 || $id_negocio <= 0 || $id_servicio <= 0 || empty($fecha_hora_inicio_str)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos para agendar la cita.']);
    exit;
}

// Seguridad: Podríamos verificar si el cliente que agenda es el que está en sesión, si tuviéramos sesión de cliente.
// Por ahora, confiamos en el ID enviado desde el frontend.

// Obtener datos del servicio, cliente y negocio para la cita y el correo
$sql_servicio = "SELECT duracion_valor, duracion_unidad FROM j104_servicios WHERE id_servicio = ? AND id_negocio = ? AND activo = 1";
$stmt_servicio = $conn->prepare($sql_servicio);
$stmt_servicio->bind_param("ii", $id_servicio, $id_negocio);
$stmt_servicio->execute();
$result_servicio = $stmt_servicio->get_result();
$servicio = $result_servicio->fetch_assoc();
$stmt_servicio->close();

if (!$servicio) {
    http_response_code(404);
    echo json_encode(['error' => 'Servicio no encontrado o no pertenece a este negocio.']);
    exit;
}

$fecha_hora_inicio = new DateTime($fecha_hora_inicio_str);
$fecha_hora_fin = clone $fecha_hora_inicio;

// Calcular fecha_hora_fin basado en la duración del servicio
$interval_spec = 'PT' . $servicio['duracion_valor'];
if ($servicio['duracion_unidad'] === 'Horas') $interval_spec .= 'H';
else if ($servicio['duracion_unidad'] === 'Dias') $interval_spec .= 'D';
else $interval_spec .= 'M'; // Minutos por defecto
$fecha_hora_fin->add(new DateInterval($interval_spec));

// Si la descripción viene vacía, ponemos un texto por defecto.
$descripcion_final = !empty($descripcion_trabajo) ? $descripcion_trabajo : 'Cita agendada por el cliente.';

$sql = "INSERT INTO j108_citas (id_negocio, id_cliente, id_servicio, fecha_hora_inicio, fecha_hora_fin, estado_cita, descripcion_trabajo) VALUES (?, ?, ?, ?, ?, 'Pendiente', ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiisss", $id_negocio, $id_cliente, $id_servicio, $fecha_hora_inicio->format('Y-m-d H:i:s'), $fecha_hora_fin->format('Y-m-d H:i:s'), $descripcion_final);

if ($stmt->execute()) {
    $id_nueva_cita = $stmt->insert_id;
    registrar_auditoria($conn, null, $id_negocio, 'CLIENT_SELF_BOOKING', "Cliente ID {$id_cliente} agendó nueva cita ID {$id_nueva_cita}.");

    // --- INICIO: LÓGICA DE ENVÍO DE CORREO REACTIVADA Y MEJORADA ---
    try {
        // Obtener datos para el correo
        $sql_datos_correo = "SELECT 
                                c.nombre_completo AS nombre_cliente, c.correo_electronico AS email_cliente,
                                n.nombre_negocio, n.email AS email_negocio,
                                s.nombre_servicio
                             FROM j106_clientes c
                             JOIN j102_negocios n ON c.id_negocio = n.id_negocio
                             JOIN j104_servicios s ON n.id_negocio = s.id_negocio
                             WHERE c.id_cliente = ? AND n.id_negocio = ? AND s.id_servicio = ?";
        $stmt_datos = $conn->prepare($sql_datos_correo);
        $stmt_datos->bind_param("iii", $id_cliente, $id_negocio, $id_servicio);
        $stmt_datos->execute();
        $datos_correo = $stmt_datos->get_result()->fetch_assoc();
        $stmt_datos->close();

        if ($datos_correo) {
            $mail = new PHPMailer(true);
            // Configuración del servidor (tomada de config.php)
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = SMTP_AUTH;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->CharSet = 'UTF-8';

            // Contenido del correo
            $fecha_formateada = $fecha_hora_inicio->format('d/m/Y');
            $hora_formateada = $fecha_hora_inicio->format('h:i A');
            $asunto = "Nueva Cita Agendada: {$datos_correo['nombre_servicio']} para {$datos_correo['nombre_cliente']}";
            $cuerpoHTML = "
                <h2>Nueva Cita Agendada</h2>
                <p>Se ha registrado una nueva cita a través del portal de clientes.</p>
                <ul>
                    <li><strong>Negocio:</strong> {$datos_correo['nombre_negocio']}</li>
                    <li><strong>Cliente:</strong> {$datos_correo['nombre_cliente']}</li>
                    <li><strong>Servicio:</strong> {$datos_correo['nombre_servicio']}</li>
                    <li><strong>Fecha:</strong> {$fecha_formateada}</li>
                    <li><strong>Hora:</strong> {$hora_formateada}</li>
                    <li><strong>Notas del cliente:</strong> " . htmlspecialchars($descripcion_final) . "</li>
                </ul>
            ";

            // Enviar al propietario del negocio
            if (!empty($datos_correo['email_negocio'])) {
                $mail->addAddress($datos_correo['email_negocio']);
                $mail->Subject = $asunto;
                $mail->Body    = $cuerpoHTML;
                $mail->isHTML(true);
                $mail->send();
                $mail->clearAddresses();
            }

            // Enviar al cliente
            if (!empty($datos_correo['email_cliente'])) {
                $mail->addAddress($datos_correo['email_cliente']);
                $mail->Subject = "Confirmación de Cita: {$datos_correo['nombre_servicio']}";
                $mail->Body    = $cuerpoHTML;
                $mail->isHTML(true);
                $mail->send();
            }
        }
    } catch (Exception $e) {
        // No detener la ejecución si el correo falla, pero registrarlo.
        registrar_auditoria($conn, null, $id_negocio, 'EMAIL_FAIL', "Fallo al enviar correo para cita ID {$id_nueva_cita}. Error: {$mail->ErrorInfo}");
    }
    // --- FIN: LÓGICA DE ENVÍO DE CORREO ---

    echo json_encode(['success' => true, 'message' => '¡Tu cita ha sido agendada con éxito!', 'id_cita' => $id_nueva_cita]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al agendar la cita: ' . $stmt->error]);
}
?>