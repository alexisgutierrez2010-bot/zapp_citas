<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).

header('Content-Type: application/json');

// Manejo de solicitudes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- CREAR RESEÑA (Requiere sesión) ---
    require_once 'api_cliente_session_check.php';
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id_negocio = (int)($input['id_negocio'] ?? 0);
    $puntuacion = (int)($input['puntuacion'] ?? 0);
    $comentario = trim($input['comentario'] ?? '');
    $id_cliente = $_SESSION['client_id'];
    
    if ($id_negocio <= 0 || $puntuacion < 1 || $puntuacion > 5 || empty($comentario)) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos. Asegúrate de seleccionar una puntuación y escribir un comentario.']);
        exit;
    }
    
    // SOLUCIÓN: Usar el nombre de tabla correcto (j112_resenas) y la columna 'activo'.
    // El campo id_servicio se deja como NULL ya que el formulario actual no lo provee.
    $sql = "INSERT INTO j112_resenas (id_negocio, id_cliente, id_servicio, puntuacion, comentario, fecha_hora, activo) VALUES (?, ?, NULL, ?, ?, NOW(), 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $id_negocio, $id_cliente, $puntuacion, $comentario);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => '¡Gracias! Tu reseña ha sido publicada.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar la reseña.']);
    }
    $stmt->close();

} else {
    // --- LISTAR RESEÑAS (Público o Privado) ---
    // Usamos conexión manual para no forzar el chequeo de sesión si solo se quiere leer
    require_once 'config.php';
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de conexión']);
        exit;
    }
    $conn->set_charset("utf8mb4");

    $id_negocio = isset($_GET['id_negocio']) ? (int)$_GET['id_negocio'] : 0;
    
    if ($id_negocio > 0) {
        // SOLUCIÓN: Usar el nombre de tabla correcto (j112_resenas) y la columna 'activo'.
        $sql = "SELECT r.puntuacion, r.comentario, r.fecha_hora, c.nombre_completo as nombre_cliente, s.nombre_servicio
                FROM j112_resenas r
                JOIN j106_clientes c ON r.id_cliente = c.id_cliente
                LEFT JOIN j104_servicios s ON r.id_servicio = s.id_servicio
                WHERE r.id_negocio = ? AND r.activo = 1
                ORDER BY r.fecha_hora DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_negocio);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    } else {
        echo json_encode([]);
    }
}
?>