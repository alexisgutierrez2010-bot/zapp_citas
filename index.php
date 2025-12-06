<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Retoque para añadir soporte multi-idioma (ES/EN).
date_default_timezone_set('America/Chicago'); // Establecer la zona horaria a US Central Time

// --- LÓGICA MULTI-IDIOMA ---
// 1. Detectar el idioma solicitado, por defecto 'es' (español)
$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'es']) ? $_GET['lang'] : 'es';

// 2. Array con todas las traducciones
$translations = [
    'es' => [
        'title' => 'Bienvenido a ZApp Citas',
        'main_heading' => 'ZApp Citas',
        'tagline' => 'La solución integral para la gestión de tus citas y clientes.',
        'admin_card_title' => '⚙️ ZApp Citas',
        'admin_card_text' => 'Administración de la Aplicación',
        'client_card_title' => '👤 App Cliente',
        'client_card_text' => 'Gestión de Citas por Cliente',
        'owner_card_title' => '📅 APP Propietario',
        'owner_card_text' => 'Gestión de Citas del Negocio',
    ],
    'en' => [
        'title' => 'Welcome to ZApp Citas',
        'main_heading' => 'ZApp Citas',
        'tagline' => 'The comprehensive solution for managing your appointments and clients.',
        'admin_card_title' => '⚙️ ZApp Citas',
        'admin_card_text' => 'Application Administration',
        'client_card_title' => '👤 Client App',
        'client_card_text' => 'Client Appointment Management',
        'owner_card_title' => '📅 Owner APP',
        'owner_card_text' => 'Business Appointment Management',
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $translations[$lang]['title']; ?></title>
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
        .language-switcher {
            position: absolute;
            top: 20px;
            left: 20px;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.7);
        }
        .language-switcher a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            padding: 5px;
        }
        .language-switcher a.active {
            text-decoration: underline;
            color: #0d6efd;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js" defer></script>
</head>
<body>

    <div class="hero-section">
        <div class="time-display">
            <div class="date"><?php echo date($lang === 'es' ? 'l, j \d\e F \d\e Y' : 'l, F j, Y'); ?></div>
            <div class="time" id="current-time"><?php echo date('h:i:s A'); ?></div>
        </div>
        <div class="language-switcher">
            <a href="?lang=es" class="<?php echo $lang === 'es' ? 'active' : ''; ?>">ES</a> | 
            <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">EN</a>
        </div>
        <div class="hero-text">
            <h1><?php echo $translations[$lang]['main_heading']; ?></h1>
            <p class="lead mb-5"><?php echo $translations[$lang]['tagline']; ?></p>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4 d-flex">
                    <a href="sesion_iniciar.php" class="action-card w-100">
                        <div class="card-body">
                            <h5><?php echo $translations[$lang]['admin_card_title']; ?></h5>
                            <p><?php echo $translations[$lang]['admin_card_text']; ?></p>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4 d-flex">
                    <a href="spa_client.php" class="action-card w-100">
                        <div class="card-body">
                            <h5><?php echo $translations[$lang]['client_card_title']; ?></h5>
                            <p><?php echo $translations[$lang]['client_card_text']; ?></p>
                        </div>
                    </a>
                </div>
                <div class="col-md-12 col-lg-4 d-flex">
                    <a href="spa_owner.php" class="action-card w-100">
                        <div class="card-body">
                            <h5><?php echo $translations[$lang]['owner_card_title']; ?></h5>
                            <p><?php echo $translations[$lang]['owner_card_text']; ?></p>
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