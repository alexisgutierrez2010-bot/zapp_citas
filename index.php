<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025). Retoque para ajustar la zona horaria del portal público.
date_default_timezone_set('America/Chicago'); // Establecer la zona horaria a US Central Time
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a ZApp Citas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css"/>
    <link rel="stylesheet" href="assets/css/spa_styles.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            color: white;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            display: flex;
            flex-direction: column;
        }
        .hero-section {
            /* Usamos un gradiente CSS que no depende de internet y carga al instante */
            background: #0f2027;  /* fallback for old browsers */
            background: -webkit-linear-gradient(to right, #2c5364, #203a43, #0f2027);  /* Chrome 10-25, Safari 5.1-6 */
            background: linear-gradient(to right, #2c5364, #203a43, #0f2027); /* W3C, IE 10+/ Edge, Firefox 16+, Chrome 26+, Opera 12+, Safari 7+ */
            flex: 1 0 auto; /* Hace que esta sección ocupe el espacio disponible */
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
        }
        .hero-text {
            max-width: 900px;
        }
        .hero-text h1 {
            font-size: clamp(2.5rem, 10vw, 4.5rem); /* CORRECCIÓN: Tamaño de fuente adaptable, mínimo más pequeño */
            font-weight: 700;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.7);
        }
        .hero-text p.lead {
            font-size: clamp(1.1rem, 4vw, 1.5rem);
            font-weight: 300;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.7);
        }
        .action-card {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            transition: transform 0.3s ease, background-color 0.3s ease;
            color: white;
            text-decoration: none;
            display: block;
        }
        .action-card:hover {
            transform: translateY(-10px);
            background-color: rgba(255, 255, 255, 0.25);
            color: white;
        }
        .action-card .card-body {
            padding: 2rem;
        }
        .action-card h5 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        .action-card p {
            font-weight: 300;
            opacity: 0.9;
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js" defer></script>
</head>
<body>

    <div class="hero-section">
        <div class="time-display">
            <div class="date"><?php echo date('l, j F Y'); ?></div>
            <div class="time" id="current-time"><?php echo date('h:i:s A'); ?></div>
        </div>
        <div class="hero-text">
            <h1>ZApp Citas</h1>
            <p class="lead mb-5">La solución integral para la gestión de tus citas y clientes.</p>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4 d-flex">
                    <a href="sesion_iniciar.php" class="action-card w-100">
                        <div class="card-body">
                            <h5>⚙️ ZApp Citas</h5>
                            <p>Administración de la Aplicación</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4 d-flex">
                    <a href="spa_client.php" class="action-card w-100">
                        <div class="card-body">
                            <h5>👤 App Cliente</h5>
                            <p>Gestión de Citas por Cliente</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-12 col-lg-4 d-flex">
                    <a href="spa_owner.php" class="action-card w-100">
                        <div class="card-body">
                            <h5>📅 APP Propietario</h5>
                            <p>Gestión de Citas del Negocio</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php 
    // No incluimos el footer directamente para evitar el margen superior (mt-5) que tiene.
    // En su lugar, lo requerimos y lo mostramos sin ese margen.
    include 'footer.php'; 
    ?>
</body>
</html>