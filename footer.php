<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
?>
<footer class="footer mt-auto py-3 bg-dark text-white-50">
    <div class="container text-center">
        <small>
            ©2025. Software development and Authorized by <a href="http://www.acticven.com" target="_blank" class="text-white">WWW.ACTICVEN.COM</a> All rights reserved. (Version 1.1 Date: Nov-24-2025).
        </small>
    </div>
</footer>

<!-- CORRECCIÓN: Añadir el script de Bootstrap aquí para que esté disponible en todas las páginas que usan el footer -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Script universal para actualizar la hora en tiempo real
    function updateUniversalClock() {
        // Busca el reloj del panel de admin O el reloj de la página de inicio
        const timeElement = document.getElementById('admin-clock') || document.getElementById('current-time');
        if (timeElement) {
            timeElement.textContent = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        }
    }
    // Ejecutar la función cada segundo
    setInterval(updateUniversalClock, 1000);
</script>