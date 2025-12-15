// js/modules/agenda.js

/**
 * Formatea un objeto Date a 'YYYY-MM-DD' de forma segura, ignorando la zona horaria.
 * @param {Date} date - El objeto de fecha a formatear.
 * @returns {string} La fecha en formato YYYY-MM-DD.
 */
function formatDateForInput(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * Renderiza la vista "Mi Agenda" (lista de citas diarias).
 * @param {object} context - El objeto de contexto de la aplicación.
 */
export async function renderAgendaView(context) {
    const { state, dom, API_URL } = context;

    dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando citas...</span></div></div>`;

    try {
        // SOLUCIÓN: Usar una función helper para formatear la fecha sin problemas de zona horaria.
        const fechaFiltro = formatDateForInput(state.currentDate);
        
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
                    accionesEstadoHtml += `<li><a class="dropdown-item btn-cambiar-estado" href="#" data-id-cita="${cita.id_cita}" data-nuevo-estado="Confirmada">👍 Confirmar</a></li>`;
                    accionesEstadoHtml += `<li><a class="dropdown-item btn-cambiar-estado" href="#" data-id-cita="${cita.id_cita}" data-nuevo-estado="Cancelada">❌ Cancelar</a></li>`;
                }
                if (estado === 'Confirmada') {
                    accionesEstadoHtml += `<li><a class="dropdown-item btn-cambiar-estado" href="#" data-id-cita="${cita.id_cita}" data-nuevo-estado="Completada">✅ Completar</a></li>`;
                    accionesEstadoHtml += `<li><a class="dropdown-item btn-cambiar-estado" href="#" data-id-cita="${cita.id_cita}" data-nuevo-estado="No Asistió">👤 No Asistió</a></li>`;
                    accionesEstadoHtml += `<li><a class="dropdown-item btn-cambiar-estado" href="#" data-id-cita="${cita.id_cita}" data-nuevo-estado="Cancelada">❌ Cancelar</a></li>`;
                }
                if (['Cancelada', 'Completada', 'No Asistió', 'Vencida'].includes(estado)) {
                    accionesEstadoHtml += `<li><a class="dropdown-item btn-cambiar-estado" href="#" data-id-cita="${cita.id_cita}" data-nuevo-estado="Pendiente">🔄 Marcar Pendiente</a></li>`;
                }

                return `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${inicio.toLocaleTimeString('es-ES', { hour: 'numeric', minute: '2-digit' })} - ${fin.toLocaleTimeString('es-ES', { hour: 'numeric', minute: '2-digit' })}</td>
                        <td>${cita.nombre_cliente}</td>
                        <td><span class="badge bg-light text-dark">${cita.tipo_cita || 'Servicio'}</span></td>
                        <td>${
                            cita.tipo_cita === 'Reunion'
                                ? (cita.descripcion_trabajo || 'Sin asunto')
                                : (cita.nombre_servicio || 'N/A')
                        }
                        </td>
                        <td><span class="badge rounded-pill ${color_clase}">${estado}</span></td>
                        <td><span class="badge rounded-pill bg-dark" title="Emails enviados">${cita.in_email}</span></td>
                        <td><span class="badge rounded-pill bg-dark" title="SMS enviados">${cita.in_sms}</span></td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Acciones
                                </button>
                                <ul class="dropdown-menu">
                                    ${accionesEstadoHtml}
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item btn-enviar-email" href="#" data-id-cita="${cita.id_cita}">📧 Enviar Email</a></li>
                                    <li><hr class="dropdown-divider text-danger"></li>
                                    <li><a class="dropdown-item text-danger btn-eliminar-fisico-cita" href="#" data-id-cita="${cita.id_cita}">🔥 Eliminar</a></li>
                                    <li><a class="dropdown-item" href="#" data-view="editar-cita" data-id-cita="${cita.id_cita}">✏️ Editar</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            citasHtml = `
                <table class="table table-striped table-hover">
                    <thead class="table-dark"><tr><th>#</th><th>Horario</th><th>Cliente</th><th>Tipo</th><th>Servicio/Asunto</th><th>Estado</th><th>📧</th><th>📱</th><th>Acciones</th></tr></thead>
                    <tbody>${citasRows}</tbody> 
                </table>`;
        } else {
            citasHtml = `<div class="alert alert-info">No hay citas agendadas para esta fecha.</div>`;
        }

        const agendaTitle = `Mi Agenda (${state.currentDate.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' })})`;

        dom.appContainer.innerHTML = ` 
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>${agendaTitle}</h3>
                <div class="btn-group">
                    <button class="btn btn-secondary" id="prev-day-btn">« Día Anterior</button>
                    <input type="date" class="form-control" id="date-picker" value="${fechaFiltro}">
                    <button class="btn btn-secondary" id="next-day-btn">Día Siguiente »</button>
                </div>
            </div>
            ${citasHtml}
            <div class="d-flex justify-content-end">
                 <button class="btn btn-primary" id="btn-agendar-desde-lista">Agendar Nueva Cita</button>
            </div>
        `;

        // Añadir listeners
        // SOLUCIÓN: Se refactoriza el manejo de fechas para evitar problemas de zona horaria.
        // En lugar de modificar la fecha existente, se crea una nueva para asegurar consistencia.
        document.getElementById('prev-day-btn').addEventListener('click', () => { 
            const nuevaFecha = new Date(state.currentDate);
            nuevaFecha.setDate(nuevaFecha.getDate() - 1);
            state.currentDate = nuevaFecha;
            context.renderView('agenda'); 
        });
        document.getElementById('next-day-btn').addEventListener('click', () => { 
            const nuevaFecha = new Date(state.currentDate);
            nuevaFecha.setDate(nuevaFecha.getDate() + 1);
            state.currentDate = nuevaFecha;
            context.renderView('agenda'); 
        });
        document.getElementById('date-picker').addEventListener('change', (e) => { 
            const [year, month, day] = e.target.value.split('-').map(Number);
            state.currentDate = new Date(year, month - 1, day, 12, 0, 0); // Fijar a mediodía para evitar errores de TZ
            context.renderView('agenda');
        });

        // Listeners para acciones que aún no están en módulos
        document.getElementById('btn-agendar-desde-lista').addEventListener('click', () => context.renderView('crear-cita')); // OK
        document.querySelectorAll('[data-view="editar-cita"]').forEach(btn => btn.addEventListener('click', (e) => context.renderView('editar-cita', { id_cita: e.currentTarget.dataset.idCita }))); // OK
        document.querySelectorAll('.btn-enviar-email').forEach(btn => btn.addEventListener('click', (e) => alert('Función en construcción.'))); // OK
        document.querySelectorAll('.btn-eliminar-fisico-cita').forEach(btn => btn.addEventListener('click', (e) => handlePhysicalDeleteCita(context, e.currentTarget.dataset.idCita)));
        document.querySelectorAll('.btn-cambiar-estado').forEach(btn => btn.addEventListener('click', (e) => handleCambiarEstado(context, e.target.dataset.idCita, e.target.dataset.nuevoEstado)));

    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

async function handleCambiarEstado(context, idCita, nuevoEstado) {
    const { API_URL } = context;
    const confirmMessage = `¿Estás seguro de que quieres cambiar el estado a "${nuevoEstado}"?`;
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
        if (!response.ok) throw new Error(data.error || 'Error desconocido');
        alert(data.message);
        context.renderView('agenda');
    } catch (error) {
        alert(`Error en la operación: ${error.message}`);
    }
}

/**
 * Maneja la eliminación física y permanente de una cita.
 * @param {object} context - El objeto de contexto de la aplicación.
 * @param {string} idCita - El ID de la cita a eliminar.
 */
async function handlePhysicalDeleteCita(context, idCita) {
    const { API_URL, renderView } = context;
    const confirmMessage1 = '¡ACCIÓN IRREVERSIBLE!\n\n¿Estás seguro de que quieres eliminar esta cita? Se borrará de la base de datos y no se podrá recuperar.';
    const confirmMessage2 = 'CONFIRMACIÓN FINAL: ¿Realmente quieres proceder con la eliminación?';

    if (confirm(confirmMessage1) && confirm(confirmMessage2)) {
        try {
            const response = await fetch(`${API_URL}api_owner_cita_eliminar_fisico.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_cita: idCita })
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'Error desconocido al eliminar.');
            alert(data.message);
            renderView('agenda');
        } catch (error) {
            alert(`Error en la operación: ${error.message}`);
        }
    }
}