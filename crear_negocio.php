<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-14-2025). CORRECCIÓN: Añadida la lógica de procesamiento que faltaba.
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';
require 'vendor/autoload.php'; // Para PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- LÓGICA DE PROCESAMIENTO ---
    $nombre_negocio = trim($_POST['nombre_negocio']);
    $email_negocio = trim($_POST['email']);
    $country_code = trim($_POST['country_code']);
    $telefono_local = trim($_POST['telefono_local']);
    $admin_user = trim($_POST['admin_user']);
    $admin_pass = $_POST['admin_pass'];
    $admin_email = trim($_POST['admin_email']);

    // Validaciones básicas
    if (empty($nombre_negocio) || empty($email_negocio) || empty($admin_user) || empty($admin_pass) || empty($admin_email)) {
        header("Location: crear_negocio.php?message_key=" . urlencode("Todos los campos son obligatorios."));
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
        $sql_negocio = "INSERT INTO j102_negocios (nombre_negocio, telefono, email, activo, fecha_registro, dias_prueba) VALUES (?, ?, ?, 4, NOW(), 30)";
        $stmt_negocio = $conn->prepare($sql_negocio);
        $stmt_negocio->bind_param("sss", $nombre_negocio, $telefono_negocio, $email_negocio);
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

            // Obtener el correo del administrador principal (Master)
            $sql_admin = "SELECT correo_electronico FROM j100_usuarios WHERE rol = 'Master' LIMIT 1";
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

        header("Location: negocios_configuracion.php?id={$id_nuevo_negocio}&status=success&message_key=" . urlencode("Negocio y propietario creados con éxito. Se han enviado correos de notificación."));
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: crear_negocio.php?message_key=" . urlencode($e->getMessage()));
        exit();
    }
}

// --- VISTA DEL FORMULARIO ---
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Negocio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Crear Nuevo Negocio</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        if (isset($_GET['message_key'])) {
                            $message = htmlspecialchars($_GET['message_key']);
                            echo "<div class='alert alert-danger'>" . htmlspecialchars($message) . "</div>";
                        }
                        ?>
                        <form action="crear_negocio.php" method="POST">
                            <p class="text-muted">Complete los datos básicos. Podrá añadir más detalles (horarios, imagen, etc.) después de crearlo.</p>
                            <div class="mb-3">
                                <label for="nombre_negocio" class="form-label">Nombre del Negocio</label>
                                <input type="text" class="form-control" id="nombre_negocio" name="nombre_negocio" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="telefono_local" class="form-label">Teléfono del Negocio</label>
                                    <div class="input-group">
                                        <select class="form-select" id="country_code" name="country_code" style="max-width: 150px;" required>
                                            <option>Cargando...</option>
                                        </select>
                                        <input type="tel" class="form-control" id="telefono_local" name="telefono_local" placeholder="Número local" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email de Contacto del Negocio</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <hr>
                            <h5 class="mt-3">Crear Usuario Propietario</h5>
                            <p class="text-muted">Se creará un usuario 'Propietario' para este nuevo negocio.</p>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="admin_user" class="form-label">Nombre de Usuario Propietario</label>
                                    <input type="text" class="form-control" id="admin_user" name="admin_user" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="admin_pass" class="form-label">Contraseña para Propietario</label>
                                    <input type="password" class="form-control" id="admin_pass" name="admin_pass" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="admin_email" class="form-label">Email del Usuario Propietario</label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Crear Negocio y Usuario Propietario</button>
                                <a href="negocios_configuracion.php" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Cargar la lista de países para el selector de código de teléfono
        document.addEventListener('DOMContentLoaded', async function() {
            const countryCodeSelect = document.getElementById('country_code');
            try {
                const response = await fetch('api_paises.php');
                const paises = await response.json();
                countryCodeSelect.innerHTML = paises.map(pais => 
                    `<option value="${pais.codigo_telefono}" ${pais.id_pais == 1 ? 'selected' : ''}>${pais.nombre_pais} (${pais.codigo_telefono})</option>`
                ).join('');
            } catch (error) {
                console.error("Error cargando códigos de país:", error);
                // Opcional: Mostrar un mensaje de error al usuario
            }
        });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>