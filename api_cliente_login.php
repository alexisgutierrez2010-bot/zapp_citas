<?php
// Revisado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM en fecha Dec/01/2025 //
session_start();
require_once 'config.php';
require_once 'audit_log.php'; // Reutilizamos el log de auditoría

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $numero_celular = $input['numero_celular'] ?? '';

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

        // CORRECCIÓN CRÍTICA: Guardar los datos del cliente en la sesión.
        $_SESSION['client_loggedin'] = true;
        $_SESSION['client_id'] = $cliente['id_cliente'];
        $_SESSION['client_nombre_completo'] = $cliente['nombre_completo'];
        $_SESSION['client_id_negocio'] = $cliente['id_negocio']; // Guardamos id_negocio para uso futuro
        $_SESSION['client_last_activity'] = time();

        registrar_auditoria($conn, null, $cliente['id_negocio'], 'CLIENT_LOGIN_SUCCESS', "Cliente '{$cliente['nombre_completo']}' (ID: {$cliente['id_cliente']}) inició sesión.");
        echo json_encode(['client' => $cliente]); // CORRECCIÓN: Envolver en 'client' para coincidir con JS
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