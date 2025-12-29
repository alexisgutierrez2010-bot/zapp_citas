<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
?><!DOCTYPE html><html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayuda - App Propietario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css' rel='stylesheet'>
    <style>
        body { 
            background-color: #f8f9fa; 
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .container { max-width: 960px; }
        .card-header { background-color: #343a40; color: white; }
        .accordion-button:not(.collapsed) { color: #0c63e4; background-color: #e7f1ff; }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <img src="logo_zapp_citas.png" alt="Logo ZApp Citas" style="height: 2.5em; margin-right: 15px;">
                <h1 class="h2 mb-0">Ayuda de la Aplicación del Propietario</h1>
            </div>
            <a href="javascript:window.close();" class="btn btn-secondary">Cerrar Ventana</a>
        </div>

        <div class="card">
            <div class="card-header"><h4>Guía Rápida de Uso</h4></div>
            <div class="card-body">
                <p class="lead">Bienvenido a la guía de la aplicación de gestión para propietarios. Aquí encontrarás una explicación detallada de cada sección y funcionalidad para que puedas administrar tu negocio de manera eficiente.</p>

                <div class="accordion" id="ayudaAccordion">

                    <!-- Sección 1: Navegación -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                <i class="bi bi-compass-fill me-2"></i> Navegación Principal
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#ayudaAccordion">
                            <div class="accordion-body">
                                La barra de navegación superior es tu centro de control. Contiene los siguientes elementos:
                                <ul>
                                    <li><strong>Nombre del Negocio:</strong> Al hacer clic aquí, te llevará al <strong>Resumen de Gestión (Dashboard)</strong>.</li>
                                    <li><strong>Mi Agenda:</strong> Es la vista principal. Muestra la lista de citas para el día seleccionado.</li>
                                    <li><strong>Calendario:</strong> Ofrece una vista mensual, semanal o diaria de todas tus citas.</li>
                                    <li><strong>Mis Clientes:</strong> Te permite ver, registrar, editar y desactivar a tus clientes.</li>
                                    <li><strong>Mis Servicios:</strong> Para administrar los servicios que ofreces, incluyendo su duración y precio.</li>
                                    <li><strong>Administración (Menú desplegable):</strong>
                                        <ul>
                                            <li><strong>Mi Negocio:</strong> Configura los datos generales, dirección y horario de trabajo de tu negocio.</li>
                                            <li><strong>Dashboard:</strong> Accede al "Resumen de Gestión" con gráficos de rendimiento.</li>
                                            <li><strong>Mi Perfil:</strong> Te permite cambiar tu contraseña de acceso.</li>
                                        </ul>
                                    </li>
                                    <li><strong>Cerrar Sesión:</strong> Finaliza tu sesión de forma segura en la aplicación.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 2: Mi Agenda -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                <i class="bi bi-list-ul me-2"></i> Mi Agenda (Vista Diaria)
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#ayudaAccordion">
                            <div class="accordion-body">
                                <p>Esta es la pantalla principal donde gestionas las citas del día a día.</p>
                                <ul>
                                    <li><strong>Controles de Fecha:</strong> Usa los botones "Día Anterior", "Día Siguiente" o el selector de fecha para navegar rápidamente a cualquier día.</li>
                                    <li><strong>Lista de Citas:</strong> Cada fila representa una cita y muestra información clave como el horario, cliente, servicio/asunto y estado actual.</li>
                                    <li><strong>Menú "Acciones":</strong> Cada cita tiene un menú desplegable con opciones para gestionarla.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 3: Acciones de Cita -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                <i class="bi bi-toggles me-2"></i> Menú de Acciones de Cita
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#ayudaAccordion">
                            <div class="accordion-body">
                                <p>El menú "Acciones" te permite controlar el ciclo de vida de cada cita:</p>
                                <ul>
                                    <li><strong>Cambiar Estado:</strong> Puedes marcar una cita como <code>Confirmada</code>, <code>Completada</code>, <code>Cancelada</code>, etc. Estos cambios son lógicos y mantienen el registro en el sistema.</li>
                                    <li><strong>Enviar Email:</strong> Permite enviar o reenviar una notificación por correo al cliente y a los invitados.</li>
                                    <li><strong><span class="text-danger">🔥 Eliminar</span>:</strong> Esta es una <strong>acción irreversible</strong>. Elimina la cita y todos sus datos asociados (incluyendo invitados) de la base de datos. Úsala con precaución, por ejemplo, para citas creadas por error.</li>
                                    <li><strong>✏️ Editar:</strong> Abre el formulario para modificar los detalles de la cita o reunión.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 4: Creación de Citas y Reuniones -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                <i class="bi bi-plus-circle-fill me-2"></i> Creación de Citas y Reuniones
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#ayudaAccordion">
                            <div class="accordion-body">
                                <p>El formulario de "Agendar Nueva Cita" te permite crear dos tipos de eventos:</p>
                                <dl>
                                    <dt>Servicio</dt>
                                    <dd>Es el tipo de cita estándar. Debes seleccionar un cliente y un servicio de tu catálogo. La duración se calcula automáticamente.</dd>
                                    <dt>Reunión</dt>
                                    <dd>Este tipo de cita es ideal para eventos que no son un servicio, como consultas o reuniones de equipo. En lugar de un servicio, puedes escribir un "Asunto" y añadir una lista de invitados.</dd>
                                </dl>
                                <p><strong>Gestión de Invitados (para Reuniones):</strong></p>
                                <ul>
                                    <li><strong>Añadir:</strong> Ingresa el nombre, correo y teléfono (opcional) y haz clic en "Añadir".</li>
                                    <li><strong>Editar:</strong> Haz clic en el botón "Editar" de un invitado en la lista, modifica sus datos y haz clic en "Actualizar".</li>
                                    <li><strong>Eliminar:</strong> Haz clic en la "X" junto a un invitado para quitarlo de la lista.</li>
                                </ul>
                                <p>Al guardar, si la opción "Notificar al cliente" está marcada, se enviará un correo de confirmación tanto al cliente principal como a todos los invitados de la reunión.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 5: Dashboard -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFive">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                <i class="bi bi-bar-chart-line-fill me-2"></i> Resumen de Gestión (Dashboard)
                            </button>
                        </h2>
                        <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#ayudaAccordion">
                            <div class="accordion-body">
                                <p>Esta sección te ofrece una vista rápida del rendimiento de tu negocio en los últimos 6 meses a través de cuatro gráficos:</p>
                                <ol>
                                    <li><strong>Cantidad de Nuevos Clientes por Mes:</strong> Muestra cuántos clientes nuevos has registrado cada mes.</li>
                                    <li><strong>Estado de Citas por Mes:</strong> Desglosa las citas de cada mes por su estado (Registradas, Completadas, Canceladas), permitiéndote medir la eficiencia.</li>
                                    <li><strong>Ingresos por Servicios Completados:</strong> Suma los precios de todas las citas de servicios marcadas como "Completada" cada mes.</li>
                                    <li><strong>Cantidad de Reuniones Registradas por Mes:</strong> Muestra cuántas citas de tipo "Reunión" has agendado.</li>
                                </ol>
                                <p>Los números que aparecen sobre cada barra te dan el valor exacto para una lectura más rápida.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sección Reseñas -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingReviews">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReviews" aria-expanded="false" aria-controls="collapseReviews">
                                <i class="bi bi-star-fill me-2"></i> Gestión de Reseñas
                            </button>
                        </h2>
                        <div id="collapseReviews" class="accordion-collapse collapse" aria-labelledby="headingReviews" data-bs-parent="#ayudaAccordion">
                            <div class="accordion-body">
                                <p>Esta sección centraliza todas las opiniones y valoraciones que los clientes han dejado sobre tu negocio.</p>
                                <ul>
                                    <li><strong>Puntuación Promedio:</strong> En la parte superior, verás la calificación promedio general basada en todas las reseñas recibidas.</li>
                                    <li><strong>Listado de Reseñas:</strong> Cada reseña muestra la puntuación (de 1 a 5 estrellas), el comentario del cliente, el nombre del cliente y la fecha en que se publicó.</li>
                                    <li><strong>Contexto del Servicio:</strong> Si la reseña fue dejada para un servicio específico, el nombre del servicio aparecerá junto a la reseña.</li>
                                </ul>
                                <p>Utiliza esta sección para entender la percepción de tus clientes y mejorar la calidad de tu servicio.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 6: Otras Secciones -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingSix">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                                <i class="bi bi-gear-wide-connected me-2"></i> Otras Secciones de Administración
                            </button>
                        </h2>
                        <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#ayudaAccordion">
                            <div class="accordion-body">
                                <ul>
                                    <li>
                                        <strong>Mis Clientes:</strong> Aquí puedes ver tu lista completa de clientes. Usa los botones para registrar uno nuevo, editar la información de uno existente, o desactivarlo si ya no es un cliente activo. Un cliente desactivado no aparecerá en el formulario para crear nuevas citas.
                                    </li>
                                    <li>
                                        <strong>Mis Servicios:</strong> Administra los servicios que ofreces. Puedes crear nuevos, editar su nombre, duración y precio, o desactivarlos. Un servicio desactivado no podrá ser seleccionado al agendar nuevas citas.
                                    </li>
                                    <li>
                                        <strong>Mi Negocio:</strong> En esta sección configuras la información vital de tu negocio, como el nombre, email de contacto, dirección y, muy importante, tu horario de trabajo (días y horas de inicio/cierre). Esta configuración afecta directamente los horarios disponibles para agendar citas.
                                    </li>
                                    <li>
                                        <strong>Mi Perfil:</strong> Una sección simple y segura para que puedas cambiar tu contraseña de acceso a la aplicación cuando lo necesites.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <footer class="footer mt-auto py-1 bg-dark text-white-50">
        <div class="container text-center">
            <small style="font-size: 0.8rem;">©2025. Authorized by <a href="http://www.acticven.com" target="_blank" class="text-white">www.acticven.com</a> All rights reserved. Version 1.12.29</small>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>