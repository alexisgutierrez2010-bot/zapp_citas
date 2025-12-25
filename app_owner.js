// Módulo principal (Cerebro) de la SPA del Propietario

import { renderLoginView, handleLogout, renderStartRegistrationView } from './js/owner_modules/auth.js';
import { renderAgendaView } from './js/owner_modules/agenda.js';
import { renderCalendarioView } from './js/owner_modules/calendario.js';
import { renderDisponibilidadView } from './js/owner_modules/disponibilidad.js';
import { renderClientesView } from './js/owner_modules/clientes.js';
import { renderServiciosView } from './js/owner_modules/servicios.js';
import { renderNegocioView } from './js/owner_modules/negocio.js';
import { renderPerfilView } from './js/owner_modules/perfil.js';
import { renderCrearCitaView, renderEditarCitaView } from './js/owner_modules/citas.js';
import { renderReviewsView } from './js/owner_modules/reviews.js'; // NUEVO
import { renderDashboardView } from './js/owner_modules/dashboard.js';
import { updateNavbar, setActiveNavLink } from './js/owner_modules/ui.js';

document.addEventListener('DOMContentLoaded', async function() {
    console.log(">>> [Sentinel 1] DOMContentLoaded: Iniciando app_owner.js");

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
        // CORRECCIÓN: Usar ruta relativa vacía (igual que en app_client.js) para evitar errores 404 si la carpeta cambia.
        API_URL: '',
        renderView: null,
    };

    // SOLUCIÓN: Restaurar el objeto de rutas que fue eliminado por error.
    // Este objeto es el "mapa" que le dice a la aplicación qué función ejecutar para cada vista.
    const routes = {
        'login': renderLoginView,
        'agenda': renderAgendaView,
        'start-register': renderStartRegistrationView,
        'calendario': renderCalendarioView,
        'disponibilidad': renderDisponibilidadView,
        'clientes': renderClientesView,
        'servicios': renderServiciosView,
        'negocio': renderNegocioView,
        'perfil': renderPerfilView,
        'crear-cita': renderCrearCitaView,
        'editar-cita': renderEditarCitaView,
        'dashboard': renderDashboardView, // <-- AÑADIDO: Registrar la nueva ruta
        'reviews': renderReviewsView, // NUEVO
    };

    context.renderView = (viewName, params = {}) => {
        console.log(`>>> [Sentinel 2] renderView llamado para: ${viewName}`, params);
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
        console.log(`>>> [Sentinel 3] Intentando fetch a: ${context.API_URL}api_owner_get_current_user.php`);
        
        // SOLUCIÓN: Se unifican las llamadas de arranque en una sola.
        // El script `api_owner_get_current_user.php` ya incluye la verificación de sesión.
        // Si no hay sesión, devolverá un error 401 que será capturado por el `catch`.
        // Si hay sesión, devolverá los datos del usuario.
        // Esto es más eficiente (una llamada de red en lugar de dos) y soluciona el bloqueo.
        const userResponse = await fetch(`${context.API_URL}api_owner_get_current_user.php`);
        
        console.log(`>>> [Sentinel 4] Respuesta recibida. Status: ${userResponse.status}`);
        
        if (!userResponse.ok) {
            throw new Error(`Error en fetch: ${userResponse.status} ${userResponse.statusText}`);
        }

        // Si la llamada fue exitosa, guardar los datos del usuario y mostrar la agenda.
        const userData = await userResponse.json();
        console.log(">>> [Sentinel 5] Datos de usuario parseados:", userData);
        
        context.state.ownerActual = userData;
        updateNavbar(context); // SOLUCIÓN: Se revierte a la llamada original, la nueva lógica de ui.js no necesita el contenedor.
        
        console.log(">>> [Sentinel 6] Renderizando vista inicial (agenda)");
        context.renderView('agenda'); // VISTA INICIAL: Se restaura la agenda como vista principal.

    } catch (error) {
        // Si CUALQUIER paso del 'try' falla (no hay sesión, no se encuentran datos de usuario, etc.), se llega aquí.
        // Mostramos el login de forma segura.
        console.error(">>> [Sentinel 7] Error capturado (probablemente sin sesión), mostrando Login:", error);
        context.renderView('login');
    }
});
