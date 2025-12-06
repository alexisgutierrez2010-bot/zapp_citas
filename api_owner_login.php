<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
session_start();
require_once 'config.php';
require_once 'audit_log.php'; // Reactivado

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
    $telefono_negocio = trim($input['telefono'] ?? '');
    $password = $input['password'] ?? '';
    $captcha_ingresado = $input['captcha'] ?? '';
    // --- BLOQUE DE VALIDACIÓN DE CAPTCHA DESACTIVADO PARA DESARROLLO ---
    /*
    if (empty($captcha_ingresado) || !isset($_SESSION['captcha_hash']) || !isset($_SESSION['captcha_time']) || (time() - $_SESSION['captcha_time']) >= 120 || strcasecmp($captcha_ingresado, $_SESSION['captcha_hash']) !== 0) {
        http_response_code(401);
        echo json_encode(['error' => 'El código de seguridad es incorrecto o ha expirado.']);
        exit;
    }
    */
    // Limpiar el captcha de la sesión después del intento para forzar uno nuevo
    unset($_SESSION['captcha_hash']);
    unset($_SESSION['captcha_time']);

    // 2. Validar campos obligatorios
    if (empty($telefono_negocio) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'El teléfono y la contraseña son obligatorios.']);
    exit;
}

// Normalizar el número de teléfono de entrada (solo dígitos)
$telefono_negocio_cleaned_input = preg_replace('/[^0-9]/', '', $telefono_negocio); 

// 3. Buscar el negocio por el número de teléfono, comparando versiones limpias
$sql_all_negocios = "SELECT id_negocio, nombre_negocio, telefono, activo, fecha_desactivacion FROM j102_negocios";
$result_all_negocios = $conn->query($sql_all_negocios);

$found_negocio = null;
if ($result_all_negocios) {
    while ($row = $result_all_negocios->fetch_assoc()) {
        $db_telefono_cleaned = preg_replace('/[^0-9]/', '', $row['telefono']);
        if ($db_telefono_cleaned === $telefono_negocio_cleaned_input) {
            $found_negocio = $row;
            break;
        }
    }
}

if (!$found_negocio) {
    http_response_code(404);
    echo json_encode(['error' => 'Negocio no encontrado con ese número de teléfono.']);
    exit;
}

// --- VALIDACIÓN DE PERÍODO DE PRUEBA ---
if ($found_negocio['activo'] == 2) { // 2 = Suspendido
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Tu cuenta ha sido suspendida. Por favor, contacta al administrador.']);
    exit;
}
if ($found_negocio['activo'] == 3) { // 3 = Eliminado
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Esta cuenta de negocio ya no existe.']);
    exit;
}
// --- FIN DE VALIDACIÓN ---

$id_negocio = $found_negocio['id_negocio'];
$nombre_negocio = $found_negocio['nombre_negocio'];

// 4. Buscar un usuario 'Propietario' para ese negocio y validar su contraseña
$sql_user = "SELECT id_usuario, nombre_usuario, password_hash, activo FROM j100_usuarios WHERE id_negocio = ? AND rol = 'Propietario'";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $id_negocio);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows > 0) { // Puede haber más de un propietario, validamos el primero que coincida
    while ($usuario = $result_user->fetch_assoc()) {
        if ($usuario['activo'] != 1) {
            continue; // Si este usuario propietario está inactivo, prueba con el siguiente (si hay más)
        }

        if (password_verify($password, $usuario['password_hash'])) {
            // ¡Login exitoso!
            // Almacenar datos del propietario en la sesión
            $_SESSION['owner_loggedin'] = true;
            $_SESSION['owner_id_usuario'] = $usuario['id_usuario'];
            $_SESSION['owner_nombre_usuario'] = $usuario['nombre_usuario'];
            $_SESSION['owner_id_negocio'] = (int)$id_negocio;
            $_SESSION['owner_nombre_negocio'] = $nombre_negocio;

            $_SESSION['owner_last_activity'] = time(); // Iniciar el contador de inactividad para la SPA
            $response_data = [
                'id_usuario' => $usuario['id_usuario'],
                'nombre_usuario' => $usuario['nombre_usuario'],
                'id_negocio' => $id_negocio,
                'nombre_negocio' => $nombre_negocio,
                'fecha_desactivacion' => $found_negocio['fecha_desactivacion'] // Enviamos la fecha de fin de prueba
            ];
            registrar_auditoria($conn, $usuario['id_usuario'], $id_negocio, 'OWNER_SPA_LOGIN_SUCCESS', "Propietario '{$usuario['nombre_usuario']}' inició sesión en la SPA."); // Reactivado
            echo json_encode($response_data);
            exit; // Salir después de un login exitoso
        }
    }
}

// Si llegamos aquí, no se encontró un propietario con esa contraseña o no hay propietario para el negocio
http_response_code(401);
echo json_encode(['error' => 'Contraseña incorrecta.']);
exit;
}

// Si no es GET ni POST, es un método no permitido.
http_response_code(405);
echo json_encode(['error' => 'Método no permitido.']);

?>