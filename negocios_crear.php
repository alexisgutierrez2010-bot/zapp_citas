<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';
require 'vendor/autoload.php'; // Para PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Solo el rol Administrador puede acceder a esta página.
if (strcasecmp(trim($rol_session ?? ''), 'Administrador') != 0) {
    header("Location: dashboard.php?status=error&message=" . urlencode("Acceso no autorizado."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- LÓGICA DE PROCESAMIENTO ---
    $nombre_negocio = trim($_POST['nombre_negocio']);
    $email_negocio = trim($_POST['email']);
    $country_code = trim($_POST['country_code']);
    $telefono_local = trim($_POST['telefono_local']);
    $admin_user = trim($_POST['admin_user']);
    $admin_pass = $_POST['admin_pass'];
    $admin_email = trim($_POST['admin_email']);
    
    // Nuevos campos del formulario extendido
    $id_categoria_negocio = !empty($_POST['id_categoria_negocio']) ? (int)$_POST['id_categoria_negocio'] : null;
    $direccion1 = trim($_POST['direccion1']);
    $direccion2 = trim($_POST['direccion2']);
    $id_pais = (int)$_POST['id_pais'];
    $id_estado = (int)$_POST['id_estado'];
    $ciudad = trim($_POST['ciudad']);
    $zip_code = trim($_POST['zip_code']);
    $dias_trabajo = !empty($_POST['dias_trabajo']) ? implode(',', $_POST['dias_trabajo']) : '';
    $hora_inicio = $_POST['hora_inicio'];
    $hora_cierre = $_POST['hora_cierre'];
    $intervalo_minutos = (int)$_POST['intervalo_minutos'];

    // Validaciones básicas
    // Se añaden validaciones para los nuevos campos obligatorios
    if (empty($nombre_negocio) || empty($email_negocio) || empty($admin_user) || empty($admin_pass) || empty($admin_email) || $id_pais <= 0 || $id_estado <= 0 || empty($dias_trabajo)) {
        header("Location: negocios_nuevo.php?message_key=" . urlencode("Todos los campos son obligatorios."));
        exit();
    }

    $telefono_negocio = $country_code . ' ' . preg_replace('/[^0-9]/', '', $telefono_local);

    $conn->begin_transaction();

    try {
        // Verificar si el usuario ya existe
        $stmt_check = $conn->prepare("SELECT id_usuario FROM j100_usuarios WHERE nombre_usuario = ? OR correo_electronico = ?");
        $stmt_check->bind_param("ss", $admin_user, $admin_email);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            throw new Exception("El nombre de usuario o el correo electrónico del propietario ya están en uso.");
        }
        $stmt_check->close();

        // 1. Crear el negocio
        // Se asigna el estado 4 (Pendiente por Aprobar) por defecto y se incluye el teléfono.
        $sql_negocio = "INSERT INTO j102_negocios (
                            nombre_negocio, telefono, email, activo, fecha_registro, dias_prueba, 
                            id_categoria_negocio, direccion1, direccion2, id_pais, id_estado, ciudad, zip_code, 
                            dias_trabajo, hora_inicio, hora_cierre, intervalo_minutos
                        ) VALUES (?, ?, ?, 4, NOW(), 30, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_negocio = $conn->prepare($sql_negocio);
        $stmt_negocio->bind_param("sssissiisssssi", 
            $nombre_negocio, $telefono_negocio, $email_negocio, 
            $id_categoria_negocio, $direccion1, $direccion2, $id_pais, $id_estado, $ciudad, $zip_code, 
            $dias_trabajo, $hora_inicio, $hora_cierre, $intervalo_minutos
        );
        if (!$stmt_negocio->execute()) {
            throw new Exception("Error al crear el negocio: " . $stmt_negocio->error);
        }
        $id_nuevo_negocio = $stmt_negocio->insert_id;
        $stmt_negocio->close();

        // 2. Crear el usuario propietario
        $password_hash = password_hash($admin_pass, PASSWORD_DEFAULT);
        $rol = 'Propietario';
        $sql_usuario = "INSERT INTO j100_usuarios (id_negocio, nombre_usuario, correo_electronico, password_hash, rol, activo) VALUES (?, ?, ?, ?, ?, 1)";
        $stmt_usuario = $conn->prepare($sql_usuario);
        $stmt_usuario->bind_param("issss", $id_nuevo_negocio, $admin_user, $admin_email, $password_hash, $rol);
        if (!$stmt_usuario->execute()) {
            throw new Exception("Error al crear el usuario propietario: " . $stmt_usuario->error);
        }
        $id_nuevo_usuario = $stmt_usuario->insert_id;
        $stmt_usuario->close();

        // 3. Confirmar transacción y auditar
        $conn->commit();
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_nuevo_negocio, 'ADMIN_CREATE_BUSINESS', "Admin creó el negocio '{$nombre_negocio}' y el propietario '{$admin_user}'.");

        // --- INICIO: Lógica de Envío de Correos ---
        try {
            // Construct base URL dynamically
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $base_path = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
            $base_url = "{$protocol}://{$host}{$base_path}";

            $spa_owner_link = $base_url . 'spa_owner.php';
            $spa_client_link = $base_url . 'spa_client.php';
            $admin_panel_link = $base_url . 'sesion_iniciar.php';

            // Obtener el correo del administrador principal
            $sql_admin = "SELECT correo_electronico FROM j100_usuarios WHERE rol = 'Administrador' LIMIT 1";
            $admin_email_master = $conn->query($sql_admin)->fetch_assoc()['correo_electronico'] ?? null;

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = (SMTP_SECURE == 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(SMTP_FROM_EMAIL, 'Sistema ZApp Citas');
            $mail->isHTML(true);

            // Correo para el nuevo propietario
            $mail->addAddress($admin_email, $admin_user);
            $mail->Subject = "¡Bienvenido a ZApp Citas! Tu Negocio Creado (Pendiente de Aprobación)";
            $mail->Body    = "
                <html><body>
                    <h2>¡Bienvenido a ZApp Citas!</h2>
                    <p>Hola " . htmlspecialchars($admin_user) . ",</p>
                    <p>Tu negocio <strong>'" . htmlspecialchars($nombre_negocio) . "'</strong> ha sido creado en ZApp Citas.</p>
                    <p>Tus credenciales de acceso son:</p>
                    <ul>
                        <li><strong>Teléfono del Negocio:</strong> " . htmlspecialchars($telefono_negocio) . "</li>
                        <li><strong>Contraseña:</strong> " . htmlspecialchars($admin_pass) . "</li>
                    </ul>
                    <p><strong>Importante:</strong> Tu negocio está actualmente en estado 'Pendiente de Aprobación'. Un administrador revisará tu solicitud y te notificará una vez que tu negocio esté activo.</p>
                    <p>Una vez aprobado, podrás acceder a la App del Propietario para gestionar tu agenda, clientes y servicios: <a href='{$spa_owner_link}'>{$spa_owner_link}</a></p>
                    <p>Tus clientes podrán agendar citas a través de la App del Cliente: <a href='{$spa_client_link}'>{$spa_client_link}</a></p>
                    <p>Te recomendamos cambiar tu contraseña una vez que inicies sesión por primera vez.</p>
                    <p>¡Gracias por unirte a ZApp Citas!</p>
                </body></html>";
            $mail->send();
            $mail->clearAddresses();

            // Correo para el administrador principal
            if ($admin_email_master) {
                $mail->addAddress($admin_email_master);
                $mail->Subject = "Nuevo Negocio Registrado: " . $nombre_negocio . " (Pendiente de Aprobación)";
                $mail->Body    = "Se ha registrado un nuevo negocio en ZApp Citas y está pendiente de tu aprobación.<br><br><strong>Detalles del Negocio:</strong><br>Nombre: " . htmlspecialchars($nombre_negocio) . "<br>Teléfono: " . htmlspecialchars($telefono_negocio) . "<br>Email de Contacto: " . htmlspecialchars($email_negocio) . "<br><br><strong>Detalles del Propietario:</strong><br>Nombre de Usuario: " . htmlspecialchars($admin_user) . "<br>Email de Acceso: " . htmlspecialchars($admin_email) . "<br><br>Por favor, revisa y aprueba este negocio en el Panel de Administración: <a href='{$admin_panel_link}'>{$admin_panel_link}</a>";
                $mail->send();
            }
        } catch (Exception $e) {
            error_log("Error al enviar correos de registro de negocio (Admin Panel): " . $e->getMessage());
        }
        // --- FIN: Lógica de Envío de Correos ---

        header("Location: negocios_configuracion.php?status=success&message=" . urlencode("Negocio y propietario creados con éxito. Se han enviado correos de notificación."));
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: negocios_nuevo.php?message_key=" . urlencode($e->getMessage()));
        exit();
    }
}