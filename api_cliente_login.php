<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
session_start();
require_once 'config.php';
require_once 'audit_log.php'; // Reutilizamos el log de auditoría

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $numero_celular = $input['numero_celular'] ?? '';
    $id_negocio_seleccionado = isset($input['id_negocio']) ? (int)$input['id_negocio'] : 0;

    // 2. Si el CAPTCHA es válido, buscar al cliente por número de celular
    if (empty($numero_celular)) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'El número de celular es obligatorio.']);
        exit;
    }

    
    // MODIFICACIÓN: Unimos con la tabla de negocios para obtener el nombre del negocio.
    // Esto nos permite listar las opciones si hay más de una cuenta.
    $sql = "SELECT c.id_cliente, c.nombre_completo, c.correo_electronico, c.id_negocio, n.nombre_negocio 
            FROM j106_clientes c 
            JOIN j102_negocios n ON c.id_negocio = n.id_negocio 
            WHERE c.numero_celular = ?";

    // Si el frontend ya nos envió una elección específica, filtramos por ella.
    if ($id_negocio_seleccionado > 0) {
        $sql .= " AND c.id_negocio = ?";
    }

    $stmt = $conn->prepare($sql);
    
    if ($id_negocio_seleccionado > 0) {
        $stmt->bind_param("si", $numero_celular, $id_negocio_seleccionado);
    } else {
        $stmt->bind_param("s", $numero_celular);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) { // Caso ideal: 1 sola cuenta o selección específica
        $cliente = $result->fetch_assoc();

        // CORRECCIÓN CRÍTICA: Guardar los datos del cliente en la sesión.
        $_SESSION['client_loggedin'] = true;
        $_SESSION['client_id'] = $cliente['id_cliente'];
        $_SESSION['client_nombre_completo'] = $cliente['nombre_completo'];
        $_SESSION['client_id_negocio'] = $cliente['id_negocio']; // Guardamos id_negocio para uso futuro
        $_SESSION['client_last_activity'] = time();

        registrar_auditoria($conn, null, $cliente['id_negocio'], 'CLIENT_LOGIN_SUCCESS', "Cliente '{$cliente['nombre_completo']}' (ID: {$cliente['id_cliente']}) inició sesión.");
        echo json_encode(['client' => $cliente]); // CORRECCIÓN: Envolver en 'client' para coincidir con JS
    } elseif ($result->num_rows > 1) {
        // Caso múltiple: El mismo teléfono está en varios negocios.
        // Devolvemos la lista para que el usuario elija en el frontend.
        $cuentas = [];
        while ($row = $result->fetch_assoc()) {
            $cuentas[] = $row;
        }
        // No iniciamos sesión todavía.
        echo json_encode(['multiple_accounts' => true, 'accounts' => $cuentas]);
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
