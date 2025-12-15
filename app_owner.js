// Módulo principal (Cerebro) de la SPA del Propietario

import { renderLoginView, handleLogout } from './js/modules/auth.js';
import { renderAgendaView } from './js/modules/agenda.js';
import { renderCalendarioView } from './js/modules/calendario.js';
import { renderDisponibilidadView } from './js/modules/disponibilidad.js';
import { renderClientesView } from './js/modules/clientes.js';
import { renderServiciosView } from './js/modules/servicios.js';
import { renderNegocioView } from './js/modules/negocio.js';
import { renderPerfilView } from './js/modules/perfil.js';
import { renderCrearCitaView, renderEditarCitaView } from './js/modules/citas.js';
import { renderDashboardView } from './js/modules/dashboard.js'; // <-- AÑADIDO: Importar la nueva vista
import { updateNavbar, setActiveNavLink } from './js/modules/ui.js';

document.addEventListener('DOMContentLoaded', async function() {

    const context = {
        state: {
            currentView: null,      // La vista que se está mostrando actualmente.
            // currentLang: ELIMINADO
            ownerActual: null,      // Datos del propietario una vez que inicia sesión.
            currentDate: new Date(),// Fecha seleccionada para vistas como agenda y disponibilidad.
            clockInterval: null,    // Referencia al intervalo del reloj para poder limpiarlo.
        },
        dom: {
            appContainer: document.getElementById('app-container'),
            navMenu: document.getElementById('nav-menu'),
            navbarBrand: document.getElementById('navbar-brand-title'),
            globalErrorContainer: document.getElementById('global-error-container'),
        },
        // SOLUCIÓN: Usar una ruta absoluta para evitar problemas de resolución de URL.
        API_URL: '/zapp_citas/',
        renderView: null,
    };

    // SOLUCIÓN: Restaurar el objeto de rutas que fue eliminado por error.
    // Este objeto es el "mapa" que le dice a la aplicación qué función ejecutar para cada vista.
    const routes = {
        'login': renderLoginView,
        'agenda': renderAgendaView,
        'calendario': renderCalendarioView,
        'disponibilidad': renderDisponibilidadView,
        'clientes': renderClientesView,
        'servicios': renderServiciosView,
        'negocio': renderNegocioView,
        'perfil': renderPerfilView,
        'crear-cita': renderCrearCitaView,
        'editar-cita': renderEditarCitaView,
        'dashboard': renderDashboardView, // <-- AÑADIDO: Registrar la nueva ruta
    };

    context.renderView = (viewName, params = {}) => {
        console.log(`Rendering view: ${viewName}`, params);
        const renderFn = routes[viewName];

        if (typeof renderFn === 'function') {
            context.state.currentView = viewName;
            setActiveNavLink(context.dom.navMenu, viewName);
            renderFn(context, params);
        } else {
            console.error(`Error: View "${viewName}" not found.`);
            context.dom.appContainer.innerHTML = `<div class="alert alert-danger">Error: La página solicitada no existe.</div>`;
        }
    };

    document.body.addEventListener('click', function(e) {
        const target = e.target.closest('[data-view], [data-action]');
        if (!target) return;

        e.preventDefault(); // Prevenir la acción por defecto del enlace/botón.

        const view = target.dataset.view;
        const action = target.dataset.action;

        if (view) {
            const params = { ...target.dataset }; // Copiar todos los data-attributes como parámetros.
            context.renderView(view, params);
        } else if (action) {
            if (action === 'logout') {
                handleLogout(context);
            }
        }
    });

    try {
        // 1. Verificar si hay una sesión activa.
        const sessionResponse = await fetch(`${context.API_URL}api_owner_session_check.php`);
        if (!sessionResponse.ok) {
            // Si no hay sesión (error 401), lanzamos un error para ir directamente al bloque catch.
            throw new Error('No active session');
        }

        // 2. Si la sesión es válida, obtener los datos del usuario desde su propia API.
        const userResponse = await fetch(`${context.API_URL}api_owner_get_current_user.php`);
        if (!userResponse.ok) throw new Error('Session is valid, but failed to fetch user data.');

        // 3. Si todo está bien, guardar los datos del usuario y mostrar la agenda.
        const userData = await userResponse.json();
        context.state.ownerActual = userData;
        updateNavbar(context); // SOLUCIÓN: Se revierte a la llamada original, la nueva lógica de ui.js no necesita el contenedor.
        
        context.renderView('agenda'); // VISTA INICIAL: Se restaura la agenda como vista principal.

    } catch (error) {
        // Si CUALQUIER paso del 'try' falla (no hay sesión, no se encuentran datos de usuario, etc.), se llega aquí.
        // Mostramos el login de forma segura.
        console.error("Error checking session, defaulting to login view.", error);
        context.renderView('login');
    }
});
