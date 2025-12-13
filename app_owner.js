// c:/xampp/htdocs/zapp_citas/app_owner.js
// Versión modularizada y refactorizada

// =================================================================================
// MÓDULO PRINCIPAL (CEREBRO) DE LA SPA DEL PROPIETARIO
// =================================================================================
// Este archivo orquesta toda la aplicación. Sus responsabilidades son:
// 1. Importar todos los módulos (vistas y lógica).
// 2. Inicializar el estado global de la aplicación.
// 3. Cargar datos iniciales como las traducciones.
// 4. Verificar la sesión del usuario para decidir si mostrar el login o la agenda.
// 5. Manejar la navegación entre las diferentes vistas.
// =================================================================================

// --- 1. IMPORTACIÓN DE MÓDULOS ---
// Se importan todas las funciones necesarias desde sus respectivos módulos.
// Las rutas apuntan a la carpeta /js/modules/ donde reside toda la lógica específica.

import { renderLoginView, handleLogout } from './js/modules/auth.js';
import { renderAgendaView } from './js/modules/agenda.js';
import { renderCalendarioView } from './js/modules/calendario.js';
import { renderDisponibilidadView } from './js/modules/disponibilidad.js';
import { renderClientesView } from './js/modules/clientes.js';
import { renderServiciosView } from './js/modules/servicios.js';
import { renderNegocioView } from './js/modules/negocio.js';
import { renderPerfilView } from './js/modules/perfil.js';
import { renderCrearCitaView, renderEditarCitaView } from './js/modules/citas.js';
import { updateNavbar, setActiveNavLink } from './js/modules/ui.js';

// --- 2. INICIALIZACIÓN DE LA APLICACIÓN ---
// El evento 'DOMContentLoaded' asegura que el script se ejecute solo cuando el HTML esté completamente cargado.
document.addEventListener('DOMContentLoaded', async function() {

    // --- A. CONTEXTO GLOBAL DE LA APLICACIÓN ---
    // Se crea un objeto 'context' que se pasará a todas las funciones.
    // Esto evita tener variables globales y mantiene el código organizado.
    const context = {
        // Objeto de estado: almacena datos que cambian durante la sesión.
        state: {
            currentView: null,      // La vista que se está mostrando actualmente.
            currentLang: new URLSearchParams(window.location.search).get('lang') || 'es', // Idioma actual.
            ownerActual: null,      // Datos del propietario una vez que inicia sesión.
            currentDate: new Date(),// Fecha seleccionada para vistas como agenda y disponibilidad.
            clockInterval: null,    // Referencia al intervalo del reloj para poder limpiarlo.
        },
        // Referencias al DOM: elementos HTML que se manipulan constantemente.
        dom: {
            appContainer: document.getElementById('app-container'),
            navMenu: document.getElementById('nav-menu'),
            navbarBrand: document.getElementById('navbar-brand-title'),
        },
        // Traducciones: se llenará con los textos del idioma actual.
        T: {},
        // URL de la API: centraliza la ruta a los scripts de backend.
        API_URL: '', // Vacío porque los scripts PHP están en la misma raíz.
        // Función central de renderizado (se define más adelante).
        renderView: null,
    };

    // --- B. CARGA DE DATOS INICIALES (TRADUCCIONES) ---
    // Antes de mostrar nada, se cargan las traducciones para el idioma detectado.
    try {
        const response = await fetch(`${context.API_URL}api_get_translations.php?lang=${context.state.currentLang}`);
        if (!response.ok) {
            throw new Error('Failed to load language file.');
        }
        context.T = await response.json();
    } catch (error) {
        // Si las traducciones fallan, se muestra un error crítico y se detiene la app.
        console.error("Critical Error:", error);
        context.dom.appContainer.innerHTML = `<div class="alert alert-danger">Error crítico: No se pudieron cargar los datos de idioma. La aplicación no puede continuar.</div>`;
        return;
    }

    // --- C. ROUTER: EL GESTOR DE VISTAS ---
    // Se define un objeto que mapea un nombre de vista a la función que la renderiza.
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
    };

    // --- D. FUNCIÓN CENTRAL DE RENDERIZADO ---
    // Esta función es el corazón de la navegación de la SPA.
    context.renderView = (viewName, params = {}) => {
        console.log(`Rendering view: ${viewName}`, params);
        const renderFn = routes[viewName];

        if (typeof renderFn === 'function') {
            context.state.currentView = viewName;
            setActiveNavLink(context.dom.navMenu, viewName);
            // Llama a la función de renderizado correspondiente, pasándole el contexto y los parámetros.
            renderFn(context, params);
        } else {
            console.error(`Error: View "${viewName}" not found.`);
            context.dom.appContainer.innerHTML = `<div class="alert alert-danger">Error: La página solicitada no existe.</div>`;
        }
    };

    // --- E. MANEJADOR DE EVENTOS DE NAVEGACIÓN ---
    // Se añade un único listener al body que captura clics en elementos con 'data-view' o 'data-action'.
    // Esto es más eficiente que añadir un listener a cada botón individualmente.
    document.body.addEventListener('click', function(e) {
        const target = e.target.closest('[data-view], [data-action]');
        if (!target) return;

        e.preventDefault(); // Prevenir la acción por defecto del enlace/botón.

        const view = target.dataset.view;
        const action = target.dataset.action;

        if (view) {
            // Si es un clic para cambiar de vista...
            const params = { ...target.dataset }; // Copiar todos los data-attributes como parámetros.
            context.renderView(view, params);
        } else if (action) {
            // Si es un clic para realizar una acción...
            if (action === 'logout') {
                handleLogout(context);
            }
            // Aquí se pueden añadir más acciones globales si es necesario.
        }
    });

    // --- F. PUNTO DE ARRANQUE DE LA LÓGICA ---
    // Se verifica la sesión del servidor para decidir qué vista mostrar primero.
    try {
        const sessionResponse = await fetch(`${context.API_URL}api_owner_session_check.php`);

        if (!sessionResponse.ok) {
            // Si la respuesta NO es OK (ej. 401 Unauthorized), significa que no hay sesión.
            // Se muestra la vista de login.
            context.renderView('login');
            return; // Se detiene la ejecución aquí.
        }

        // Si la respuesta es OK (200), se procesa para obtener los datos del usuario.
        const sessionData = await sessionResponse.json();
        
        // Se guarda la información del propietario en el estado global.
        context.state.ownerActual = sessionData.owner;
        
        // Se actualiza la barra de navegación para mostrar el menú de usuario logueado.
        updateNavbar(context);
        
        // Se muestra la vista principal de la agenda.
        context.renderView('agenda');

    } catch (error) {
        // Si hay cualquier otro error durante la comprobación (ej. red), se va al login por seguridad.
        console.error("Error checking session, defaulting to login view.", error);
        context.renderView('login');
    }
});
