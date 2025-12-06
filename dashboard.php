<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
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
    <style>
        body, html {
            height: 100%;
            margin: 0;
            color: white;
        }
        .hero-section {
            background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('get_image.php');
            height: 100%;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .hero-text {
            max-width: 800px;
        }
        .hero-text h1 {
            /* Usamos clamp para un tamaño de fuente fluido */
            font-size: clamp(2.2rem, 10vw, 4rem); /* CORRECCIÓN: Tamaño de fuente adaptable */
            font-weight: bold;
        }
        .hero-text p {
            font-size: clamp(1rem, 4vw, 1.5rem); /* CORRECCIÓN: Tamaño de fuente adaptable */
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
        .action-card a {
            text-decoration: none;
            color: white;
        }
        .action-card .card-body {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .time-display {
            position: absolute;
            top: 20px;
            right: 20px;
            text-align: right;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.7);
        }
        .time-display .date {
            font-size: clamp(0.8rem, 2.5vw, 1.1rem); /* CORRECCIÓN: Tamaño de fuente adaptable */
        }
        .time-display .time {
            font-size: clamp(1.5rem, 5vw, 2rem); /* CORRECCIÓN: Tamaño de fuente adaptable */
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="hero-section">
        <div class="time-display">
            <div class="date"><?php echo date('l, j F Y'); ?></div>
            <div class="time" id="dashboard-time"><?php echo date('h:i:s A'); ?></div>
        </div>
        <div class="hero-text">
            <h1>Bienvenido a <?php echo htmlspecialchars($nombre_negocio); ?></h1>
            <?php if (!empty($telefono_negocio) || !empty($email_negocio)): ?>
                <p class="lead mt-3">
                    <?php if (!empty($telefono_negocio)): ?>
                        <span class="me-3">📞 <?php echo htmlspecialchars($telefono_negocio); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($email_negocio)): ?>
                        <span>📧 <?php echo htmlspecialchars($email_negocio); ?></span>
                    <?php endif; ?>
                </p>
            <?php endif; ?>

            <p>Su asistente personal para la gestión de citas.</p>

            <div class="row mt-5 g-4">
                <div class="col-md-4">
                    <div class="card action-card">
                        <a href="citas_lista.php" class="card-body">Agendar Cita</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card action-card">
                        <a href="calendario_ver.php" class="card-body">Ver Calendario</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card action-card">
                        <a href="clientes_lista.php" class="card-body">Gestionar Clientes</a>
                    </div>
                </div>
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
</body>
</html>
