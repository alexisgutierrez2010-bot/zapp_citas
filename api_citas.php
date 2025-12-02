<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-27-2025).
// 1. Incluir configuración y establecer la cabecera para que la respuesta sea JSON
require_once 'auth_check.php';
require_once 'config.php';
header('Content-Type: application/json');

// 2. Preparar la consulta para obtener las citas
// Unimos Citas con Clientes para obtener el nombre del cliente
// Filtramos por el id_negocio de la sesión para un entorno multi-negocio
// Opcional: Excluimos las citas canceladas para no mostrarlas en el calendario
$sql = "SELECT 
            j108_citas.id_cita,
            j108_citas.fecha_hora_inicio,
            j108_citas.fecha_hora_fin,
            j106_clientes.nombre_completo,
            j108_citas.estado_cita
        FROM j108_citas
        JOIN j106_clientes ON j108_citas.id_cliente = j106_clientes.id_cliente
        WHERE j108_citas.estado_cita != 'Cancelada' AND j108_citas.id_negocio = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_negocio_session);
$stmt->execute();
$result = $stmt->get_result();

// 3. Construir el array de eventos en el formato que FullCalendar necesita
$eventos = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $eventos[] = [
            'id'    => $row['id_cita'],
            'title' => $row['nombre_completo'], // El texto que se mostrará en el evento
            'start' => $row['fecha_hora_inicio'], // Fecha y hora de inicio
            'end'   => $row['fecha_hora_fin'],   // Fecha y hora de fin
            // Podríamos añadir colores según el estado, pero lo mantenemos simple por ahora
        ];
    }
}

// 4. Cerrar la conexión y devolver los eventos en formato JSON
echo json_encode($eventos);
exit();
?>