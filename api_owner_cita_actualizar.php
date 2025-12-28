<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).

require_once 'config.php';
require_once 'audit_log.php';

session_start();
if (!isset($_SESSION['owner_loggedin']) || $_SESSION['owner_loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$id_cita = (int)($input['id_cita'] ?? 0);
$id_negocio = $_SESSION['owner_id_negocio'];
$id_usuario = $_SESSION['owner_id_usuario'];

if ($id_cita <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de cita no válido.']);
    exit;
}

$conn->begin_transaction();

try {
    // --- ESCENARIO 1: Solo se actualiza el estado de la cita ---
    if (isset($input['estado_cita']) && count($input) <= 2) {
        $nuevo_estado = trim($input['estado_cita']);
        $estados_validos = ['Pendiente', 'Confirmada', 'Completada', 'Cancelada', 'No Asistió', 'Pospuesta'];
        if (!in_array($nuevo_estado, $estados_validos)) {
            throw new Exception('Estado no permitido.', 400);
        }

        $stmt = $conn->prepare("UPDATE j108_citas SET estado_cita = ? WHERE id_cita = ? AND id_negocio = ?");
        $stmt->bind_param("sii", $nuevo_estado, $id_cita, $id_negocio);
        if (!$stmt->execute()) throw new Exception("Error al actualizar la cita: " . $stmt->error);
        $stmt->close();

        // Obtener datos para la notificación (WhatsApp/SMS)
        $sql_info = "SELECT c.nombre_completo AS nombre_cliente, c.numero_celular AS telefono_cliente, s.nombre_servicio, ci.descripcion_trabajo, ci.tipo_cita, ci.fecha_hora_inicio, n.nombre_negocio FROM j108_citas ci JOIN j106_clientes c ON ci.id_cliente = c.id_cliente JOIN j102_negocios n ON ci.id_negocio = n.id_negocio LEFT JOIN j104_servicios s ON ci.id_servicio = s.id_servicio WHERE ci.id_cita = ? AND ci.id_negocio = ?";
        $stmt_info = $conn->prepare($sql_info);
        $stmt_info->bind_param("ii", $id_cita, $id_negocio);
        $stmt_info->execute();
        $info = $stmt_info->get_result()->fetch_assoc();
        $stmt_info->close();

        registrar_auditoria($conn, $id_usuario, $id_negocio, 'CITA_UPDATE_STATUS', "Cita ID $id_cita cambiada a estado: $nuevo_estado");
        $conn->commit();

        echo json_encode([
            'success' => true, 
            'message' => "Estado actualizado a '{$nuevo_estado}'.",
            'notification_payload' => $info ?: null,
            'nuevo_estado' => $nuevo_estado
        ]);

    // --- ESCENARIO 2: Se edita la cita completa ---
    } elseif (isset($input['fecha_hora_inicio'])) {
        $tipo_cita = $input['tipo_cita'] ?? 'Servicio';
        $id_servicio = ($tipo_cita === 'Servicio') ? (int)($input['id_servicio'] ?? 0) : null;
        $descripcion_trabajo = trim($input['descripcion_trabajo'] ?? '');
        $fecha_hora_inicio_str = trim($input['fecha_hora_inicio'] ?? '');
        $invitados = ($tipo_cita === 'Reunion') ? ($input['invitados'] ?? []) : [];

        if (empty($fecha_hora_inicio_str) || ($tipo_cita === 'Servicio' && $id_servicio <= 0)) {
            throw new Exception('Datos incompletos para editar la cita.', 400);
        }

        // Calcular fecha de fin
        $duracion_valor = 60; // Default para reuniones
        $duracion_unidad = 'Minutos';
        if ($tipo_cita === 'Servicio') {
            $stmt_duracion = $conn->prepare("SELECT duracion_valor, duracion_unidad FROM j104_servicios WHERE id_servicio = ? AND id_negocio = ?");
            $stmt_duracion->bind_param("ii", $id_servicio, $id_negocio);
            $stmt_duracion->execute();
            $servicio_dur = $stmt_duracion->get_result()->fetch_assoc();
            if (!$servicio_dur) throw new Exception("Servicio no válido.", 404);
            $duracion_valor = (int)$servicio_dur['duracion_valor'];
            $duracion_unidad = $servicio_dur['duracion_unidad'];
            $stmt_duracion->close();
        }
        $fecha_hora_inicio = new DateTime($fecha_hora_inicio_str);
        $fecha_hora_fin = clone $fecha_hora_inicio;

        // MEJORA: Lógica de intervalo robusta para manejar Minutos, Horas y Días.
        $interval_unit = 'M'; // Default a Minutos
        if ($duracion_unidad === 'Horas') $interval_unit = 'H';
        else if ($duracion_unidad === 'Dias') $interval_unit = 'D';
        $interval_spec = 'PT' . $duracion_valor . $interval_unit;

        $fecha_hora_fin->add(new DateInterval($interval_spec));

        // Actualizar cita principal
        $sql_update = "UPDATE j108_citas SET id_servicio = ?, fecha_hora_inicio = ?, fecha_hora_fin = ?, descripcion_trabajo = ? WHERE id_cita = ? AND id_negocio = ?";
        $stmt_update = $conn->prepare($sql_update);
        // SOLUCIÓN: Asignar los resultados de las funciones a variables antes de pasarlas a bind_param.
        $fecha_inicio_sql = $fecha_hora_inicio->format('Y-m-d H:i:s');
        $fecha_fin_sql = $fecha_hora_fin->format('Y-m-d H:i:s');
        $stmt_update->bind_param("isssii", $id_servicio, $fecha_inicio_sql, $fecha_fin_sql, $descripcion_trabajo, $id_cita, $id_negocio);
        if (!$stmt_update->execute()) throw new Exception("Error al actualizar la cita: " . $stmt_update->error);
        $stmt_update->close();

        // Actualizar invitados (si es reunión)
        if ($tipo_cita === 'Reunion') {
            // Borrar invitados antiguos
            $stmt_delete = $conn->prepare("DELETE FROM j109_invitados_cita WHERE id_cita = ?");
            $stmt_delete->bind_param("i", $id_cita);
            $stmt_delete->execute();
            $stmt_delete->close();
            // Insertar nuevos invitados
            if (!empty($invitados)) {
                $sql_invitado = "INSERT INTO j109_invitados_cita (id_cita, nombre_invitado, correo_electronico_invitado, numero_celular_invitado) VALUES (?, ?, ?, ?)";
                $stmt_invitado = $conn->prepare($sql_invitado);
                foreach ($invitados as $invitado) {
                    if (!empty($invitado['nombre']) && !empty($invitado['email'])) {
                        $stmt_invitado->bind_param("isss", $id_cita, $invitado['nombre'], $invitado['email'], $invitado['telefono']);
                        $stmt_invitado->execute();
                    }
                }
                $stmt_invitado->close();
            }
        }

        registrar_auditoria($conn, $id_usuario, $id_negocio, 'CITA_UPDATE_FULL', "Cita ID $id_cita fue editada.");
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Cita actualizada con éxito.']);

    } else {
        // --- ESCENARIO 3: Formato de solicitud no reconocido ---
        throw new Exception('Datos inválidos o formato de solicitud no reconocido.', 400);
    }
} catch (Exception $e) {
    $conn->rollback();
    $http_code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
    http_response_code($http_code);
    echo json_encode(['error' => $e->getMessage()]);
}
$conn->close();
?>
