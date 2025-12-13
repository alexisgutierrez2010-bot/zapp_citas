<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Propietario - Gestión de Citas del Negocio</title>
    <!-- FullCalendar CSS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f4f7f6; 
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        /* Estilos para el reloj responsivo en la barra de navegación */
        #owner-clock {
            font-size: clamp(0.8rem, 3vw, 1rem);
            white-space: nowrap;
            color: rgba(255, 255, 255, 0.75);
            align-self: center; /* Centrar verticalmente en el toggler */
        }
        #app-container {
            flex: 1;
        }
        /* Estilos para que los eventos del calendario se vean bien con Bootstrap */
        .fc-event {
            color: #fff !important; /* Forzar texto blanco en eventos */
            padding: 2px 4px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#" style="padding-top: 0; padding-bottom: 0;">
                <img src="logo_zapp_citas.png" alt="Logo ZApp Citas" style="height: 1.5em; margin-right: 10px; filter: drop-shadow(1px 1px 2px rgba(0,0,0,0.5));">
                <span id="navbar-brand-title" style="text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">
                    <!-- El título y subtítulo se cargarán aquí por JavaScript -->
                </span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center" id="nav-menu">
                    <!-- El menú dinámico se insertará aquí por JS -->
                    <div id="nav-menu-items" class="d-flex flex-column flex-lg-row"></div>
                    <!-- Enlace de ayuda estático -->
                    <li class="nav-item ms-lg-2"><a class="nav-link" id="help-link" href="ayuda_spa_owner.php?lang=es" target="_blank">❓ Ayuda</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main id="app-container" class="container mt-4">
        <div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div></div>
    </main>

    <?php 
        $lang_param = isset($_GET['lang']) ? $_GET['lang'] : 'es';
        include 'spa_owner_footer.php'; 
    ?>
    <script type="module" src="app_owner.js?v=<?php echo filemtime('app_owner.js'); ?>"></script>
</body>
</html>