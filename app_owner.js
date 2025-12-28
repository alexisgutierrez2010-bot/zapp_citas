// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
// Módulo principal (Cerebro) de la SPA del Propietario

import { renderLoginView, handleLogout, renderStartRegistrationView } from './js/owner_modules/auth.js';
import { renderAgendaView } from './js/owner_modules/agenda.js';
import { renderCalendarioView } from './js/owner_modules/calendario.js';
import { renderClientesView } from './js/owner_modules/clientes.js';
import { renderServiciosView } from './js/owner_modules/servicios.js';
import { renderNegocioView } from './js/owner_modules/negocio.js';
import { renderPerfilView } from './js/owner_modules/perfil.js';
import { renderCrearCitaView, renderEditarCitaView } from './js/owner_modules/citas.js';
import { renderDashboardView } from './js/owner_modules/dashboard.js';
import { renderReviewsView } from './js/owner_modules/reviews.js';
import { updateNavbar, setActiveNavLink } from './js/owner_modules/ui.js';

/**
 * Maneja la acción de cerrar citas vencidas.
 * @param {object} context - El objeto de contexto de la aplicación.
 */
async function handleCloseOverdueAppointments(context) {
    const { API_URL, renderView, state, dom } = context;

    if (!confirm('¿Estás seguro de que quieres marcar como "Vencidas" todas las citas pasadas que aún están "Pendientes"?\n\nEsta acción es útil para limpiar la agenda, pero no se puede deshacer.')) {
        return;
    }

    // Indicador de carga simple
    const originalAppHtml = dom.appContainer.innerHTML;
    dom.appContainer.innerHTML = `<div class="text-center mt-5"><div class="spinner-border" role="status"></div><p class="mt-2">Procesando, por favor espere...</p></div>`;

    try {
        const response = await fetch(`${API_URL}api_owner_citas_cerrar_vencidas.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });

        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error || 'Error al procesar la solicitud.');
        }

        alert(`Proceso completado. Se actualizaron ${data.citas_actualizadas} citas.`);

        // Refresca la vista actual para mostrar los cambios.
        renderView(state.currentView || 'agenda');
    } catch (error) {
        alert(`Error: ${error.message}`);
        dom.appContainer.innerHTML = originalAppHtml; // Restaurar vista en caso de error
    }
}

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
        // CORRECCIÓN: Usar ruta relativa vacía (igual que en app_client.js) para evitar errores 404 si la carpeta cambia.
        API_URL: '',
        renderView: null,
    };

    const routes = {
        'login': renderLoginView,
        'agenda': renderAgendaView,
        'start-register': renderStartRegistrationView,
        'calendario': renderCalendarioView,
        'clientes': renderClientesView,
        'servicios': renderServiciosView,
        'negocio': renderNegocioView,
        'perfil': renderPerfilView,
        'crear-cita': renderCrearCitaView,
        'editar-cita': renderEditarCitaView,
        'dashboard': renderDashboardView, // <-- AÑADIDO: Registrar la nueva ruta
        'reviews': renderReviewsView,
    };

    context.renderView = (viewName, params = {}) => {
        const renderFn = routes[viewName];

        if (typeof renderFn === 'function') {
            context.state.currentView = viewName;
            setActiveNavLink(context.dom.navMenu, viewName);
            renderFn(context, params);
        } else {
            console.error(`Error: View "${viewName}" not found.`);
            context.dom.appContainer.innerHTML = `<div class="alert alert-danger">Error Crítico: No se pudo cargar la vista "<b>${viewName}</b>".<br>Verifique que los módulos JS (auth.js, agenda.js, etc.) estén presentes y cargados correctamente.</div>`;
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
            } else if (action === 'close-overdue-appointments') {
                handleCloseOverdueAppointments(context);
            }
        }
    });

    try {
        // Intentamos obtener el usuario actual. Si no hay sesión, la API devolverá un error 401
        // y la ejecución saltará directamente al bloque catch.
        const userResponse = await fetch(`${context.API_URL}api_owner_get_current_user.php`);
        
        if (!userResponse.ok) {
            throw new Error(`Error en fetch: ${userResponse.status} ${userResponse.statusText}`);
        }

        const userData = await userResponse.json();
        
        context.state.ownerActual = userData;
        updateNavbar(context);
        context.renderView('agenda'); // Vista inicial si hay sesión

    } catch (error) {
        // Si el fetch falla (ej. 401 Unauthorized), mostramos la vista de login.
        console.warn("No se encontró sesión activa, mostrando login.", error.message);
        context.renderView('login');
    }
});
