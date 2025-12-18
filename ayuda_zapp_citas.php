<?php
// Update: Dec-14-2025. Eliminada lógica multi-idioma.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayuda - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card-header { background-color: #343a40; color: white; }
        .accordion-button { font-weight: 500; }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1><i class="bi bi-question-circle-fill text-primary"></i> Guía del Panel de Administración</h1>
            <a href="javascript:window.close();" class="btn btn-secondary">Cerrar Ventana</a>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h4>Objetivo del Panel</h4>
            </div>
            <div class="card-body">
                <p>Este panel es el centro de control total del sistema ZApp Citas. Está diseñado para que el administrador principal (rol "Master") pueda supervisar y gestionar todos los aspectos de la plataforma, desde la creación de negocios hasta la auditoría de acciones.</p>
            </div>
        </div>

        <div class="accordion" id="ayudaAdminAccordion">
            <!-- Dashboard -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDash" aria-expanded="true">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </button>
                </h2>
                <div id="collapseDash" class="accordion-collapse collapse show" data-bs-parent="#ayudaAdminAccordion">
                    <div class="accordion-body">
                        <p>Es la pantalla de bienvenida. Ofrece un resumen rápido del estado del sistema.</p>
                        <strong>Funcionalidades:</strong>
                        <ul>
                            <li>Contadores totales de negocios, usuarios, clientes y citas.</li>
                            <li>Accesos directos a las secciones más importantes.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Negocios -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNegocios">
                        <i class="bi bi-building me-2"></i> Negocios
                    </button>
                </h2>
                <div id="collapseNegocios" class="accordion-collapse collapse" data-bs-parent="#ayudaAdminAccordion">
                    <div class="accordion-body">
                        <p>Esta sección permite la gestión completa de las cuentas de los negocios que usan la plataforma.</p>
                        <strong>Funcionalidades:</strong>
                        <ul>
                            <li><strong>Crear Negocio:</strong> Registra un nuevo negocio y su usuario propietario asociado.</li>
                            <li><strong>Configurar Negocio:</strong> Edita todos los detalles de un negocio, incluyendo su horario laboral, período de prueba y estado (activo, suspendido).</li>
                            <li><strong>Eliminar Negocio:</strong> Borra permanentemente un negocio y todos sus datos asociados (usuarios, clientes, citas). ¡Esta acción es irreversible!</li>
                            <li><strong>Imagen de Fondo:</strong> Personaliza la imagen de fondo que verá el propietario en su SPA.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Clientes y Servicios -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseClientesServicios">
                        <i class="bi bi-people-fill me-2"></i> Clientes y Servicios
                    </button>
                </h2>
                <div id="collapseClientesServicios" class="accordion-collapse collapse" data-bs-parent="#ayudaAdminAccordion">
                    <div class="accordion-body">
                        <p>Permite administrar los clientes y servicios de cualquier negocio registrado en el sistema.</p>
                        <strong>Gestión de Clientes:</strong>
                        <ul>
                            <li>Ver la lista de clientes de un negocio específico.</li>
                            <li>Crear, editar o desactivar clientes manualmente.</li>
                            <li>Subir o cambiar la foto de perfil de un cliente.</li>
                        </ul>
                        <strong>Gestión de Servicios:</strong>
                        <ul>
                            <li>Ver la lista de servicios de un negocio.</li>
                            <li>Crear, editar o desactivar servicios, definiendo su duración y precio.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Citas y Calendario -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCitas">
                        <i class="bi bi-calendar-check me-2"></i> Citas y Calendario
                    </button>
                </h2>
                <div id="collapseCitas" class="accordion-collapse collapse" data-bs-parent="#ayudaAdminAccordion">
                    <div class="accordion-body">
                        <p>Permite una supervisión completa de todas las citas agendadas en la plataforma.</p>
                        <strong>Funcionalidades:</strong>
                        <ul>
                            <li><strong>Lista de Citas:</strong> Vista tabular con filtros para buscar citas por negocio, cliente, servicio o rango de fechas.</li>
                            <li><strong>Crear Cita:</strong> Agendar manualmente una cita para cualquier cliente de cualquier negocio.</li>
                            <li><strong>Editar Cita:</strong> Modificar los detalles de una cita existente.</li>
                            <li><strong>Cambiar Estado:</strong> Actualizar el estado de una cita (ej. de 'Pendiente' a 'Confirmada').</li>
                            <li><strong>Cancelar Cita:</strong> Marca una cita como 'Cancelada' (borrado lógico).</li>
                            <li><strong>Vista de Calendario:</strong> Un calendario visual que muestra todas las citas del sistema.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Configuración -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseConfig">
                        <i class="bi bi-sliders me-2"></i> Configuración
                    </button>
                </h2>
                <div id="collapseConfig" class="accordion-collapse collapse" data-bs-parent="#ayudaAdminAccordion">
                    <div class="accordion-body">
                        <p>Esta sección agrupa las configuraciones maestras del sistema.</p>
                        <strong>Funcionalidades:</strong>
                        <ul>
                            <li><strong>Usuarios:</strong> Permite crear, editar y desactivar las cuentas de los usuarios (Propietarios) que pueden acceder al sistema.</li>
                            <li><strong>Categorías:</strong> Administra las categorías en las que se pueden clasificar los negocios (ej. "Peluquería", "Consultorio Médico").</li>
                            <li><strong>Localizaciones:</strong> Gestiona la lista de países y sus respectivos estados/provincias, que se usarán en los formularios de dirección.</li>
                            <li><strong>Auditoría:</strong> Muestra un registro detallado de todas las acciones importantes realizadas en el sistema, permitiendo filtrar por usuario, negocio o fecha. Es una herramienta clave para la seguridad y el seguimiento.</li>
                            <li><strong>Documentos:</strong> Un gestor de archivos interno para subir, ver y eliminar documentos importantes (.pdf, .txt, .sql, .md).</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Mi Perfil -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePerfil">
                        <i class="bi bi-person-circle me-2"></i> Mi Perfil y Sesión
                    </button>
                </h2>
                <div id="collapsePerfil" class="accordion-collapse collapse" data-bs-parent="#ayudaAdminAccordion">
                    <div class="accordion-body">
                        <p>Opciones relacionadas con tu cuenta de usuario personal.</p>
                        <strong>Funcionalidades:</strong>
                        <ul>
                            <li><strong>Mi Perfil:</strong> Te permite cambiar tu propia contraseña de acceso al panel de administración.</li>
                            <li><strong>Salir:</strong> Cierra tu sesión de forma segura.</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>