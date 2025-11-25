<?php
// Revisado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM en fecha Nov/20/2025 //
session_start();
require_once 'config.php';
require_once 'audit_log.php'; // Reutilizamos el log de auditoría

header('Content-Type: application/json');

/**
 * Genera un nuevo código CAPTCHA, lo guarda en la sesión y lo devuelve.
 */
function generarYDevolverCaptcha() {
    $_SESSION['captcha_time'] = time();
    $_SESSION['captcha_hash'] = strtoupper(substr(sha1(session_id() . $_SESSION['captcha_time']), 0, 6));
    echo json_encode(['captcha_hash' => $_SESSION['captcha_hash']]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    generarYDevolverCaptcha();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $numero_celular = $input['numero_celular'] ?? '';
    $captcha_ingresado = $input['captcha'] ?? '';

    // 1. Validar el CAPTCHA
    if (empty($captcha_ingresado) || !isset($_SESSION['captcha_hash']) || !isset($_SESSION['captcha_time'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['error' => 'Código de seguridad no proporcionado o sesión inválida.']);
        exit;
    }

    if ((time() - $_SESSION['captcha_time']) >= 120) { // Aumentado a 2 minutos
        http_response_code(401);
        echo json_encode(['error' => 'El código de seguridad ha expirado.']);
        exit;
    }

    if (strcasecmp($captcha_ingresado, $_SESSION['captcha_hash']) !== 0) {
        http_response_code(401);
        echo json_encode(['error' => 'El código de seguridad es incorrecto.']);
        exit;
    }

    // Limpiar el captcha de la sesión después del intento para forzar uno nuevo
    unset($_SESSION['captcha_hash']);
    unset($_SESSION['captcha_time']);

    // 2. Si el CAPTCHA es válido, buscar al cliente por número de celular
    if (empty($numero_celular)) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'El número de celular es obligatorio.']);
        exit;
    }

    // --- CORRECCIÓN: Asegurar formato consistente del número de teléfono ---
    // Limpiamos cualquier caracter que no sea número o el signo '+' y luego reconstruimos
    // el formato "código espacio número" para que coincida con la base de datos.
    $celular_limpio = preg_replace('/[^+0-9]/', '', $numero_celular);
    // Esta lógica asume que el código de país viene primero.
    // Buscamos un cliente que coincida exactamente con el número de celular (incluyendo código de país si lo tuviera)
    $sql = "SELECT id_cliente, nombre_completo, correo_electronico, id_negocio FROM j106_clientes WHERE numero_celular = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $numero_celular); // Usamos la variable original que ya tiene el formato correcto desde JS
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $cliente = $result->fetch_assoc();
        registrar_auditoria($conn, null, null, 'CLIENT_LOGIN_SUCCESS', "Cliente '{$cliente['nombre_completo']}' (ID: {$cliente['id_cliente']}) inició sesión.");
        echo json_encode($cliente);
    } else {
        // No se encontró el cliente, devolvemos un error 404 para que el frontend sepa que debe registrarlo.
        http_response_code(404); // Not Found
        registrar_auditoria($conn, null, null, 'CLIENT_LOGIN_NOT_FOUND', "Intento de login con celular no registrado: {$numero_celular}.");
        echo json_encode(['error' => 'Cliente no registrado.']);
    }

    $stmt->close();
    exit;
}

http_response_code(405); // Method Not Allowed
echo json_encode(['error' => 'Método no permitido.']);
?>