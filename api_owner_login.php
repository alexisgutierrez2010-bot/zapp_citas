<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
session_start();
require_once 'config.php';
require_once 'audit_log.php'; // Reactivado

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $telefono_negocio = trim($input['telefono'] ?? '');
    $password = $input['password'] ?? '';

    // 2. Validar campos obligatorios
    if (empty($telefono_negocio) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'El teléfono y la contraseña son obligatorios.']);
    exit;
}

// Normalizar el número de teléfono de entrada (solo dígitos)
$telefono_negocio_cleaned_input = preg_replace('/[^0-9]/', '', $telefono_negocio); 

// 3. Buscar el negocio por el número de teléfono de forma directa y eficiente.
$sql_negocio = "SELECT id_negocio, nombre_negocio, telefono, activo, fecha_desactivacion, background_image_type FROM j102_negocios WHERE REPLACE(REPLACE(REPLACE(telefono, '+', ''), ' ', ''), '-', '') LIKE CONCAT('%', ?, '%')";
$stmt_negocio = $conn->prepare($sql_negocio);
$stmt_negocio->bind_param("s", $telefono_negocio_cleaned_input);
$stmt_negocio->execute();
$result_negocios = $stmt_negocio->get_result();

$negocios_candidatos = [];
while ($row = $result_negocios->fetch_assoc()) {
    $negocios_candidatos[] = $row;
}
$stmt_negocio->close();

if (empty($negocios_candidatos)) {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'El negocio no fue encontrado.', 'error_key' => 'login_error_business_not_found']);
    exit;
}

// 4. Iterar sobre los negocios encontrados para ver si la contraseña coincide con algún usuario propietario
foreach ($negocios_candidatos as $found_negocio) {
    $id_negocio = $found_negocio['id_negocio'];
    $nombre_negocio = $found_negocio['nombre_negocio'];

    // Buscar un usuario 'Propietario' para este negocio específico
    $sql_user = "SELECT id_usuario, nombre_usuario, password_hash, activo FROM j100_usuarios WHERE id_negocio = ? AND rol = 'Propietario'";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $id_negocio);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    while ($usuario = $result_user->fetch_assoc()) {
        if (password_verify($password, $usuario['password_hash'])) {
            // ¡Contraseña correcta! Ahora validamos el estado.
            
            if ($usuario['activo'] != 1) continue; // Usuario inactivo, saltar

            // --- VALIDACIÓN DE ESTADO DEL NEGOCIO ---
            if ($found_negocio['activo'] == 2) { // 2 = Suspendido
                http_response_code(403);
                echo json_encode(['error' => "La cuenta del negocio '{$nombre_negocio}' se encuentra suspendida."]);
                exit;
            }
            if ($found_negocio['activo'] == 3) { // 3 = Eliminado
                http_response_code(403);
                echo json_encode(['error' => "La cuenta del negocio '{$nombre_negocio}' ha sido eliminada."]);
                exit;
            }
            if ($found_negocio['activo'] == 4) { // 4 = Pendiente
                http_response_code(403);
                echo json_encode(['error' => "La cuenta del negocio '{$nombre_negocio}' está pendiente de aprobación."]);
                exit;
            }
            // --- FIN VALIDACIÓN ---

            // ¡Login exitoso!
            // Almacenar datos del propietario en la sesión
            $_SESSION['owner_loggedin'] = true;
            $_SESSION['owner_id_usuario'] = $usuario['id_usuario'];
            $_SESSION['owner_nombre_usuario'] = $usuario['nombre_usuario'];
            $_SESSION['owner_id_negocio'] = (int)$id_negocio;
            $_SESSION['owner_nombre_negocio'] = $nombre_negocio;

            $_SESSION['owner_last_activity'] = time(); // Iniciar el contador de inactividad para la SPA
            $response_data = [
                'success' => true,
                'id_usuario' => $usuario['id_usuario'],
                'nombre_usuario' => $usuario['nombre_usuario'],
                'id_negocio' => $id_negocio,
                'nombre_negocio' => $nombre_negocio,
                'fecha_desactivacion' => $found_negocio['fecha_desactivacion'], // Enviamos la fecha de fin de prueba
                'has_background' => !empty($found_negocio['background_image_type']) // Informamos si tiene imagen
            ];
            registrar_auditoria($conn, $usuario['id_usuario'], $id_negocio, 'OWNER_SPA_LOGIN_SUCCESS', "Propietario '{$usuario['nombre_usuario']}' inició sesión en la SPA."); // Reactivado
            echo json_encode($response_data);
            exit; // Salir después de un login exitoso
        }
    }
    $stmt_user->close();
}

// Si llegamos aquí, no se encontró un propietario con esa contraseña o no hay propietario para el negocio
http_response_code(401);
echo json_encode(['error' => 'Las credenciales son incorrectas.', 'error_key' => 'login_error_credentials']);
exit;
}

// Si no es GET ni POST, es un método no permitido.
http_response_code(405);
echo json_encode(['error' => 'Método no permitido.']);