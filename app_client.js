// c:/xampp/htdocs/zapp_citas/app_client.js
// Versión modularizada y refactorizada para la SPA del Cliente

// --- 1. IMPORTACIÓN DE MÓDULOS ---
// Importamos toda la lógica desde la carpeta /js/client_modules/
import { renderDashboardView } from './js/client_modules/dashboard.js';
import { renderLoginView, handleLogout, renderConfirmacionRegistroView, renderRegistroView } from './js/client_modules/auth.js';
import { renderProfileView } from './js/client_modules/profile.js';
import { renderHistoryView } from './js/client_modules/history.js';
import { renderBookingView, handleConfirmarCita, handleCancelarCita, handleRescheduleCita } from './js/client_modules/booking.js';
import { renderReviewsView } from './js/client_modules/reviews.js';
import { updateNavbar, setActiveNavLink } from './js/client_modules/ui.js';

// --- 2. INICIALIZACIÓN DE LA APLICACIÓN ---
document.addEventListener('DOMContentLoaded', async function() {

    // --- A. CONTEXTO GLOBAL DE LA APLICACIÓN ---
    const context = {
        state: {
            currentView: null,
            clienteActual: null,
            negocioInfo: null,
        },
        dom: {
            appContainer: document.getElementById('app-container'),
            navMenu: document.getElementById('nav-menu'),
            navbarBrand: document.getElementById('navbar-brand-title'),
        },
        API_URL: '', // Misma raíz
        renderView: null, // Se define más abajo
        updateNavbar: updateNavbar, // Hacemos la función de UI accesible globalmente en el contexto
    };

    // --- C. ROUTER: GESTOR DE VISTAS ---
    const routes = {
        'login': renderLoginView,
        'dashboard': renderDashboardView,
        'profile': renderProfileView,
        'history': renderHistoryView,
        'booking': renderBookingView,
        'confirm-register': renderConfirmacionRegistroView,
        'register': renderRegistroView,
        'reviews': renderReviewsView,
    };

    // --- D. FUNCIÓN CENTRAL DE RENDERIZADO ---
    context.renderView = (viewName, params = {}) => {
        console.log(`Rendering client view: ${viewName}`, params);
        const renderFn = routes[viewName];

        if (typeof renderFn === 'function') {
            context.state.currentView = viewName;
            setActiveNavLink(context);
            renderFn(context, params);
        } else {
            console.error(`Error: View "${viewName}" not found.`);
            context.dom.appContainer.innerHTML = `<div class="alert alert-danger">Error: La página solicitada no existe.</div>`;
        }
    };

    // --- E. MANEJADOR DE EVENTOS DE NAVEGACIÓN ---
    document.body.addEventListener('click', function(e) {
        const target = e.target.closest('[data-view], [data-action]');
        if (!target) return;

        e.preventDefault();
        const view = target.dataset.view;
        const action = target.dataset.action;

        if (view) {
            const params = { ...target.dataset };
            context.renderView(view, params);
        } else if (action) {
            if (action === 'logout') {
                handleLogout(context);
            } else if (action === 'confirm-appointment') {
                const idCita = target.dataset.idCita;
                handleConfirmarCita(context, idCita, target); // Pasamos el botón para dar feedback
            } else if (action === 'cancel-appointment') {
                const idCita = target.dataset.idCita;
                handleCancelarCita(context, idCita, target); // Pasamos el botón para dar feedback
            } else if (action === 'reschedule-appointment') {
                const { idCita, idServicio, nombreServicio, fechaHoraInicio } = target.dataset;
                handleRescheduleCita(context, idCita, idServicio, nombreServicio, fechaHoraInicio);
            }
        }
    });

    // --- F. PUNTO DE ARRANQUE DE LA LÓGICA ---
    try {
        // 1. Verificar si hay una sesión activa.
        const sessionResponse = await fetch(`${context.API_URL}api_cliente_session_check.php`);
        if (!sessionResponse.ok) {
            // Si no hay sesión (error 401), lanzamos un error para ir directamente al bloque catch.
            throw new Error('No active session');
        }

        // 2. Si la sesión es válida, obtener los datos del cliente.
        const sessionData = await sessionResponse.json();
        context.state.clienteActual = sessionData.client;
        
        // 3. Si todo está bien, actualizar la UI y mostrar el dashboard (próxima cita).
        await updateNavbar(context);
        context.renderView('dashboard');

    } catch (error) {
        // Si CUALQUIER paso del 'try' falla, se llega aquí.
        // Mostramos el login de forma segura.
        console.error("Error checking session, defaulting to login view.", error);
        await updateNavbar(context);
        context.renderView('login');
    }
});