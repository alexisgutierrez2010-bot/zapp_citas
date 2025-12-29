<?php
// Elaborado por GEMINI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-27-2025).
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayuda - Aplicación del Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card-header { background-color: #0d6efd; color: white; }
        .accordion-button { font-weight: 500; }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1><i class="bi bi-question-circle-fill text-primary"></i> Guía de la Aplicación del Cliente</h1>
            <a href="javascript:window.close();" class="btn btn-secondary">Cerrar Ventana</a>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h4>Bienvenido al Portal de Clientes</h4>
            </div>
            <div class="card-body">
                <p>Esta aplicación está diseñada para que puedas gestionar tus citas de forma rápida y sencilla. Aquí puedes agendar nuevos servicios, ver tu historial y mantener tus datos actualizados.</p>
            </div>
        </div>

        <div class="accordion" id="ayudaClienteAccordion">

            <!-- Inicio de Sesión y Registro -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLogin" aria-expanded="true">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Inicio de Sesión y Registro
                    </button>
                </h2>
                <div id="collapseLogin" class="accordion-collapse collapse show" data-bs-parent="#ayudaClienteAccordion">
                    <div class="accordion-body">
                        <p>Para acceder, simplemente ingresa tu <strong>número de teléfono celular</strong> con el que te registraste en el negocio.</p>
                        <ul>
                            <li><strong>Si ya eres cliente:</strong> Iniciarás sesión directamente. Si tu número está en varios negocios, el sistema te pedirá que elijas a cuál deseas entrar.</li>
                            <li><strong>Si eres nuevo:</strong> El sistema te indicará que no estás registrado y te ofrecerá un formulario para que completes tus datos y te registres en el negocio de tu elección.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Inicio (Dashboard) -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDashboard">
                        <i class="bi bi-house-door-fill me-2"></i> Inicio (Tu Próxima Cita)
                    </button>
                </h2>
                <div id="collapseDashboard" class="accordion-collapse collapse" data-bs-parent="#ayudaClienteAccordion">
                    <div class="accordion-body">
                        <p>Es tu pantalla principal. Aquí verás un resumen de tu próxima cita pendiente.</p>
                        <strong>Funcionalidades:</strong>
                        <ul>
                            <li><strong>Ver Detalles:</strong> Muestra la fecha, hora, servicio y negocio de tu próxima cita.</li>
                            <li><strong>Confirmar Asistencia:</strong> Permite al negocio saber que asistirás.</li>
                            <li><strong>Cancelar Cita:</strong> Cancela tu cita. Esta acción notificará al negocio.</li>
                            <li><strong>Reagendar:</strong> Te permite elegir una nueva fecha u hora para una cita que aún está pendiente.</li>
                        </ul>
                        <p>Si no tienes citas próximas, verás un mensaje de bienvenida y un botón para agendar tu primera cita.</p>
                    </div>
                </div>
            </div>

            <!-- Agendar Cita -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBooking">
                        <i class="bi bi-calendar-plus-fill me-2"></i> Agendar Cita
                    </button>
                </h2>
                <div id="collapseBooking" class="accordion-collapse collapse" data-bs-parent="#ayudaClienteAccordion">
                    <div class="accordion-body">
                        <p>Esta sección te guía a través de un proceso sencillo de 2 pasos para reservar un nuevo servicio.</p>
                        <ol>
                            <li><strong>Paso 1: Selecciona un Servicio.</strong> Verás una lista de todos los servicios ofrecidos por el negocio, con su duración y precio.</li>
                            <li><strong>Paso 2: Elige Fecha y Hora.</strong> Se mostrará un calendario. Al seleccionar un día, verás todos los horarios disponibles. Simplemente haz clic en el que prefieras y confirma.</li>
                        </ol>
                        <p>Una vez confirmada, recibirás un correo electrónico con los detalles y un evento de calendario (.ics) para que lo añadas a tu agenda personal.</p>
                    </div>
                </div>
            </div>

            <!-- Mi Historial -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHistory">
                        <i class="bi bi-clock-history me-2"></i> Mi Historial
                    </button>
                </h2>
                <div id="collapseHistory" class="accordion-collapse collapse" data-bs-parent="#ayudaClienteAccordion">
                    <div class="accordion-body">
                        <p>Aquí encontrarás un listado completo de todas tus citas, tanto las pasadas como las futuras.</p>
                        <strong>Información Mostrada:</strong>
                        <ul>
                            <li>Fecha y hora de la cita.</li>
                            <li>Nombre del servicio o evento.</li>
                            <li>Estado actual de la cita (Completada, Cancelada, Pendiente, etc.).</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Reseñas -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReviews">
                        <i class="bi bi-star-fill me-2"></i> Reseñas
                    </button>
                </h2>
                <div id="collapseReviews" class="accordion-collapse collapse" data-bs-parent="#ayudaClienteAccordion">
                    <div class="accordion-body">
                        <p>En esta sección puedes compartir tu experiencia y leer las opiniones de otros clientes.</p>
                        <strong>Funcionalidades:</strong>
                        <ul>
                            <li><strong>Ver Reseñas:</strong> Lee los comentarios y puntuaciones que otros clientes han dejado sobre el negocio.</li>
                            <li><strong>Dejar tu Reseña:</strong>
                                <ol>
                                    <li>Selecciona una puntuación de 1 a 5 estrellas.</li>
                                    <li>Escribe un comentario detallando tu experiencia.</li>
                                    <li>Haz clic en "Publicar Reseña" para compartir tu opinión.</li>
                                </ol>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Mi Perfil -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProfile">
                        <i class="bi bi-person-fill-gear me-2"></i> Mi Perfil
                    </button>
                </h2>
                <div id="collapseProfile" class="accordion-collapse collapse" data-bs-parent="#ayudaClienteAccordion">
                    <div class="accordion-body">
                        <p>Mantén tu información de contacto actualizada.</p>
                        <strong>Datos que puedes editar:</strong>
                        <ul>
                            <li>Nombre Completo.</li>
                            <li>Correo Electrónico.</li>
                            <li>Número de Teléfono.</li>
                            <li>Dirección y preferencias de comunicación.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>