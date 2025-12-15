// js/modules/calendario.js

/**
 * Obtiene la configuración del negocio desde la API.
 * @param {string} API_URL - La URL base de la API.
 * @returns {Promise<object>} La configuración del negocio.
 */
async function getBusinessConfig(API_URL) {
    try {
        const response = await fetch(`${API_URL}api_owner_negocio_get.php`);
        if (!response.ok) {
            return {}; // Devuelve objeto vacío en caso de error para usar los defaults.
        }
        return await response.json();
    } catch (error) {
        console.error("Error fetching business config:", error);
        return {};
    }
}

/**
 * Renderiza la vista del Calendario con FullCalendar.
 * @param {object} context - El objeto de contexto de la aplicación.
 */
export async function renderCalendarioView(context) {
    const { dom, API_URL, state, renderView } = context;

    // Obtener la configuración del negocio para ajustar las horas del calendario.
    const businessConfig = await getBusinessConfig(API_URL);

    dom.appContainer.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Calendario</h3>
            <button class="btn btn-primary" id="btn-agendar-desde-calendario">Agendar Nueva Cita</button>
        </div>
        <div id="calendar-container" class="bg-white p-3 rounded shadow-sm">
            <div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando calendario...</span></div></div>
        </div>
    `;

    document.getElementById('btn-agendar-desde-calendario').addEventListener('click', () => context.renderView('crear-cita'));

    const calendarEl = document.getElementById('calendar-container');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        themeSystem: 'bootstrap5',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        locale: 'es',
        initialView: 'dayGridMonth',
        events: `${API_URL}api_owner_calendario_eventos.php`,
        height: 'auto',
        slotMinTime: businessConfig.hora_inicio || '08:00:00',
        slotMaxTime: businessConfig.hora_cierre || '20:00:00',
        scrollTime: businessConfig.hora_inicio || '08:00:00',
        eventClick: function(info) {
            context.renderView('editar-cita', { id_cita: info.event.id });
        }
    });
    calendar.render();
}