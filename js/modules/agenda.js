// js/modules/agenda.js

/**
 * Renderiza la vista "Mi Agenda" (lista de citas diarias).
 * @param {object} context - El objeto de contexto de la aplicación.
 */
export async function renderAgendaView(context) {
    const { state, dom, T, API_URL } = context; // CORRECCIÓN: No desestructurar renderView

    dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">${T.spa_owner_loading_appointments}</span></div></div>`;

    try {
        const fechaFiltro = state.currentDate.toISOString().split('T')[0];
        
        const response = await fetch(`${API_URL}api_owner_citas.php?fecha=${fechaFiltro}`);
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'No se pudieron cargar las citas.');
        }
        const citas = await response.json();

        let citasHtml = '';
        if (citas.length > 0) {
            const status_colors = {
                'Pendiente': 'bg-info text-dark', 'Completada': 'bg-success',
                'Cancelada': 'bg-danger', 'Pospuesta': 'bg-warning text-dark',
                'No Asistió': 'bg-secondary', 'Confirmada': 'bg-primary',
            };

            const citasRows = citas.map((cita, index) => {
                const inicio = new Date(cita.fecha_hora_inicio);
                const fin = new Date(cita.fecha_hora_fin);
                const estado = cita.estado_cita;
                const color_clase = status_colors[estado] ?? 'bg-light text-dark';

                let accionesEstadoHtml = '';
                if (estado === 'Pendiente') {
                    accionesEstadoHtml += `<li><a class="dropdown-item btn-cambiar-estado" href="#" data-id-cita="${cita.id_cita}" data-nuevo-estado="Confirmada">👍 ${T.confirm || 'Confirm'}</a></li>`;
                    accionesEstadoHtml += `<li><a class="dropdown-item btn-cambiar-estado" href="#" data-id-cita="${cita.id_cita}" data-nuevo-estado="Cancelada">❌ ${T.cancel || 'Cancel'}</a></li>`;
                }
                if (estado === 'Confirmada') {
                    accionesEstadoHtml += `<li><a class="dropdown-item btn-cambiar-estado" href="#" data-id-cita="${cita.id_cita}" data-nuevo-estado="Completada">✅ ${T.complete || 'Complete'}</a></li>`;
                    accionesEstadoHtml += `<li><a class="dropdown-item btn-cambiar-estado" href="#" data-id-cita="${cita.id_cita}" data-nuevo-estado="No Asistió">👤 ${T.no_show || 'No Show'}</a></li>`;
                    accionesEstadoHtml += `<li><a class="dropdown-item btn-cambiar-estado" href="#" data-id-cita="${cita.id_cita}" data-nuevo-estado="Cancelada">❌ ${T.cancel || 'Cancel'}</a></li>`;
                }
                if (['Cancelada', 'Completada', 'No Asistió', 'Vencida'].includes(estado)) {
                    accionesEstadoHtml += `<li><a class="dropdown-item btn-cambiar-estado" href="#" data-id-cita="${cita.id_cita}" data-nuevo-estado="Pendiente">🔄 ${T.mark_pending || 'Mark Pending'}</a></li>`;
                }

                return `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${inicio.toLocaleTimeString(state.currentLang === 'es' ? 'es-ES' : 'en-US', { hour: 'numeric', minute: '2-digit' })} - ${fin.toLocaleTimeString(state.currentLang === 'es' ? 'es-ES' : 'en-US', { hour: 'numeric', minute: '2-digit' })}</td>
                        <td>${cita.nombre_cliente}</td>
                        <td>${cita.nombre_servicio || 'N/A'}</td>
                        <td><span class="badge rounded-pill ${color_clase}">${estado}</span></td>
                        <td><span class="badge rounded-pill bg-dark" title="Emails enviados">${cita.in_email}</span></td>
                        <td><span class="badge rounded-pill bg-dark" title="SMS enviados">${cita.in_sms}</span></td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    ${T.spa_owner_table_actions}
                                </button>
                                <ul class="dropdown-menu">
                                    ${accionesEstadoHtml}
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item btn-enviar-email" href="#" data-id-cita="${cita.id_cita}">📧 ${T.send_email || 'Send Email'}</a></li>
                                    <li><a class="dropdown-item" href="#" data-view="editar-cita" data-id-cita="${cita.id_cita}">✏️ ${T.edit || 'Edit'}</a></li>
                                    <li><a class="dropdown-item text-danger btn-cancel-cita" href="#" data-id-cita="${cita.id_cita}">🗑️ ${T.cancel || 'Cancel'}</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            citasHtml = `
                <table class="table table-striped table-hover">
                    <thead class="table-dark"><tr><th>${T.spa_owner_table_hash_symbol || '#'}</th><th>${T.spa_owner_table_schedule || 'Horario'}</th><th>${T.spa_owner_table_client || 'Cliente'}</th><th>${T.spa_owner_table_service || 'Servicio'}</th><th>${T.spa_owner_table_status || 'Estado'}</th><th>📧</th><th>📱</th><th>${T.spa_owner_table_actions || 'Acciones'}</th></tr></thead>
                    <tbody>${citasRows}</tbody> 
                </table>`;
        } else {
            citasHtml = `<div class="alert alert-info">${T.spa_owner_no_appointments_for_date || 'No hay citas para esta fecha.'}</div>`;
        }

        const agendaTitle = (T.spa_owner_agenda_for_date || 'Mi Agenda ({date})').replace('{date}', state.currentDate.toLocaleDateString(state.currentLang.startsWith('es') ? 'es-ES' : 'en-US', { weekday: 'long', day: 'numeric', month: 'long' }));

        dom.appContainer.innerHTML = ` 
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>${agendaTitle}</h3>
                <div class="btn-group">
                    <button class="btn btn-secondary" id="prev-day-btn">${T.spa_owner_btn_prev_day}</button>
                    <input type="date" class="form-control" id="date-picker" value="${fechaFiltro}">
                    <button class="btn btn-secondary" id="next-day-btn">${T.spa_owner_btn_next_day}</button>
                </div>
            </div>
            ${citasHtml}
            <div class="d-flex justify-content-end">
                 <button class="btn btn-primary" id="btn-agendar-desde-lista">${T.spa_owner_btn_new_appointment || 'Nueva Cita'}</button>
            </div>
        `;

        // Añadir listeners
        document.getElementById('prev-day-btn').addEventListener('click', () => { state.currentDate.setDate(state.currentDate.getDate() - 1); context.renderView('agenda'); });
        document.getElementById('next-day-btn').addEventListener('click', () => { state.currentDate.setDate(state.currentDate.getDate() + 1); context.renderView('agenda'); });
        document.getElementById('date-picker').addEventListener('change', (e) => { 
            const [year, month, day] = e.target.value.split('-').map(Number);
            state.currentDate = new Date(year, month - 1, day);
            context.renderView('agenda');
        });

        // Listeners para acciones que aún no están en módulos
        document.getElementById('btn-agendar-desde-lista').addEventListener('click', () => context.renderView('crear-cita')); // OK
        document.querySelectorAll('[data-view="editar-cita"]').forEach(btn => btn.addEventListener('click', (e) => context.renderView('editar-cita', { id_cita: e.currentTarget.dataset.idCita }))); // OK
        document.querySelectorAll('.btn-cancel-cita').forEach(btn => btn.addEventListener('click', (e) => handleCancelCita(context, e.currentTarget.dataset.idCita)));
        document.querySelectorAll('.btn-enviar-email').forEach(btn => btn.addEventListener('click', (e) => alert(T.feature_in_construction || 'Feature in construction.'))); // OK
        document.querySelectorAll('.btn-cambiar-estado').forEach(btn => btn.addEventListener('click', (e) => handleCambiarEstado(context, e.target.dataset.idCita, e.target.dataset.nuevoEstado)));

    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

async function handleCambiarEstado(context, idCita, nuevoEstado) {
    const { T, API_URL } = context;
    const confirmMessage = (T.spa_owner_confirm_change_status || 'Are you sure you want to change the status?').replace('{status}', nuevoEstado);
    if (!confirm(confirmMessage)) {
        return;
    }
    try {
        const response = await fetch(`${API_URL}api_owner_cita_actualizar.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_cita: idCita, estado_cita: nuevoEstado })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || T.unknown_error || 'Unknown error');
        context.renderView('agenda');
    } catch (error) {
        alert(`${T.operation_error}: ${error.message}`);
    }
}

async function handleCancelCita(context, idCita) {
    const { T, API_URL } = context;
    if (!confirm(T.spa_owner_confirm_cancel_appointment || 'Are you sure you want to cancel this appointment?')) {
        return;
    }
    try {
        const response = await fetch(`${API_URL}api_owner_cita_eliminar.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_cita: idCita })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || T.unknown_error || 'Unknown error when canceling.');
        context.renderView('agenda');
    } catch (error) {
        alert(`${T.operation_error}: ${error.message}`);
    }
}