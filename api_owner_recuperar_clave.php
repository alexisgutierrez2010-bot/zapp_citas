<?php
// Elaborado por GEMINI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update: Dec-01-2025.

// --- SECUENCIA DE CARGA ESTÁNDAR ---
session_start();
require_once 'config.php';
require_once 'audit_log.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$telefono_completo = trim($input['telefono'] ?? '');

if (empty($telefono_completo)) {
    http_response_code(400);
    echo json_encode(['error' => 'El número de teléfono es requerido.']);
    exit;
}

// --- LÓGICA REFACTORIZADA PARA MANEJAR NEGOCIOS SIN USUARIO ---

// 1. Buscar primero el negocio por el número de teléfono.
$sql_negocio = "SELECT id_negocio, nombre_negocio FROM j102_negocios WHERE telefono = ? AND activo = 1 LIMIT 1";
$stmt_negocio = $conn->prepare($sql_negocio);
$stmt_negocio->bind_param("s", $telefono_completo);
$stmt_negocio->execute();
$result_negocio = $stmt_negocio->get_result();
$negocio = $result_negocio->fetch_assoc();
$stmt_negocio->close();

if ($negocio) {
    // 2. Si se encontró un negocio activo, buscar su usuario propietario.
    $id_negocio_encontrado = $negocio['id_negocio'];
    $sql_usuario = "SELECT id_usuario, nombre_usuario, correo_electronico FROM j100_usuarios WHERE id_negocio = ? AND rol = 'Propietario' AND activo = 1 LIMIT 1";
    $stmt_usuario = $conn->prepare($sql_usuario);
    $stmt_usuario->bind_param("i", $id_negocio_encontrado);
    $stmt_usuario->execute();
    $result_usuario = $stmt_usuario->get_result();
    $usuario = $result_usuario->fetch_assoc();
    $stmt_usuario->close();

    if (!$usuario) {
        // CASO CRÍTICO: El negocio existe, pero no tiene un usuario propietario activo asignado.
        // Lo registramos en la auditoría para que el admin pueda investigarlo.
        registrar_auditoria($conn, null, $id_negocio_encontrado, 'OWNER_RECOVERY_NO_USER', "Intento de recuperación para negocio '{$negocio['nombre_negocio']}' sin usuario propietario activo asignado.");
    } elseif (empty($usuario['correo_electronico'])) {
        // CASO CRÍTICO 2: El usuario propietario existe pero no tiene un email para enviarle la clave.
        registrar_auditoria($conn, $usuario['id_usuario'], $id_negocio_encontrado, 'OWNER_RECOVERY_NO_EMAIL', "Intento de recuperación para usuario '{$usuario['nombre_usuario']}' sin email registrado.");
    } else {
        // --- FLUJO NORMAL: Se encontró negocio y usuario con email ---
        $usuario['id_negocio'] = $id_negocio_encontrado;
        $usuario['nombre_negocio'] = $negocio['nombre_negocio'];

        // Si se encuentra el usuario y tiene un email, procedemos a enviar el correo.
        try {
            // 1. Generar una nueva contraseña temporal
            $nueva_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);
            $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
    
            // 2. Actualizar la contraseña en la base de datos
            $sql_update = "UPDATE j100_usuarios SET password_hash = ? WHERE id_usuario = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("si", $password_hash, $usuario['id_usuario']);
            $stmt_update->execute();
            $stmt_update->close();
    
            // 3. Enviar el correo con la nueva contraseña
            $mail = new PHPMailer(true);
            
            // Configuración SMTP (reutilizada)
            $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;
    
            // Contenido del correo
            $mail->setFrom(SMTP_USERNAME, $usuario['nombre_negocio']);
            $mail->addAddress($usuario['correo_electronico'], $usuario['nombre_usuario']);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Recuperación de Contraseña - ZApp Citas';
            $mail->Body    = "Hola {$usuario['nombre_usuario']},<br><br>" .
                             "Has solicitado recuperar tu contraseña para acceder al portal de gestión de tu negocio '{$usuario['nombre_negocio']}'.<br><br>" .
                             "Tu nueva contraseña temporal es: <b>{$nueva_password}</b><br><br>" .
                             "Por favor, inicia sesión con esta nueva contraseña y cámbiala inmediatamente desde la sección 'Mi Perfil' por una que recuerdes.<br><br>" .
                             "Atentamente,<br>El equipo de ZApp Citas.";
    
            $mail->send();
    
            // 4. Registrar en auditoría
            registrar_auditoria($conn, $usuario['id_usuario'], $usuario['id_negocio'], 'OWNER_PASSWORD_RECOVERY', "Se ha enviado una nueva contraseña al propietario '{$usuario['nombre_usuario']}'.");
    
        } catch (Exception $e) {
            // Si el envío de correo falla, no rompemos la app, solo lo registramos.
            error_log("Fallo al enviar correo de recuperación para usuario ID {$usuario['id_usuario']}: " . $e->getMessage());
        }
    }
}

// IMPORTANTE: Por seguridad, siempre devolvemos un mensaje de éxito genérico.
// Esto evita que alguien pueda usar esta función para averiguar qué números de teléfono están registrados.
echo json_encode(['success' => true, 'message' => 'Si existe una cuenta asociada a este número de teléfono, se ha enviado un correo electrónico con las instrucciones para recuperar tu contraseña.']);

?>
