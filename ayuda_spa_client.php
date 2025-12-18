<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
?><!DOCTYPE html><html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayuda - App Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css' rel='stylesheet'>
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 960px; }
        .card-header { background-color: #0d6efd; color: white; }
        .accordion-button:not(.collapsed) { color: #0c63e4; background-color: #e7f1ff; }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <img src="logo_zapp_citas.png" alt="Logo ZApp Citas" style="height: 2.5em; margin-right: 15px;">
                <h1 class="h2 mb-0">Ayuda de la Aplicación del Cliente</h1>
            </div>
            <a href="javascript:window.close();" class="btn btn-secondary">Cerrar Ventana</a>
        </div>

        <div class="card">
            <div class="card-header"><h4>Guía Rápida de Uso</h4></div>
            <div class="card-body">
                <p class="lead">Bienvenido a la guía de la aplicación para clientes. Aquí encontrarás una explicación de cada sección para que puedas gestionar tus citas de forma fácil y rápida.</p>

                <div class="accordion" id="ayudaAccordion">

                    <!-- Sección 1: Acceso y Navegación -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Acceso y Navegación
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#ayudaAccordion">
                            <div class="accordion-body">
                                <p>El acceso a la aplicación es muy sencillo:</p>
                                <ul>
                                    <li><strong>Inicio de Sesión:</strong> Solo necesitas tu número de teléfono y completar un código de seguridad (CAPTCHA).</li>
                                    <li><strong>Nuevo Usuario:</strong> Si tu número no está registrado, el sistema te guiará para que crees tu cuenta.</li>
                                    <li><strong>Menú Principal:</strong> Una vez dentro, tendrás acceso a las secciones: <strong>Inicio</strong>, <strong>Agendar Cita</strong>, <strong>Mi Historial</strong> y <strong>Mi Perfil</strong>.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 2: Inicio (Dashboard) -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                <i class="bi bi-house-door-fill me-2"></i> Inicio (Dashboard)
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#ayudaAccordion">
                            <div class="accordion-body">
                                <p>Es tu pantalla principal. Aquí verás un resumen de tu actividad:</p>
                                <ul>
                                    <li><strong>Próxima Cita:</strong> Muestra los detalles de tu cita más cercana.</li>
                                    <li><strong>Acciones Rápidas:</strong> Si tu cita está pendiente, podrás <strong>Confirmar</strong> tu asistencia o <strong>Cancelar</strong> la cita directamente desde aquí.</li>
                                    <li><strong>Agendar:</strong> Si no tienes citas, verás un botón para agendar tu primera cita.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 3: Agendar Cita -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                <i class="bi bi-calendar-plus-fill me-2"></i> Agendar Cita
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#ayudaAccordion">
                            <div class="accordion-body">
                                <p>El proceso para agendar una nueva cita es guiado y consta de 3 simples pasos:</p>
                                <ol>
                                    <li><strong>Elige un servicio:</strong> Se te mostrará la lista de servicios disponibles en el negocio.</li>
                                    <li><strong>Elige fecha y hora:</strong> Selecciona un día en el calendario y luego elige uno de los horarios disponibles que se mostrarán.</li>
                                    <li><strong>Confirma los detalles:</strong> Revisa que toda la información sea correcta y confirma para finalizar el agendamiento.</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 4: Mi Historial y Mi Perfil -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour">
                                <i class="bi bi-person-lines-fill me-2"></i> Mi Historial y Mi Perfil
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#ayudaAccordion">
                            <div class="accordion-body">
                                <ul>
                                    <li><strong>Mi Historial:</strong> En esta sección podrás ver una lista completa de todas tus citas, tanto las pasadas como las futuras, con su respectivo estado.</li>
                                    <li><strong>Mi Perfil:</strong> Aquí puedes ver y actualizar tus datos personales, como tu nombre, correo electrónico y dirección.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="card-footer text-center text-muted"><p class="mb-0 small">©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.</p></div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>