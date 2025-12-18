<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Aplicada la internacionalización (i18n).
require_once 'auth_check.php'; // Inicia la sesión, carga el idioma y verifica el login
?><!DOCTYPE html><html lang='es'>
<head>
    <meta charset='utf-8' />
    <title>Calendario de Citas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FullCalendar CSS y JS desde CDN -->
    <!-- SOLUCIÓN: Cargar los locales de FullCalendar para que se traduzca la interfaz del calendario -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/locales-all.global.min.js'></script>
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
        require_once 'config.php';     // Correcto

        // Obtener la configuración del negocio para ajustar el calendario
        $stmt_config = $conn->prepare("SELECT hora_inicio, hora_cierre, dias_trabajo FROM j102_negocios WHERE id_negocio = ?");
        $stmt_config->bind_param("i", $id_negocio_session);
        $stmt_config->execute();
        $config = $stmt_config->get_result()->fetch_assoc();
        $stmt_config->close();

        // Preparar los datos para JavaScript
        $hora_inicio = $config['hora_inicio'] ?? '08:00:00';
        $hora_cierre = $config['hora_cierre'] ?? '18:00:00';
        $dias_trabajo = !empty($config['dias_trabajo']) ? explode(',', $config['dias_trabajo']) : [1, 2, 3, 4, 5]; // Lunes a Viernes por defecto

        include 'navbar.php';         // Muestra el menú de navegación
    ?>

    <div id='calendar'></div>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        const calendar = new FullCalendar.Calendar(calendarEl, {
          themeSystem: 'bootstrap5',
          initialView: 'timeGridWeek', // Vista inicial: semana
          locale: 'es', // Poner el calendario en español
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay' // Botones para cambiar de vista
          },
          // Le decimos al calendario dónde buscar los eventos
          events: 'api_citas.php',

          // --- AJUSTE DE HORAS LABORABLES ---
          // SOLUCIÓN: Se ajusta la configuración para que el calendario se centre en el horario laboral.
          slotMinTime: '<?php echo $hora_inicio; ?>',
          slotMaxTime: '<?php echo $hora_cierre; ?>',
          scrollTime: '<?php echo $hora_inicio; ?>', // Desplaza la vista a la hora de inicio
          height: 'auto',
          businessHours: {
            daysOfWeek: <?php echo json_encode($dias_trabajo); ?>, // Días laborables
            startTime: '<?php echo $hora_inicio; ?>',
            endTime: '<?php echo $hora_cierre; ?>',
          },

          // Hacer que los eventos sean clickables para ir a la página de edición
          eventClick: function(info) {
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