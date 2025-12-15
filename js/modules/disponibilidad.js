// js/modules/disponibilidad.js

/**
 * Renderiza la vista "Disponibilidad del Día", mostrando slots libres y ocupados.
 * @param {object} context - El objeto de contexto de la aplicación.
 */
export async function renderDisponibilidadView(context) {
    const { state, dom, API_URL, renderView } = context;

    dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div></div>`;

    try {
        const fechaFiltro = state.currentDate.toISOString().split('T')[0];
        const response = await fetch(`${API_URL}api_owner_horario_disponible.php?fecha=${fechaFiltro}`);
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'No se pudo cargar el horario.');
        }
        const data = await response.json();
        const slots = data.slots;
        const infoNegocio = data.info_negocio;

        let cronogramaHtml = '';
        const diasTrabajo = infoNegocio.dias_trabajo ? infoNegocio.dias_trabajo.split(',').map(String) : [];
        const diaSemanaActual = (state.currentDate.getDay() === 0) ? '7' : String(state.currentDate.getDay());

        if (slots.length === 0 && !diasTrabajo.includes(diaSemanaActual)) {
            const dayName = state.currentDate.toLocaleDateString('es-ES', { weekday: 'long' });
            cronogramaHtml = `<div class="alert alert-warning">El negocio está cerrado los ${dayName}.</div>`;
        } else if (slots.length === 0) {
            cronogramaHtml = `<div class="alert alert-info">No hay horarios disponibles para este día.</div>`;
        } else {
            cronogramaHtml = `<div class="list-group">`;
            slots.forEach(slot => {
                let slotClass = 'list-group-item';
                let slotContent = `<strong>${slot.start_time} - ${slot.end_time}</strong>`;
                let actionButton = '';

                if (slot.status === 'booked') {
                    slotClass += ' list-group-item-danger';
                    slotContent += `<br>${slot.cita.nombre_cliente} - ${slot.cita.nombre_servicio} (${slot.cita.estado_cita})`;
                    actionButton = `<button class="btn btn-sm btn-outline-light btn-edit-cita" data-id-cita="${slot.cita.id_cita}">Editar</button>`;
                } else {
                    slotClass += ' list-group-item-success';
                    slotContent += `<br>Disponible`;
                    actionButton = `<button class="btn btn-sm btn-outline-light btn-agendar-cita" data-start-time="${fechaFiltro} ${slot.start_time}">Agendar</button>`;
                }

                cronogramaHtml += `
                    <div class="${slotClass} d-flex justify-content-between align-items-center">
                        <div>${slotContent}</div>
                        <div>${actionButton}</div>
                    </div>
                `;
            });
            cronogramaHtml += `</div>`;
        }

        const viewTitle = `Disponibilidad para el ${state.currentDate.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' })}`;

        dom.appContainer.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>${viewTitle}</h3>
                <div class="btn-group">
                    <button class="btn btn-secondary" id="prev-day-btn">« Día Anterior</button>
                    <input type="date" class="form-control" id="date-picker" value="${fechaFiltro}">
                    <button class="btn btn-secondary" id="next-day-btn">Día Siguiente »</button>
                </div>
            </div>
            ${cronogramaHtml}
        `;

        // Añadir listeners para la navegación por fecha
        document.getElementById('prev-day-btn').addEventListener('click', () => { state.currentDate.setDate(state.currentDate.getDate() - 1); renderView('disponibilidad'); });
        document.getElementById('next-day-btn').addEventListener('click', () => { state.currentDate.setDate(state.currentDate.getDate() + 1); renderView('disponibilidad'); });
        document.getElementById('date-picker').addEventListener('change', (e) => {
            const [year, month, day] = e.target.value.split('-').map(Number);
            state.currentDate = new Date(year, month - 1, day);
            renderView('disponibilidad');
        });

        // Listeners para acciones (placeholders por ahora)
        document.querySelectorAll('.btn-edit-cita').forEach(btn => btn.addEventListener('click', (e) => context.renderView('editar-cita', { id_cita: e.target.dataset.idCita })));
        document.querySelectorAll('.btn-agendar-cita').forEach(btn => btn.addEventListener('click', (e) => {
            const [fecha, hora] = e.target.dataset.startTime.split(' ');
            // Llamamos a la vista de crear cita, pasando la fecha y hora para pre-rellenar
            context.renderView('crear-cita', { fecha: fecha, hora: hora });
        }));
    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}