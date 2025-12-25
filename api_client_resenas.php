<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.

session_start();
require_once 'config.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// --- GET: Listar reseñas (Público o por Cliente) ---
if ($method === 'GET') {
    $id_negocio = isset($_GET['id_negocio']) ? (int)$_GET['id_negocio'] : 0;
    $id_cliente = isset($_GET['id_cliente']) ? (int)$_GET['id_cliente'] : 0;
    
    if ($id_negocio <= 0) {
        echo json_encode([]);
        exit;
    }

    $sql = "SELECT r.id_resena, r.puntuacion, r.comentario, r.fecha_hora, 
                   c.nombre_completo AS nombre_cliente, 
                   s.nombre_servicio
            FROM j112_resenas r
            JOIN j106_clientes c ON r.id_cliente = c.id_cliente
            LEFT JOIN j104_servicios s ON r.id_servicio = s.id_servicio
            WHERE r.id_negocio = ? AND r.activo = 1";
    
    $params = [$id_negocio];
    $types = "i";

    // Si se filtra por cliente (para que vea sus propias reseñas)
    if ($id_cliente > 0) {
        $sql .= " AND r.id_cliente = ?";
        $params[] = $id_cliente;
        $types .= "i";
    }

    $sql .= " ORDER BY r.fecha_hora DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $resenas = [];
    while ($row = $result->fetch_assoc()) {
        $resenas[] = $row;
    }
    echo json_encode($resenas);
    exit;
}

// --- POST: Crear o Editar reseña (Requiere sesión) ---
if ($method === 'POST') {
    // Verificar sesión de cliente (simple check)
    if (!isset($_SESSION['client_loggedin']) || $_SESSION['client_loggedin'] !== true) {
        http_response_code(401);
        echo json_encode(['error' => 'Debes iniciar sesión para comentar.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    $id_negocio = (int)($input['id_negocio'] ?? 0);
    $id_cliente = $_SESSION['client_id']; // Usar ID de la sesión por seguridad
    $id_servicio = !empty($input['id_servicio']) ? (int)$input['id_servicio'] : null;
    $puntuacion = (float)($input['puntuacion'] ?? 5.0);
    $comentario = trim($input['comentario'] ?? '');

    if ($id_negocio <= 0 || $puntuacion < 1 || $puntuacion > 5) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos.']);
        exit;
    }

    // Insertar reseña
    $sql = "INSERT INTO j112_resenas (id_negocio, id_cliente, id_servicio, puntuacion, comentario, fecha_hora, activo) 
            VALUES (?, ?, ?, ?, ?, NOW(), 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiids", $id_negocio, $id_cliente, $id_servicio, $puntuacion, $comentario);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => '¡Gracias por tu reseña!']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar la reseña.']);
    }
}
?>