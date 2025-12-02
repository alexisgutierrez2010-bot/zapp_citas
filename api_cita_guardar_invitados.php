<?php
session_start(); // CORRECCIÓN: Iniciar la sesión para que el guardián funcione.
require_once 'api_admin_session_check.php'; // CORRECCIÓN: Usar el guardián de API correcto.
require_once 'config.php';
require_once 'audit_log.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id_cita = $input['id_cita'] ?? 0;
$invitados = $input['invitados'] ?? [];

if ($id_cita <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de cita no válido.']);
    exit;
}

$conn->begin_transaction();

try {
    // 1. Eliminar todos los invitados existentes para esta cita para simplificar la lógica.
    $stmt_delete = $conn->prepare("DELETE FROM j109_invitados_cita WHERE id_cita = ?");
    $stmt_delete->bind_param("i", $id_cita);
    $stmt_delete->execute();
    $stmt_delete->close();

    // 2. Insertar la nueva lista de invitados.
    if (!empty($invitados)) {
        $sql_insert = "INSERT INTO j109_invitados_cita (id_cita, nombre_invitado, correo_electronico_invitado, numero_celular_invitado) VALUES (?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);

        foreach ($invitados as $invitado) {
            $stmt_insert->bind_param("isss", $id_cita, $invitado['nombre'], $invitado['email'], $invitado['telefono']);
            $stmt_insert->execute();
        }
        $stmt_insert->close();
    }

    $conn->commit();
    registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'UPDATE_GUESTS', "Se actualizaron los invitados para la cita ID {$id_cita}.");
    echo json_encode(['success' => true, 'message' => 'Invitados guardados con éxito.']);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar los invitados: ' . $e->getMessage()]);
}
?>