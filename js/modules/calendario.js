// js/modules/calendario.js

/**
 * Renderiza la vista del Calendario con FullCalendar.
 * @param {object} context - El objeto de contexto de la aplicación.
 */
export async function renderCalendarioView(context) {
    const { dom, T, API_URL, state, renderView } = context;

    dom.appContainer.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>${T.spa_owner_nav_calendar || 'Calendar'}</h3>
            <button class="btn btn-primary" id="btn-agendar-desde-calendario">${T.spa_owner_btn_new_appointment || 'New Appointment'}</button>
        </div>
        <div id="calendar-container" class="bg-white p-3 rounded shadow-sm">
            <div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">${T.spa_owner_loading_calendar}</span></div></div>
        </div>
    `;

    document.getElementById('btn-agendar-desde-calendario').addEventListener('click', () => context.renderView('crear-cita'));

    try {
        // 1. Obtener la configuración del negocio para saber las horas de trabajo
        const negocioResponse = await fetch(`${API_URL}api_owner_negocio_get.php`);
        if (!negocioResponse.ok) throw new Error(T.error_loading_business_config || 'Could not load business configuration.');
        const negocio = await negocioResponse.json();

        // 2. Inicializar el calendario con las horas de trabajo
        const calendarEl = document.getElementById('calendar-container');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            themeSystem: 'bootstrap5',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            locale: state.currentLang,
            initialView: 'dayGridMonth',
            events: `${API_URL}api_owner_calendario_eventos.php`,
            height: 'auto', // Ajustar la altura automáticamente
            businessHours: {
                daysOfWeek: negocio.dias_trabajo.split(',').map(Number), // [1, 2, 3, 4, 5] para L-V
                startTime: negocio.hora_inicio,
                endTime: negocio.hora_cierre,
            },
            eventClick: function(info) {
                context.renderView('editar-cita', { id_cita: info.event.id });
            },
            dateClick: function(info) {
                // Al hacer clic en un día, vamos a la vista de crear cita pre-rellenando la fecha
                context.renderView('crear-cita', { fecha: info.dateStr });
            }
        });
        calendar.render();
    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}