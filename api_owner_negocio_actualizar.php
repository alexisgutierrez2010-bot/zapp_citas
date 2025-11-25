<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-20-2025).
session_start();
require_once 'config.php';
require_once 'audit_log.php';

header('Content-Type: application/json');

// Seguridad: Verificar que el propietario ha iniciado sesión
if (!isset($_SESSION['owner_loggedin']) || $_SESSION['owner_loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$id_negocio_session = $_SESSION['owner_id_negocio'];
$input = json_decode(file_get_contents('php://input'), true);

$nombre_negocio = trim($input['nombre_negocio'] ?? '');
$email = trim($input['email'] ?? '');
$dias_trabajo_raw = $input['dias_trabajo'] ?? [];
if (!is_array($dias_trabajo_raw)) {
    $dias_trabajo_raw = []; // Asegurarse de que sea un array para implode
}
$dias_trabajo = implode(',', $dias_trabajo_raw);
$hora_inicio = $input['hora_inicio'] ?? '';
$hora_cierre = $input['hora_cierre'] ?? '';
$intervalo_minutos = (int)($input['intervalo_minutos'] ?? 0);

$conn->begin_transaction();

try {
    // Validar el email solo si no está vacío
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("El formato del email de contacto no es válido.");
    }

    // Verificar que el email no esté en uso por OTRO negocio
    if (!empty($email)) {
        $sql_check_email = "SELECT id_negocio FROM j102_negocios WHERE email = ? AND id_negocio != ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param("si", $email, $id_negocio_session);
        $stmt_check_email->execute();
        if ($stmt_check_email->get_result()->num_rows > 0) {
            throw new Exception("El correo electrónico ya está en uso por otro negocio.");
        }
    }

    $sql = "UPDATE j102_negocios SET 
                nombre_negocio = ?, email = ?,
                dias_trabajo = ?, hora_inicio = ?, hora_cierre = ?, intervalo_minutos = ?
            WHERE id_negocio = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssii", 
        $nombre_negocio, $email, 
        $dias_trabajo, $hora_inicio, $hora_cierre, $intervalo_minutos,
        $id_negocio_session
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar los datos del negocio: " . $stmt->error);
    }
    $stmt->close();

    $conn->commit();
    registrar_auditoria($conn, (int)$_SESSION['owner_id_usuario'], (int)$id_negocio_session, 'OWNER_SPA_BUSINESS_UPDATE', "Propietario actualizó los datos de su negocio.");
    echo json_encode(['success' => true, 'message' => 'Configuración del negocio guardada con éxito.']);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>