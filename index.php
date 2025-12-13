<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Retoque para añadir soporte multi-idioma (ES/EN).
date_default_timezone_set('America/Chicago'); // Establecer la zona horaria a US Central Time

// --- LÓGICA MULTI-IDIOMA (CORREGIDA) ---
// Iniciar sesión para que el idioma se pueda guardar entre páginas
session_start();
// Cargar el sistema de internacionalización completo
require_once __DIR__ . '/languages.php';

// --- LÓGICA PARA IMÁGENES DE FONDO DINÁMICAS ---
$hero_images_dir = 'assets/images/hero/';
$hero_images = [];
if (is_dir($hero_images_dir)) {
    $files = scandir($hero_images_dir);
    foreach ($files as $file) {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
            $hero_images[] = $hero_images_dir . $file;
        }
    }
}
// --- FIN LÓGICA ---
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('welcome'); ?> a <?php echo __('zapp_citas'); ?></title>
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
            /* SOLUCIÓN: Usar 'contain' para que la imagen completa sea visible, y 'cover' como fallback */
            background-size: contain, cover;
            background-position: center;
            background-repeat: no-repeat;
            /* SOLUCIÓN: Añadir un gradiente radial (viñeta) para centrar la atención y mejorar la legibilidad */
            background-image: radial-gradient(ellipse at center, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.8) 100%), linear-gradient(to right, #2c5364, #203a43, #0f2027);
            
            /* Transición suave para el cambio de imagen de fondo */
            transition: background-image 1.5s ease-in-out;
            flex: 1 0 auto; /* Hace que esta sección ocupe el espacio disponible */
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
        }
        .hero-text {
            max-width: 95%; /* Usar porcentaje para que siempre haya un pequeño margen */
            width: 900px; /* Mantener el ancho máximo para pantallas grandes */
        }
        .hero-text h1 {
            font-size: clamp(2.2rem, 8vw, 4.5rem); /* AJUSTE: Reducimos el tamaño mínimo y el preferido para pantallas pequeñas */
            font-weight: 700;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.7);
        }
        .hero-text p.lead {
            font-size: clamp(1rem, 4vw, 1.4rem); /* AJUSTE: Reducimos ligeramente el tamaño para mejor proporción */
            font-weight: 300;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.7);
        }
        .hero-logo {
            width: clamp(100px, 20vw, 180px); /* Tamaño adaptable: mínimo 100px, preferido 20% del ancho de la pantalla, máximo 180px */
            height: auto;
            filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.6));
            margin-bottom: 1rem; /* Espacio entre el logo y el título */
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
        .help-fab {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #0d6efd;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            transition: transform 0.2s;
        }
        .help-fab:hover {
            transform: scale(1.1);
            color: white;
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
            <img src="logo_zapp_citas.png" alt="ZApp Citas Logo" class="hero-logo">
            <h1><?php echo __('zapp_citas'); ?></h1>
            <p class="lead mb-5"><?php echo __('index_subtitle'); ?></p>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4 d-flex">
                    <a href="sesion_iniciar.php?lang=<?php echo $lang; ?>" class="action-card w-100">
                        <div class="card-body">
                            <h5><i class="bi bi-gear-wide-connected"></i> <?php echo __('zapp_citas'); ?></h5>
                            <p><?php echo __('index_card_admin_desc'); ?></p>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4 d-flex">
                    <a href="spa_client.php?lang=<?php echo $lang; ?>" class="action-card w-100">
                        <div class="card-body">
                            <h5><i class="bi bi-person-circle"></i> <?php echo __('index_card_client_title'); ?></h5>
                            <p><?php echo __('index_card_client_desc'); ?></p>
                        </div>
                    </a>
                </div>
                <div class="col-md-12 col-lg-4 d-flex">
                    <a href="spa_owner.php?lang=<?php echo $lang; ?>" class="action-card w-100">
                        <div class="card-body">
                            <div class="lh-1">
                                <h5 class="card-title mb-1">App Propietario</h5>
                                <p class="card-text text-white-50 small mb-0">Gestión de Citas del Negocio</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Botón Flotante de Ayuda -->
    <a href="ayuda_index.php?lang=<?php echo $lang; ?>" target="_blank" class="help-fab" title="Ayuda">
        <i class="bi bi-question-lg" style="font-size: 1.8rem;"></i>
    </a>

    <?php 
    // No incluimos el footer directamente para evitar el margen superior (mt-5) que tiene.
    // En su lugar, lo requerimos y lo mostramos sin ese margen.
    include 'footer.php'; 
    ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Actualizar el reloj cada segundo
        const timeElement = document.getElementById('current-time');
        if (timeElement) {
            setInterval(() => {
                timeElement.textContent = new Date().toLocaleTimeString('<?php echo $lang === 'es' ? 'es-ES' : 'en-US'; ?>', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
            }, 1000);
        }

        // Carrusel de imágenes de fondo
        const heroSection = document.querySelector('.hero-section');
        const images = <?php echo json_encode($hero_images); ?>;
        let currentImageIndex = -1; // Empezar en -1 para que la primera llamada sea el índice 0

        if (images.length > 0) {
            const changeBackgroundImage = () => {
                currentImageIndex = (currentImageIndex + 1) % images.length;
                // Combinamos la viñeta con la nueva imagen
                heroSection.style.backgroundImage = `
                    radial-gradient(ellipse at center, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.8) 100%), 
                    url('${images[currentImageIndex]}')
                `;
            };

            // SOLUCIÓN: Cambiar la primera imagen después de 1 segundo, luego cada 7 segundos.
            setTimeout(() => {
                changeBackgroundImage(); // Primera llamada
                setInterval(changeBackgroundImage, 7000); // Llamadas subsecuentes
            }, 1000); // Espera de 1 segundo para la primera imagen
        }
    });
    </script>
</body>
</html>