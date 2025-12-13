<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Aplicada la internacionalización (i18n).
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

$citas_actualizadas = 0;
$mensaje = "";
$tipo_mensaje = "info";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- PROCESO DE ACTUALIZACIÓN DE ESTADO ---
    // Actualiza las citas pendientes cuya fecha ya pasó para el negocio actual.
    $sql_update_vencidas = "UPDATE j108_citas SET estado_cita = 'Vencida' WHERE fecha_hora_inicio < NOW() AND estado_cita = 'Pendiente' AND id_negocio = ?";
    $stmt_update = $conn->prepare($sql_update_vencidas);
    $stmt_update->bind_param("i", $id_negocio_session);
    
    if ($stmt_update->execute()) {
        $citas_actualizadas = $stmt_update->affected_rows;
        $mensaje = str_replace('{count}', $citas_actualizadas, __('close_appointments_success'));
        $tipo_mensaje = "success";
        
        if ($citas_actualizadas > 0) {
            registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'MANUAL_CLOSE_APPOINTMENTS', "Usuario ejecutó cierre manual, actualizando {$citas_actualizadas} citas.");
        }
    } else {
        $mensaje = "Ocurrió un error durante el proceso: " . $stmt_update->error;
        $tipo_mensaje = "danger";
    }
    $stmt_update->close();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('close_appointments_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: <?php echo $daily_bg_color; ?>;">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card text-center">
                    <div class="card-header">
                        <h3><?php echo __('close_appointments_header'); ?></h3>
                    </div>
                    <div class="card-body">
                        <p class="lead"><?php echo __('close_appointments_desc_1'); ?></p>
                        <p><?php echo __('close_appointments_desc_2'); ?></p>
                        <?php if (!empty($mensaje)): ?>
                            <div class="alert alert-<?php echo $tipo_mensaje; ?>"><?php echo $mensaje; ?></div>
                        <?php endif; ?>
                        <form action="citas_cerrar_vencidas.php" method="POST">
                            <button type="submit" class="btn btn-primary btn-lg mt-3"><?php echo __('close_appointments_button'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>