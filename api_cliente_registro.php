<?php
// Revisado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM en fecha Dec/01/2025 //
require_once 'config.php';
require_once 'audit_log.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// 1. Recoger y validar datos
$nombre = trim($input['nombre_completo'] ?? '');
$celular = trim($input['numero_celular'] ?? '');
$email = trim($input['correo_electronico'] ?? '');
$id_pais = (int)($input['id_pais'] ?? 0);
$id_estado = (int)($input['id_estado'] ?? 0);
$id_negocio = (int)($input['id_negocio'] ?? 0); // Ahora lo recibimos del formulario
$direccion1 = trim($input['direccion1'] ?? '');
$direccion2 = trim($input['direccion2'] ?? '');
$ciudad = trim($input['ciudad'] ?? '');
$zip_code = trim($input['zip_code'] ?? '');

// Los campos de dirección no son obligatorios, pero los demás sí.
if (empty($nombre) || empty($celular) || empty($email) || $id_pais <= 0 || $id_estado <= 0 || $id_negocio <= 0) { 
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Todos los campos son obligatorios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'El formato del correo electrónico no es válido.']);
    exit;
}

// 2. Iniciar transacción y verificar duplicados
$conn->begin_transaction();

try {
    // Verificar si el correo o el celular ya existen
    $sql_check = "SELECT id_cliente FROM j106_clientes WHERE correo_electronico = ? OR numero_celular = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ss", $email, $celular);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        throw new Exception("El correo electrónico o número de teléfono ya está registrado.");
    }
    $stmt_check->close();

    // 3. Insertar el nuevo cliente
    $sql_insert = "INSERT INTO j106_clientes (
                        nombre_completo, numero_celular, correo_electronico, 
                        direccion1, direccion2, ciudad, zip_code,
                        id_pais, id_estado, id_negocio
                   ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("sssssssiii", 
        $nombre, $celular, $email, $direccion1, $direccion2, $ciudad, $zip_code, $id_pais, $id_estado, $id_negocio
    );
    
    if (!$stmt_insert->execute()) {
        throw new Exception("Error al registrar el cliente: " . $stmt_insert->error);
    }
    $id_nuevo_cliente = $stmt_insert->insert_id;
    $stmt_insert->close();

    // Obtener el email del negocio para la copia del correo
    $sql_negocio = "SELECT email FROM j102_negocios WHERE id_negocio = ?";
    $stmt_negocio = $conn->prepare($sql_negocio);
    $stmt_negocio->bind_param("i", $id_negocio);
    $stmt_negocio->execute();
    $email_negocio = $stmt_negocio->get_result()->fetch_assoc()['email'] ?? SMTP_USERNAME;
    $stmt_negocio->close();

    // 4. Enviar correo de bienvenida
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = (SMTP_SECURE == 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(SMTP_USERNAME, 'ZApp Citas');
        $mail->addAddress($email, $nombre);
        $mail->addBCC($email_negocio); // Copia oculta para el propietario
        $mail->isHTML(true);
        $mail->Subject = '¡Bienvenido a ZApp Citas!';
        $mail->Body    = "Hola " . htmlspecialchars($nombre) . ",<br><br>¡Tu registro ha sido completado con éxito!<br>Ya puedes iniciar sesión con tu número de teléfono para agendar tus citas.<br><br>Gracias por preferirnos.";
        $mail->send();
    } catch (Exception $e) {
        // Si el correo falla, no detenemos el proceso, pero podríamos registrarlo.
    }

    // 5. Confirmar transacción y registrar auditoría
    $conn->commit();
    registrar_auditoria($conn, null, $id_negocio, 'CLIENT_REGISTER_SUCCESS', "Nuevo cliente '{$nombre}' registrado desde la SPA.");

    echo json_encode(['success' => true, 'message' => '¡Registro exitoso! Se ha enviado un correo de bienvenida.']);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(409); // Conflict
    echo json_encode(['error' => $e->getMessage()]);
}

?>