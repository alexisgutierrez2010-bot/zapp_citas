<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-20-2025).
require_once 'auth_check.php';
require_once 'config.php';

// 1. Validar el ID de la cita que viene por la URL
$id_cita = isset($_GET['id_cita']) ? (int)$_GET['id_cita'] : 0;
$accion = isset($_GET['accion']) ? $_GET['accion'] : 'NUEVA'; // Por defecto es 'NUEVA'

if ($id_cita <= 0) {
    header("Location: citas_lista.php?status=error&message=" . urlencode("ID de cita no válido."));
    exit();
}

// 2. Obtener todos los datos necesarios de la base de datos para mostrar un resumen
$sql_cita = "SELECT 
                c.fecha_hora_inicio, 
                c.tipo_cita,
                cl.nombre_completo, 
                cl.correo_electronico, 
                s.nombre_servicio 
             FROM j108_citas c
             JOIN j106_clientes cl ON c.id_cliente = cl.id_cliente
             LEFT JOIN j104_servicios s ON c.id_servicio = s.id_servicio
             WHERE c.id_cita = ? AND c.id_negocio = ?";
$stmt_cita = $conn->prepare($sql_cita);
$stmt_cita->bind_param("ii", $id_cita, $id_negocio_session);
$stmt_cita->execute();
$cita_details = $stmt_cita->get_result()->fetch_assoc();
$stmt_cita->close();

if (!$cita_details) {
    header("Location: citas_lista.php?status=error&message=" . urlencode("Cita no encontrada."));
    exit();
}

// Si es una reunión, obtener la lista de invitados
$invitados = [];
if ($cita_details['tipo_cita'] === 'Reunion') {
    $stmt_invitados = $conn->prepare("SELECT nombre_invitado, correo_electronico_invitado FROM j109_invitados_cita WHERE id_cita = ?");
    $stmt_invitados->bind_param("i", $id_cita);
    $stmt_invitados->execute();
    $result_invitados = $stmt_invitados->get_result();
    while ($row = $result_invitados->fetch_assoc()) {
        $invitados[] = $row;
    }
    $stmt_invitados->close();
}

$fecha_hora_inicio = new DateTime($cita_details['fecha_hora_inicio']);

// Personalizar mensajes según la acción
$titulo_pagina = "Confirmar Notificación";
$mensaje_principal = "¿Deseas notificar al cliente por correo electrónico?";

switch ($accion) {
    case 'NUEVA':
        $titulo_pagina = "Confirmar Notificación de Nueva Cita";
        $mensaje_principal = "La cita ha sido creada con éxito. ¿Deseas notificar al cliente?";
        break;
    case 'MODIFICADA':
        $titulo_pagina = "Confirmar Notificación de Modificación";
        $mensaje_principal = "La cita ha sido actualizada. ¿Deseas notificar al cliente sobre los cambios?";
        break;
    case 'CANCELADA':
    case 'COMPLETADA':
        $titulo_pagina = "Confirmar Notificación de Estado";
        $mensaje_principal = "El estado de la cita ha sido actualizado. ¿Deseas notificar al cliente?";
        break;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Envío de Notificación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3><?php echo $titulo_pagina; ?></h3>
                    </div>
                    <div class="card-body">
                        <p class="lead"><?php echo $mensaje_principal; ?></p>
                        
                        <div class="alert alert-light border">
                            <h5 class="alert-heading">Detalles de la Cita:</h5>
                            <hr>
                            <p><strong>Cliente:</strong> <?php echo htmlspecialchars($cita_details['nombre_completo']); ?></p>
                            <p><strong>Correo:</strong> <?php echo htmlspecialchars($cita_details['correo_electronico']); ?></p>
                            <?php if ($cita_details['tipo_cita'] === 'Servicio'): ?>
                                <p><strong>Servicio:</strong> <?php echo htmlspecialchars($cita_details['nombre_servicio']); ?></p>
                            <?php endif; ?>
                            <p><strong>Fecha:</strong> <?php echo $fecha_hora_inicio->format('d/m/Y'); ?></p>
                            <p><strong>Hora:</strong> <?php echo $fecha_hora_inicio->format('h:i A'); ?></p>

                            <?php if (!empty($invitados)): ?>
                                <hr>
                                <p><strong>Invitados (recibirán copia):</strong></p>
                                <ul>
                                    <?php foreach ($invitados as $invitado): ?>
                                        <li><?php echo htmlspecialchars($invitado['nombre_invitado']); ?> (<?php echo htmlspecialchars($invitado['correo_electronico_invitado']); ?>)</li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <p class="mt-4">Al confirmar, se enviará un correo al cliente principal y a todos los invitados (si aplica) con los detalles y un archivo de calendario (RSVP).</p>

                        <div class="d-flex justify-content-between mt-4">
                            <!-- Formulario para enviar el correo -->
                            <form action="enviar_email.php" method="POST">
                                <input type="hidden" name="id_cita" value="<?php echo $id_cita; ?>">
                                <input type="hidden" name="accion" value="<?php echo htmlspecialchars($accion); ?>">
                                <button type="submit" class="btn btn-success btn-lg">
                                    ✅ Sí, Enviar Notificación
                                </button>
                            </form>

                            <!-- Botón para omitir el envío y volver a la lista -->
                            <a href="citas_lista.php?status=success_nosend" class="btn btn-secondary btn-lg">
                                ❌ No, Volver a la Lista
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
