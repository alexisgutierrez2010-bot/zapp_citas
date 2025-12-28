<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
session_start(); // RESTAURADO: El script principal es responsable de iniciar la sesión.
require_once 'api_owner_session_check.php'; // 1. Guardián de sesión y timeout
header('Content-Type: application/json'); // 2. Establecer cabecera
require_once 'config.php';

// 3. Verificación de la conexión a la base de datos
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Error de conexión a la base de datos: ' . $conn->connect_error]);
    exit;
}

$id_negocio_session = $_SESSION['owner_id_negocio'];
$id_cita = isset($_GET['id_cita']) ? (int)$_GET['id_cita'] : 0;

if ($id_cita <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de cita no válido.']);
    exit;
}

$sql = "SELECT 
            c.id_cita, c.fecha_hora_inicio, c.fecha_hora_fin, c.estado_cita, c.descripcion_trabajo, c.tipo_cita, c.in_email, c.in_sms,
            cl.nombre_completo AS nombre_cliente, cl.numero_celular AS telefono_cliente,
            s.nombre_servicio, s.precio
        FROM j108_citas c
        JOIN j106_clientes cl ON c.id_cliente = cl.id_cliente
        LEFT JOIN j104_servicios s ON c.id_servicio = s.id_servicio
        WHERE c.id_cita = ? AND c.id_negocio = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_cita, $id_negocio_session);
$stmt->execute();
$result = $stmt->get_result();
$cita = $result->fetch_assoc();

if (!$cita) {
    http_response_code(404);
    echo json_encode(['error' => 'Cita no encontrada o no pertenece a su negocio.']);
    exit;
}

// Si es una reunión, obtener la lista de invitados
$cita['invitados'] = [];
if ($cita['tipo_cita'] === 'Reunion') {
    $stmt_invitados = $conn->prepare("SELECT * FROM j109_invitados_cita WHERE id_cita = ?");
    $stmt_invitados->bind_param("i", $id_cita);
    $stmt_invitados->execute();
    $result_invitados = $stmt_invitados->get_result();
    while ($row = $result_invitados->fetch_assoc()) {
        $cita['invitados'][] = $row;
    }
    $stmt_invitados->close();
}

echo json_encode($cita);
?>