<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.

session_start();
require_once 'api_owner_session_check.php';
header('Content-Type: application/json');
require_once 'audit_log.php';

$conn->begin_transaction();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido.', 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $id_cita = (int)($input['id_cita'] ?? 0);
    $id_negocio = $_SESSION['owner_id_negocio'];

    if ($id_cita <= 0) {
        throw new Exception('ID de cita no válido.', 400);
    }

    // 1. Eliminar invitados asociados a la cita (si los hay)
    $stmt_invitados = $conn->prepare("DELETE FROM j109_invitados_cita WHERE id_cita = ?");
    $stmt_invitados->bind_param("i", $id_cita);
    $stmt_invitados->execute();
    $stmt_invitados->close();

    // 2. Eliminar la cita principal
    $stmt_cita = $conn->prepare("DELETE FROM j108_citas WHERE id_cita = ? AND id_negocio = ?");
    $stmt_cita->bind_param("ii", $id_cita, $id_negocio);
    $stmt_cita->execute();

    if ($stmt_cita->affected_rows > 0) {
        registrar_auditoria($conn, $_SESSION['owner_id_usuario'], $id_negocio, 'OWNER_CITA_DELETE_PHYSICAL', "Cita ID {$id_cita} eliminada.");
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'La cita ha sido eliminada.']);
    } else {
        throw new Exception('La cita no fue encontrada o no pertenece a su negocio.', 404);
    }
    $stmt_cita->close();

} catch (Exception $e) {
    $conn->rollback();
    $codigo_error = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($codigo_error);
    echo json_encode(['error' => $e->getMessage()]);
}