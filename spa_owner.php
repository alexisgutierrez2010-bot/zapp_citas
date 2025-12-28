<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Propietario - Gestión de Citas del Negocio</title>
    <!-- FullCalendar CSS -->
    <!-- SOLUCIÓN: Añadir Chart.js para el nuevo dashboard -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f4f7f6; 
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            /* MEJORA: El fondo se cargará dinámicamente con JS */
            background-color: #f4f7f6;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            transition: background-image 0.5s ease-in-out;
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
            <!-- SOLUCIÓN: Se añade data-view="dashboard" para que el nombre del negocio sea un enlace directo al resumen. -->
            <a class="navbar-brand d-flex align-items-center" href="#" data-view="dashboard" style="padding-top: 0; padding-bottom: 0;">
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
                    <!-- SOLUCIÓN: Contenedor de errores en la barra de navegación -->
                    <li class="nav-item me-3"><div id="global-error-container"></div></li>
                    
                    <!-- SOLUCIÓN DEFINITIVA: El contenedor dinámico DEBE estar dentro de un <li> para que el HTML sea válido. -->
                    <li class="nav-item">
                        <div id="nav-menu-items" class="d-flex flex-column flex-lg-row align-items-center"></div>
                    </li>

                    <!-- SOLUCIÓN: El enlace de ayuda se convierte en un <li> y se coloca fuera del contenedor dinámico para que no sea borrado. -->
                    <li class="nav-item ms-lg-2"><a class="nav-link" id="help-link" href="ayuda_spa_owner.php" target="_blank">❓ Ayuda</a></li>

                    <!-- SOLUCIÓN: Se restaura el contenedor para el reloj, que era requerido por ui.js y causaba el bloqueo. -->
                    <li class="nav-item ms-lg-3"><span id="owner-clock" class="navbar-text"></span></li>
                </ul>
            </div>
        </div>
    </nav>

    <main id="app-container" class="container mt-4">
        <div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div></div>
    </main>

    <!-- CONTENEDOR PARA NOTIFICACIONES (TOASTS) -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <!-- Las notificaciones se insertarán aquí por JavaScript -->
    </div>

    <?php 
        include 'footer.php'; 
    ?>
    <!-- MEJORA: Se usa filemtime para el versionado automático del script, evitando problemas de caché de forma dinámica. -->
    <script type="module" src="app_owner.js?v=<?php echo filemtime('app_owner.js'); ?>"></script>
    <script>
        // Fallback de seguridad: Si la app no carga en 4 segundos, mostrar error.
        setTimeout(function() {
            var spinner = document.querySelector('.spinner-border');
            if (spinner) {
                var container = document.getElementById('app-container');
                container.innerHTML = '<div class="alert alert-danger"><h4>Error de Arranque</h4><p>La aplicación tardó demasiado en iniciar.</p><hr><p><strong>Posibles causas:</strong><br>1. Faltan archivos en la carpeta <code>/js/owner_modules/</code>.<br>2. Error de sintaxis en JavaScript (Presiona F12 y mira la Consola).<br>3. La API devolvió datos inválidos.</p></div>';
            }
        }, 4000);
    </script>
</body>
</html>