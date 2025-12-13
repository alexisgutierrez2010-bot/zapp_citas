// c:/xampp/htdocs/zapp_citas/js/client_modules/booking.js

/**
 * Inicia el flujo de agendamiento de citas, mostrando primero la lista de servicios.
 * @param {object} context - El contexto global de la aplicación.
 */
export async function renderBookingView(context) {
    context.dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border"></div></div>`;

    try {
        const response = await fetch(`${context.API_URL}api_servicios_publicos.php?id_negocio=${context.state.clienteActual.id_negocio}`);
        if (!response.ok) throw new Error(context.T.client_booking_error_loading_services);
        const servicios = await response.json();

        if (servicios.length === 0) {
            context.dom.appContainer.innerHTML = `<div class="alert alert-warning">${context.T.client_booking_no_services}</div>`;
            return;
        }

        const serviciosHtml = servicios.map(s => `
            <a href="#" class="list-group-item list-group-item-action btn-seleccionar-servicio" data-id-servicio="${s.id_servicio}" data-nombre-servicio="${s.nombre_servicio}">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">${s.nombre_servicio}</h5>
                    <small>${context.T.client_booking_duration}: ${s.duracion_valor} ${s.duracion_unidad}</small>
                </div>
                <p class="mb-1">${context.T.client_booking_price}: ${s.precio ? `$${parseFloat(s.precio).toFixed(2)}` : context.T.client_booking_consult}</p>
            </a>
        `).join('');

        context.dom.appContainer.innerHTML = `
            <h3>${context.T.client_booking_title}</h3>
            <div class="card">
                <div class="card-header">${context.T.client_booking_step1}</div>
                <div class="list-group list-group-flush">${serviciosHtml}</div>
            </div>
        `;

        document.querySelectorAll('.btn-seleccionar-servicio').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const idServicio = e.currentTarget.dataset.idServicio;
                const nombreServicio = e.currentTarget.dataset.nombreServicio;
                renderTimeSlotSelection(context, idServicio, nombreServicio);
            });
        });

    } catch (error) {
        context.dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

/**
 * Muestra el selector de fecha y los horarios disponibles para un servicio.
 * @param {object} context - El contexto global de la aplicación.
 * @param {string} idServicio - El ID del servicio seleccionado.
 * @param {string} nombreServicio - El nombre del servicio seleccionado.
 */
function renderTimeSlotSelection(context, idServicio, nombreServicio) {
    const hoy = new Date().toISOString().split('T')[0];
    context.dom.appContainer.innerHTML = `
        <h3>${context.T.client_booking_title_with_service.replace('{service}', nombreServicio)}</h3>
        <div class="card">
            <div class="card-header">${context.T.client_booking_step2}</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label for="fecha-cita" class="form-label">${context.T.client_booking_select_date}</label>
                        <input type="date" id="fecha-cita" class="form-control" min="${hoy}">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">${context.T.client_booking_available_slots}</label>
                        <div id="slots-container" class="p-3 bg-light rounded" style="min-height: 100px;">
                            <p class="text-muted text-center">${context.T.client_booking_select_date_prompt}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-secondary" data-view="booking">${context.T.client_booking_back_to_services}</button>
            </div>
        </div>
    `;

    const fechaInput = document.getElementById('fecha-cita');
    const slotsContainer = document.getElementById('slots-container');

    fechaInput.addEventListener('change', async () => {
        const fechaSeleccionada = fechaInput.value;
        if (!fechaSeleccionada) return;

        slotsContainer.innerHTML = `<div class="text-center"><div class="spinner-border spinner-border-sm"></div> ${context.T.client_booking_searching_slots}</div>`;

        try {
            const response = await fetch(`${context.API_URL}api_cliente_horario_disponible.php?id_negocio=${context.state.clienteActual.id_negocio}&id_servicio=${idServicio}&fecha=${fechaSeleccionada}`);
            if (!response.ok) throw new Error(context.T.client_booking_error_loading_slots);
            const slots = await response.json();

            if (slots.length === 0) {
                slotsContainer.innerHTML = `<p class="text-muted text-center">${context.T.client_booking_no_slots}</p>`;
            } else {
                const slotsHtml = slots.map(slot => `<button class="btn btn-outline-primary m-1 btn-seleccionar-slot" data-fecha-hora="${fechaSeleccionada} ${slot}">${slot}</button>`).join('');
                slotsContainer.innerHTML = `<div class="d-flex flex-wrap">${slotsHtml}</div>`;

                document.querySelectorAll('.btn-seleccionar-slot').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const fechaHora = e.target.dataset.fechaHora;
                        handleBookingConfirmation(context, idServicio, fechaHora, nombreServicio);
                    });
                });
            }
        } catch (error) {
            slotsContainer.innerHTML = `<p class="text-danger text-center">${error.message}</p>`;
        }
    });
}

/**
 * Muestra la confirmación final y procesa el agendamiento de la cita.
 * @param {object} context - El contexto global de la aplicación.
 * @param {string} idServicio - El ID del servicio.
 * @param {string} fechaHora - La fecha y hora seleccionadas.
 * @param {string} nombreServicio - El nombre del servicio.
 */
async function handleBookingConfirmation(context, idServicio, fechaHora, nombreServicio) {
    const fechaObj = new Date(fechaHora);
    const fechaFormateada = fechaObj.toLocaleDateString(context.state.currentLang, { weekday: 'long', day: 'numeric', month: 'long' });
    const horaFormateada = fechaObj.toLocaleTimeString(context.state.currentLang, { hour: '2-digit', minute: '2-digit' });

    const confirmMessage = context.T.client_booking_confirm_prompt
        .replace('{service}', `"${nombreServicio}"`)
        .replace('{date}', fechaFormateada)
        .replace('{time}', horaFormateada);

    if (!confirm(confirmMessage)) return;

    try {
        const payload = { id_cliente: context.state.clienteActual.id_cliente, id_negocio: context.state.clienteActual.id_negocio, id_servicio: idServicio, fecha_hora_inicio: fechaHora };
        const response = await fetch(`${context.API_URL}api_cliente_agendar_cita.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);

        alert(data.message);
        context.renderView('dashboard');
    } catch (error) {
        alert(`${context.T.client_booking_error_final}: ${error.message}`);
    }
}

/**
 * Maneja la cancelación de una cita.
 * @param {object} context - El contexto global de la aplicación.
 * @param {string} idCita - El ID de la cita a cancelar.
 * @param {HTMLElement} button - El botón que disparó la acción, para dar feedback.
 */
export async function handleCancelarCita(context, idCita, button) {
    if (!confirm(context.T.client_dashboard_confirm_cancel)) return;

    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${context.T.processing}...`;

    try {
        const response = await fetch(`${context.API_URL}api_cliente_accion_cita.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id_cita: idCita, id_cliente: context.state.clienteActual.id_cliente, accion: 'cancelar' }) });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);

        // Recargar la vista para mostrar el estado actualizado
        context.renderView('dashboard');
    } catch (error) {
        alert(`${context.T.client_dashboard_error_cancel}: ${error.message}`);
        button.disabled = false;
        button.innerHTML = originalText;
    }
}

/**
 * Maneja la confirmación de una cita.
 * @param {object} context - El contexto global de la aplicación.
 * @param {string} idCita - El ID de la cita a confirmar.
 * @param {HTMLElement} button - El botón que disparó la acción, para dar feedback.
 */
export async function handleConfirmarCita(context, idCita, button) {
    if (!confirm(context.T.client_dashboard_confirm_confirm)) return;

    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${context.T.processing}...`;

    try {
        const response = await fetch(`${context.API_URL}api_cliente_accion_cita.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id_cita: idCita, id_cliente: context.state.clienteActual.id_cliente, accion: 'confirmar' }) });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);

        // Recargar la vista para mostrar el estado actualizado
        context.renderView('dashboard');
    } catch (error) {
        alert(`${context.T.client_dashboard_error_confirm}: ${error.message}`);
        button.disabled = false;
        button.innerHTML = originalText;
    }
}
