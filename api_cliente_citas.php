<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025). - RECONSTRUIDO PARA SIMPLICIDAD Y ROBUSTEZ

// 1. Incluir el guardián de sesión (maneja sesión y conexión DB)
require_once 'api_cliente_session_check.php';

// 2. Definir cabecera JSON explícitamente
header('Content-Type: application/json; charset=utf-8');
 
try {
    $id_cliente = $_SESSION['client_id'] ?? 0;
    if ($id_cliente <= 0) {
        throw new Exception("ID de cliente no válido en la sesión.", 401);
    }

    // Consulta SQL directa y optimizada para obtener LA PRÓXIMA cita activa.
    $sql = "SELECT 
                c.id_cita, 
                c.fecha_hora_inicio, 
                c.estado_cita, 
                c.tipo_cita, 
                c.descripcion_trabajo,
                c.id_servicio,
                n.nombre_negocio,
                s.nombre_servicio,
                s.precio
            FROM j108_citas c
            LEFT JOIN j102_negocios n ON c.id_negocio = n.id_negocio
            LEFT JOIN j104_servicios s ON c.id_servicio = s.id_servicio
            WHERE c.id_cliente = ? 
              AND c.estado_cita IN ('Pendiente', 'Confirmada')
              AND c.fecha_hora_inicio >= NOW()
            ORDER BY c.fecha_hora_inicio ASC
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("Error al preparar la consulta: " . $conn->error);

    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $result = $stmt->get_result();
    $cita = $result->fetch_assoc();
    $stmt->close();

    $citas_proximas = [];
    if ($cita) {
        // Procesar la única cita encontrada
        $nombre_evento = $cita['nombre_servicio'];
        if ($cita['tipo_cita'] === 'Reunion') {
            $nombre_evento = $cita['descripcion_trabajo'];
        }
        
        $citas_proximas[] = [
            'id_cita' => $cita['id_cita'],
            'fecha_hora_inicio' => str_replace(' ', 'T', $cita['fecha_hora_inicio']),
            'estado_cita' => $cita['estado_cita'],
            'tipo_cita' => $cita['tipo_cita'],
            'id_servicio' => $cita['id_servicio'],
            'nombre_servicio' => $nombre_evento ?? 'Evento',
            // 'nombre_evento' es usado por dashboard.js, lo mantenemos por compatibilidad
            'nombre_evento' => $nombre_evento ?? 'Evento', 
            'nombre_negocio' => $cita['nombre_negocio'] ?? 'Negocio',
            'precio' => $cita['precio'] ?? 0,
            'descripcion_trabajo' => $cita['descripcion_trabajo'] ?? ''
        ];
    }

    // Devolver en el formato que espera el frontend (dashboard.js)
    echo json_encode(['citas_proximas' => $citas_proximas]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al procesar la solicitud: ' . $e->getMessage()]);
}
// No cerrar $conn aquí, dejar que PHP lo maneje al final del script
?>
