<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayuda Principal - ZApp Citas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f8f9fa; 
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .card-header { background-color: #343a40; color: white; }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1><i class="bi bi-question-circle-fill text-primary"></i> Guía del Ecosistema ZApp Citas</h1>
            <a href="javascript:window.close();" class="btn btn-secondary">Cerrar Ventana</a>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h4>Objetivo del Sistema</h4>
            </div>
            <div class="card-body">
                <p>ZApp Citas es un sistema integral diseñado para facilitar la gestión de citas. Está compuesto por tres aplicaciones interconectadas, cada una con un propósito específico, para cubrir todas las necesidades de administradores, propietarios de negocios y clientes finales.</p>
            </div>
        </div>

        <div class="accordion" id="ayudaAccordion">
            <!-- Sección ZApp Citas -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        <i class="bi bi-gear-wide-connected me-2"></i> <strong>Panel de Administración (ZApp Citas)</strong>
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#ayudaAccordion">
                    <div class="accordion-body">
                        <p>Es el núcleo del sistema. Permite la gestión completa de negocios, usuarios, clientes, servicios, citas y configuraciones globales.</p>
                        <strong>Funcionalidades:</strong>
                        <ul>
                            <li>Creación y configuración de nuevos negocios.</li>
                            <li>Administración de usuarios propietarios y sus permisos.</li>
                            <li>Supervisión de todas las citas, clientes y servicios del sistema.</li>
                            <li>Gestión de catálogos maestros (categorías, países, etc.).</li>
                        </ul>
                        <strong>Acceso:</strong> Se accede a través de la tarjeta "ZApp Citas" en la página de inicio, usando credenciales de administrador.
                    </div>
                </div>
            </div>

            <!-- Sección App Propietario -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOwner">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOwner" aria-expanded="false" aria-controls="collapseOwner">
                        <i class="bi bi-shop me-2"></i> <strong>App del Propietario</strong>
                    </button>
                </h2>
                <div id="collapseOwner" class="accordion-collapse collapse" aria-labelledby="headingOwner" data-bs-parent="#ayudaAccordion">
                    <div class="accordion-body">
                        <p>Una interfaz moderna y rápida diseñada para que los dueños de negocios gestionen su agenda, clientes y servicios de forma eficiente.</p>
                        <strong>Funcionalidades:</strong>
                        <ul>
                            <li>Gestión completa de la agenda diaria y calendario.</li>
                            <li>Administración de la base de datos de clientes y catálogo de servicios.</li>
                            <li>Configuración del perfil del negocio, incluyendo horarios de trabajo.</li>
                        </ul>
                        <strong>Acceso:</strong> Se accede a través de la tarjeta 'App del Propietario' en la página de inicio.
                    </div>
                </div>
            </div>

            <!-- Sección App Cliente -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingClient">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseClient" aria-expanded="false" aria-controls="collapseClient">
                        <i class="bi bi-person-circle me-2"></i> <strong>App del Cliente</strong>
                    </button>
                </h2>
                <div id="collapseClient" class="accordion-collapse collapse" aria-labelledby="headingClient" data-bs-parent="#ayudaAccordion">
                    <div class="accordion-body">
                        <p>Un portal de autoservicio para que los clientes finales puedan agendar y gestionar sus citas de forma fácil y rápida.</p>
                        <strong>Funcionalidades:</strong>
                        <ul>
                            <li>Inicio de sesión rápido usando solo el número de teléfono.</li>
                            <li>Registro de nuevos clientes.</li>
                            <li>Proceso guiado para agendar nuevas citas.</li>
                            <li>Visualización del historial de citas y gestión del perfil personal.</li>
                            <li>Publicación y visualización de reseñas sobre el negocio.</li>
                        </ul>
                        <strong>Acceso:</strong> Se accede a través de la tarjeta "App del Cliente" en la página de inicio.
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