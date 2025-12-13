// js/app_owner.js
import { renderLoginView, handleLogout } from './js/modules/auth.js';
import { updateNavbar, setActiveNavLink } from './js/modules/ui.js';
// SOLUCIÓN: Importar la nueva función para renderizar la agenda.
import { renderAgendaView } from './js/modules/agenda.js';
import { renderCalendarioView } from './js/modules/calendario.js';
import { renderDisponibilidadView } from './js/modules/disponibilidad.js';
import { renderClientesView } from './js/modules/clientes.js';
import { renderServiciosView } from './js/modules/servicios.js';
import { renderNegocioView } from './js/modules/negocio.js';
import { renderPerfilView } from './js/modules/perfil.js';
import { renderCrearCitaView, renderEditarCitaView } from './js/modules/citas.js';

document.addEventListener('DOMContentLoaded', function() {
    // 1. Definir el contexto de la aplicación
    const context = {
        // Constantes
        API_URL: '',

        // Estado de la aplicación (variables que cambian)
        state: {
            ownerActual: null,
            currentDate: new Date(),
            clockInterval: null,
            T: {}, // Objeto para las traducciones
            currentLang: 'es',
        },

        // Referencias a elementos del DOM
        dom: {
            navbarBrand: document.getElementById('navbar-brand-title'),
            appContainer: document.getElementById('app-container'),
            navMenu: document.getElementById('nav-menu'),
        },

        // Mapeo de vistas a funciones de renderizado
        views: {
            // SOLUCIÓN: Registrar la vista 'agenda' con su función correspondiente.
            'agenda': renderAgendaView,
            'calendario': renderCalendarioView,
            'disponibilidad': renderDisponibilidadView,
            'clientes': renderClientesView,
            'servicios': renderServiciosView,
            'negocio': renderNegocioView,
            'perfil': renderPerfilView,
            'crear-cita': renderCrearCitaView,
            'editar-cita': renderEditarCitaView,
            // ...etc.
        },

        /**
         * Función central para renderizar vistas.
         * @param {string} viewName - El nombre de la vista a renderizar (ej. 'agenda').
         * @param {object} [params={}] - Parámetros adicionales para pasar a la función de renderizado.
         */
        renderView(viewName, params = {}) {
            // Detener procesos en segundo plano de la vista anterior (si los hay)
            if (this.state.clockInterval) clearInterval(this.state.clockInterval);

            const renderFunction = this.views[viewName];
            if (typeof renderFunction === 'function') {
                setActiveNavLink(this.dom.navMenu, viewName); // setActiveNavLink no necesita params
                renderFunction(this, params); // Pasamos el contexto y los parámetros
            } else {
                // Fallback para vistas aún no migradas
                console.warn(`Vista "${viewName}" no implementada como módulo.`);
                this.dom.appContainer.innerHTML = `<h2>Vista "${viewName}" en construcción.</h2>`;
                // Mantener el link activo en la vista fallback
                setActiveNavLink(this.dom.navMenu, viewName);
            }
        }
    };

    // 2. Configurar la navegación principal
    function setupNavigation() {
        context.dom.navMenu.addEventListener('click', (e) => {
            const target = e.target;

            // Navegación por vistas
            if (target.matches('[data-view]')) {
                e.preventDefault();
                const viewName = target.dataset.view;
                context.renderView(viewName);
            }

            // Acciones especiales
            if (target.matches('[data-action]')) {
                e.preventDefault();
                const actionName = target.dataset.action;
                switch (actionName) {
                    case 'logout':
                        handleLogout(context);
                        break;
                    case 'cierre-citas':
                        // handleCierreCitas(context); // Descomentar cuando se cree el módulo
                        alert(context.T.feature_in_construction || 'Feature in construction.');
                        break;
                }
            }
        });
    }

    // 3. Función principal de inicialización
    async function main() {
        // Determinar idioma
        const urlParams = new URLSearchParams(window.location.search);
        context.state.currentLang = urlParams.get('lang') || 'es';

        // Cargar traducciones
        try {
            const response = await fetch(`${context.API_URL}api_get_translations.php?lang=${context.state.currentLang}&module=owner`);
            if (!response.ok) throw new Error('Failed to load translations');
            context.state.T = await response.json();
        } catch (error) {
            console.error("Error loading translations:", error);
            context.dom.appContainer.innerHTML = `<div class="alert alert-danger">Error fatal: No se pudieron cargar los textos de la aplicación.</div>`;
            return;
        }

        // Asignar T al contexto para fácil acceso
        context.T = context.state.T;

        // Configurar la navegación
        setupNavigation();

        // Iniciar la aplicación mostrando la vista de login
        renderLoginView(context);
    }

    main();
});