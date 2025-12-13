// js/modules/citas.js

/**
 * Renderiza la vista para crear una nueva cita.
 * @param {object} context - El objeto de contexto de la aplicación.
 * @param {object} [prefillData={}] - Datos para pre-rellenar el formulario (ej. fecha).
 */
export async function renderCrearCitaView(context, prefillData = {}) {
    const { dom, T, API_URL, renderView } = context;
    dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"></div></div>`;

    try {
        // Cargar clientes y servicios en paralelo para optimizar
        const [clientesResponse, serviciosResponse] = await Promise.all([
            fetch(`${API_URL}api_owner_clientes.php`),
            fetch(`${API_URL}api_owner_servicios.php`)
        ]);

        if (!clientesResponse.ok || !serviciosResponse.ok) {
            throw new Error(T.error_loading_scheduling_data || 'Could not load necessary data for scheduling.');
        }

        const clientes = await clientesResponse.json();
        const servicios = await serviciosResponse.json();

        const clientesOptions = clientes.map(c => `<option value="${c.id_cliente}">${c.nombre_completo}</option>`).join('');
        const serviciosOptions = servicios.map(s => `<option value="${s.id_servicio}">${s.nombre_servicio} (${s.duracion_valor} ${s.duracion_unidad})</option>`).join('');

        // Formatear fecha y hora pre-rellenadas si existen
        const ahora = new Date();
        let fechaPrefill = prefillData.fecha || ahora.toISOString().split('T')[0];
        let horaPrefill = prefillData.hora || ahora.toTimeString().substring(0, 5);

        dom.appContainer.innerHTML = `
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header"><h3>${T.spa_owner_btn_new_appointment}</h3></div>
                        <div class="card-body">
                            <div id="error-container-cita"></div>
                            <form id="crear-cita-form">
                                <div class="mb-3">
                                    <label for="id_cliente" class="form-label">${T.spa_owner_table_client}</label>
                                    <select id="id_cliente" class="form-select" required>${clientesOptions}</select>
                                </div>
                                <div class="mb-3">
                                    <label for="id_servicio" class="form-label">${T.spa_owner_table_service}</label>
                                    <select id="id_servicio" class="form-select" required>${serviciosOptions}</select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="fecha_cita" class="form-label">${T.appointments_date || 'Date'}</label>
                                        <input type="date" id="fecha_cita" class="form-control" value="${fechaPrefill}" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="hora_cita" class="form-label">${T.appointments_time || 'Time'}</label>
                                        <input type="time" id="hora_cita" class="form-control" value="${horaPrefill}" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="descripcion_trabajo" class="form-label">${T.clients_form_notes || 'Notes'} (${T.optional || 'Optional'})</label>
                                    <textarea id="descripcion_trabajo" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="notificar_cliente" checked>
                                    <label class="form-check-label" for="notificar_cliente">
                                        ${T.clients_form_email_notifications || 'Email Notifications'}
                                    </label>
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary" id="btn-cancelar-crear-cita">${T.cancel}</button>
                                    <button type="submit" class="btn btn-primary">${T.save}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Listeners del formulario
        document.getElementById('btn-cancelar-crear-cita').addEventListener('click', () => context.renderView('agenda'));
        document.getElementById('crear-cita-form').addEventListener('submit', (e) => handleCrearCita(e, context));

    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

/**
 * Maneja el envío del formulario de creación de citas.
 * @param {Event} e - El evento de submit.
 * @param {object} context - El objeto de contexto de la aplicación.
 */
async function handleCrearCita(e, context) {
    e.preventDefault();
    const { T, API_URL } = context;
    const errorContainer = document.getElementById('error-container-cita');
    const submitButton = e.target.querySelector('button[type="submit"]');
    errorContainer.innerHTML = '';

    const fecha = document.getElementById('fecha_cita').value;
    const hora = document.getElementById('hora_cita').value;

    const payload = {
        id_cliente: document.getElementById('id_cliente').value,
        id_servicio: document.getElementById('id_servicio').value,
        fecha_hora_inicio: `${fecha} ${hora}`,
        descripcion_trabajo: document.getElementById('descripcion_trabajo').value,
        notificar_cliente: document.getElementById('notificar_cliente').checked,
        tipo_cita: 'Servicio' // Por ahora solo manejamos tipo Servicio
    };

    submitButton.disabled = true;
    submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${T.saving}...`;

    try {
        const response = await fetch(`${API_URL}api_owner_cita_crear.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error || T.unknown_error || 'An unknown error occurred.');
        }

        alert(data.message); // Mostrar mensaje de éxito (ej. "Cita agendada y notificación enviada")
        context.renderView('agenda'); // Volver a la agenda para ver la nueva cita

    } catch (error) {
        errorContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = T.save;
    }
}

/**
 * Renderiza la vista para editar una cita existente.
 * @param {object} context - El objeto de contexto de la aplicación.
 * @param {object} params - Parámetros, debe contener { id_cita: '...' }.
 */
export async function renderEditarCitaView(context, params) {
    const { dom, T, API_URL, renderView } = context;
    const id_cita = params.id_cita;

    if (!id_cita) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">${T.error_no_appointment_id || 'Error: No appointment ID specified for editing.'}</div>`;
        return;
    }

    dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"></div></div>`;

    try {
        // Cargar datos de la cita, clientes y servicios en paralelo
        const [citaResponse, clientesResponse, serviciosResponse] = await Promise.all([
            fetch(`${API_URL}api_owner_cita_detalle.php?id_cita=${id_cita}`),
            fetch(`${API_URL}api_owner_clientes.php`),
            fetch(`${API_URL}api_owner_servicios.php`)
        ]);

        if (!citaResponse.ok) throw new Error(T.error_loading_appointment_details || 'Could not load appointment details.');
        if (!clientesResponse.ok || !serviciosResponse.ok) throw new Error(T.error_loading_editing_data || 'Could not load necessary data for editing.');

        const cita = await citaResponse.json();
        const clientes = await clientesResponse.json();
        const servicios = await serviciosResponse.json();

        const clientesOptions = clientes.map(c => `<option value="${c.id_cliente}" ${c.id_cliente == cita.id_cliente ? 'selected' : ''}>${c.nombre_completo}</option>`).join('');
        const serviciosOptions = servicios.map(s => `<option value="${s.id_servicio}" ${s.id_servicio == cita.id_servicio ? 'selected' : ''}>${s.nombre_servicio} (${s.duracion_valor} ${s.duracion_unidad})</option>`).join('');

        const fechaHora = new Date(cita.fecha_hora_inicio);
        const fechaPrefill = fechaHora.toISOString().split('T')[0];
        const horaPrefill = fechaHora.toTimeString().substring(0, 5);

        dom.appContainer.innerHTML = `
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header"><h3>${T.edit_appointment || 'Edit Appointment'}</h3></div>
                        <div class="card-body">
                            <div id="error-container-cita"></div>
                            <form id="editar-cita-form">
                                <input type="hidden" id="id_cita" value="${cita.id_cita}">
                                <div class="mb-3">
                                    <label for="id_cliente" class="form-label">${T.spa_owner_table_client}</label>
                                    <select id="id_cliente" class="form-select" required>${clientesOptions}</select>
                                </div>
                                <div class="mb-3">
                                    <label for="id_servicio" class="form-label">${T.spa_owner_table_service}</label>
                                    <select id="id_servicio" class="form-select" required>${serviciosOptions}</select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="fecha_cita" class="form-label">${T.appointments_date || 'Date'}</label>
                                        <input type="date" id="fecha_cita" class="form-control" value="${fechaPrefill}" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="hora_cita" class="form-label">${T.appointments_time || 'Time'}</label>
                                        <input type="time" id="hora_cita" class="form-control" value="${horaPrefill}" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="descripcion_trabajo" class="form-label">${T.clients_form_notes || 'Notes'} (${T.optional || 'Optional'})</label>
                                    <textarea id="descripcion_trabajo" class="form-control" rows="3">${cita.descripcion_trabajo || ''}</textarea>
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary" id="btn-cancelar-editar-cita">${T.cancel}</button>
                                    <button type="submit" class="btn btn-primary">${T.save_changes || 'Save Changes'}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('btn-cancelar-editar-cita').addEventListener('click', () => context.renderView('agenda'));
        document.getElementById('editar-cita-form').addEventListener('submit', (e) => handleEditarCita(e, context));

    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

/**
 * Maneja el envío del formulario de edición de citas.
 * @param {Event} e - El evento de submit.
 * @param {object} context - El objeto de contexto de la aplicación.
 */
async function handleEditarCita(e, context) {
    e.preventDefault();
    const { T, API_URL } = context;
    const errorContainer = document.getElementById('error-container-cita');
    const submitButton = e.target.querySelector('button[type="submit"]');
    errorContainer.innerHTML = '';

    const fecha = document.getElementById('fecha_cita').value;
    const hora = document.getElementById('hora_cita').value;

    const payload = {
        id_cita: document.getElementById('id_cita').value,
        id_cliente: document.getElementById('id_cliente').value,
        id_servicio: document.getElementById('id_servicio').value,
        fecha_hora_inicio: `${fecha} ${hora}`,
        descripcion_trabajo: document.getElementById('descripcion_trabajo').value,
    };

    submitButton.disabled = true;
    submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${T.saving}...`;

    try {
        const response = await fetch(`${API_URL}api_owner_cita_actualizar.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        if (!response.ok) throw new Error(data.error || T.unknown_error || 'An unknown error occurred.');

        alert(data.message);
        context.renderView('agenda');

    } catch (error) {
        errorContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = T.save_changes || 'Save Changes';
    }
}