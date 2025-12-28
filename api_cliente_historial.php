<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).

// 1. Incluir el guardián de sesión (maneja sesión y conexión DB)
require_once 'api_cliente_session_check.php';

// 2. Definir cabecera JSON explícitamente
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 3. Validar que tengamos el ID del cliente
$id_cliente = $_SESSION['client_id'] ?? 0;

if ($id_cliente <= 0) {
    // Si no hay ID válido, devolvemos array vacío
    echo json_encode([]);
    exit;
}

// 4. Consulta SQL simplificada
$sql = "SELECT 
            c.id_cita, 
            c.fecha_hora_inicio, 
            c.estado_cita, 
            c.tipo_cita, 
            c.descripcion_trabajo,
            n.nombre_negocio,
            s.nombre_servicio,
            s.precio
        FROM j108_citas c
        LEFT JOIN j102_negocios n ON c.id_negocio = n.id_negocio
        LEFT JOIN j104_servicios s ON c.id_servicio = s.id_servicio
        WHERE c.id_cliente = ?
        ORDER BY c.fecha_hora_inicio DESC";

// 5. Ejecución segura
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $id_cliente);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $citas = [];

        while ($row = $result->fetch_assoc()) {
            // Lógica de presentación en PHP
            $nombre_evento = $row['nombre_servicio'];
            if ($row['tipo_cita'] === 'Reunion') {
                $nombre_evento = $row['descripcion_trabajo'];
            }

            // Determinar si se puede cancelar
            $fecha_inicio = new DateTime($row['fecha_hora_inicio']);
            $ahora = new DateTime();
            $puede_cancelar = ($fecha_inicio > $ahora) && in_array($row['estado_cita'], ['Pendiente', 'Confirmada']);
            
            // Construir el objeto de cita
            $citas[] = [
                'id_cita' => $row['id_cita'],
                // SOLUCIÓN: Formato ISO 8601 para compatibilidad JS
                'fecha_hora_inicio' => str_replace(' ', 'T', $row['fecha_hora_inicio']),
                'estado_cita' => $row['estado_cita'],
                'puede_cancelar' => $puede_cancelar,
                'tipo_cita' => $row['tipo_cita'],
                'nombre_evento' => $nombre_evento ?? 'Evento',
                'nombre_servicio' => $nombre_evento ?? 'Evento', // Para compatibilidad con la columna 'Servicio'
                'nombre_negocio' => $row['nombre_negocio'] ?? 'Negocio',
                'precio' => $row['precio'] ?? 0
            ];
        }
        
        // 6. Salida JSON limpia
        echo json_encode($citas);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al ejecutar consulta: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al preparar consulta: ' . $conn->error]);
}
?>