<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-20-2025).
?><!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='utf-8' />
    <title>Calendario de Citas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FullCalendar CSS y JS desde CDN -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js'></script>
    <style>
        /* Pequeños ajustes para que el calendario se vea bien */
        body {
            background-color: #f8f9fa;
        }
        #calendar {
            max-width: 1100px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

    <?php
        require_once 'auth_check.php'; // Inicia la sesión y verifica el login
        require_once 'config.php';     // Correcto
        include 'navbar.php';         // Muestra el menú de navegación
    ?>

    <div id='calendar'></div>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: 'dayGridMonth', // Vista inicial: mes
          locale: 'es', // Poner el calendario en español
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay' // Botones para cambiar de vista
          },
          // Aquí está la magia: le decimos al calendario dónde buscar los eventos
          events: 'api_citas.php',

          // (Opcional) Hacer que los eventos sean clickables para ir a la página de edición
          eventClick: function(info) {
            // Redirigir a la página de edición de la cita
            window.location.href = 'citas_editar.php?id=' + info.event.id;
          }
        });
        calendar.render();
      });
    </script>

    <!-- No incluye footer.php, así que añadimos el script de Bootstrap aquí -->
    <?php include 'footer.php'; ?>
</body>
</html>