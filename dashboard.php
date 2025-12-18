<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Aplicada la internacionalización (i18n).
require_once 'auth_check.php'; // PRIMERO: Inicia la sesión y define los roles.
require_once 'config.php';     // SEGUNDO: Establece la conexión a la BD.

// Obtener la configuración del negocio para el fondo y el título
$stmt = $conn->prepare("SELECT nombre_negocio, telefono, email FROM j102_negocios WHERE id_negocio = ?");
$stmt->bind_param("i", $id_negocio_session);
$stmt->execute();
$config_result = $stmt->get_result();
$config = $config_result->fetch_assoc();

$nombre_negocio = $config['nombre_negocio'] ?? 'ZApp Citas';
$telefono_negocio = $config['telefono'] ?? '';
$email_negocio = $config['email'] ?? '';

// La imagen de fondo ahora se carga a través de un script dedicado
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a <?php echo htmlspecialchars($nombre_negocio); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
            margin: 0;
        }
        body {
            background-image: linear-gradient(to right, rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.4)), url('get_image.php?id=<?php echo $id_negocio_session; ?>');
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            color: white;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex: 1;
        }
        .action-card {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease, background-color 0.3s ease;
        }
        .action-card:hover {
            transform: translateY(-10px);
            background-color: rgba(255, 255, 255, 0.2);
        }
        .time-display {
            text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container main-content d-flex flex-column justify-content-center">
        <div class="text-center">
            <div class="time-display mb-4">
                <div class="date fs-4"><?php echo date('l, j F Y'); ?></div>
                <div class="time display-4" id="dashboard-time"><?php echo date('h:i:s A'); ?></div>
            </div>

            <h1 class="display-3 fw-bold">Bienvenido a <?php echo htmlspecialchars($nombre_negocio); ?></h1>
            <p class="lead">Su asistente personal para la gestión de citas.</p>
        </div>

        <div class="row mt-5 g-4 justify-content-center">
            <div class="col-md-4">
                <a href="citas_lista.php" class="text-decoration-none">
                    <div class="card action-card text-center text-white h-100"><div class="card-body"><i class="bi bi-calendar-plus fs-1"></i><h4 class="card-title mt-2">Agendar Cita</h4></div></div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="calendario_ver.php" class="text-decoration-none">
                    <div class="card action-card text-center text-white h-100"><div class="card-body"><i class="bi bi-calendar-week fs-1"></i><h4 class="card-title mt-2">Ver Calendario</h4></div></div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="clientes_lista.php" class="text-decoration-none">
                    <div class="card action-card text-center text-white h-100"><div class="card-body"><i class="bi bi-people-fill fs-1"></i><h4 class="card-title mt-2">Gestionar Clientes</h4></div></div>
                </a>
            </div>
        </div>
    </div>
    <script>
        // Script para actualizar la hora en tiempo real en el dashboard
        function updateDashboardTime() {
            const timeElement = document.getElementById('dashboard-time');
            if (timeElement) {
                timeElement.textContent = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
            }
        }
        setInterval(updateDashboardTime, 1000);
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>
