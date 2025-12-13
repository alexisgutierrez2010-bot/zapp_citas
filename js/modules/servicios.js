// js/modules/servicios.js

/**
 * Renderiza la vista "Mis Servicios".
 * @param {object} context - El objeto de contexto de la aplicación.
 */
export async function renderServiciosView(context) {
    const { dom, T, API_URL } = context;
    dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">${T.spa_owner_loading_services}</span></div></div>`;

    try {
        const response = await fetch(`${API_URL}api_owner_servicios.php`);
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || T.error_loading_services || 'Could not load services.');
        }
        const servicios = await response.json();

        let serviciosHtml = '';
        if (servicios.length > 0) {
            const serviciosRows = servicios.map((servicio, index) => {
                const precioFormateado = servicio.precio ? `$${parseFloat(servicio.precio).toFixed(2)}` : 'N/A';
                return `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${servicio.nombre_servicio}</td>
                        <td>${servicio.duracion_valor} ${servicio.duracion_unidad}</td>
                        <td>${precioFormateado}</td>
                        <td>
                            <button class="btn btn-sm btn-warning btn-edit-servicio" data-id-servicio='${JSON.stringify(servicio)}'>${T.edit || 'Edit'}</button>
                            <button class="btn btn-sm btn-danger btn-delete-servicio" data-id-servicio="${servicio.id_servicio}">${T.deactivate || 'Deactivate'}</button>
                        </td>
                    </tr>
                `;
            }).join('');

            serviciosHtml = `
                <table class="table table-striped table-hover">
                    <thead class="table-dark"><tr><th>#</th><th>${T.services_col_service || 'Service'}</th><th>${T.services_col_duration || 'Duration'}</th><th>${T.services_col_price || 'Price'}</th><th>${T.actions || 'Actions'}</th></tr></thead>
                    <tbody>${serviciosRows}</tbody>
                </table>`;
        } else {
            serviciosHtml = `<div class="alert alert-info">${T.services_no_services || 'No services registered.'}</div>`;
        }

        dom.appContainer.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>${T.services_title || 'Service Management'}</h3>
                <button class="btn btn-primary" id="btn-crear-servicio">${T.services_register_new || 'Register New Service'}</button>
            </div>
            ${serviciosHtml}
        `;

        document.getElementById('btn-crear-servicio').addEventListener('click', () => openServicioModal(context));
        document.querySelectorAll('.btn-edit-servicio').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const servicioData = JSON.parse(e.currentTarget.dataset.idServicio);
                openServicioModal(context, servicioData);
            });
        });
        document.querySelectorAll('.btn-delete-servicio').forEach(btn => {
            btn.addEventListener('click', (e) => handleDeleteServicio(context, e.target.dataset.idServicio));
        });

    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

async function handleDeleteServicio(context, id_servicio) {
    const { T, API_URL, renderView } = context;
    if (!confirm(T.services_confirm_deactivate || 'Are you sure you want to deactivate this service?')) return;
    try {
        const response = await fetch(`${API_URL}api_owner_servicio_eliminar.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_servicio: id_servicio })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);
        alert(data.message);
        renderView('servicios');
    } catch (error) {
        alert(`${T.operation_error || 'Operation Error'}: ${error.message}`);
    }
}

function openServicioModal(context, servicio = null) {
    const { T, API_URL, renderView } = context;
    const isNew = !servicio;
    const modalTitle = isNew ? T.services_register_new : T.services_edit_title;
    const duracionUnidades = { 'Minutos': T.duration_minutes, 'Horas': T.duration_hours, 'Dias': T.duration_days };
    const unidadesOptions = Object.entries(duracionUnidades).map(([key, value]) => `<option value="${key}" ${!isNew && servicio.duracion_unidad === key ? 'selected' : ''}>${value}</option>`).join('');

    const modalHtml = `
        <div class="modal fade" id="servicioModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">${modalTitle}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div id="modal-error-container"></div>
                        <form id="servicio-form">
                            <div class="mb-3"><label for="nombre_servicio" class="form-label">${T.services_form_name || 'Service Name'}</label><input type="text" class="form-control" id="nombre_servicio" value="${servicio?.nombre_servicio || ''}" required></div>
                            <div class="mb-3"><label class="form-label">${T.services_form_duration || 'Duration'}</label><div class="input-group"><input type="number" class="form-control" id="duracion_valor" value="${servicio?.duracion_valor || 30}" required><select class="form-select" id="duracion_unidad">${unidadesOptions}</select></div></div>
                            <div class="mb-3"><label for="precio" class="form-label">${T.services_form_price || 'Price (optional)'}</label><input type="number" step="0.01" class="form-control" id="precio" value="${servicio?.precio || ''}"></div>
                        </form>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${T.cancel || 'Cancel'}</button><button type="button" class="btn btn-primary" id="save-servicio-btn">${T.save || 'Save'}</button></div>
                </div>
            </div>
        </div>`;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modalElement = document.getElementById('servicioModal');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    document.getElementById('save-servicio-btn').addEventListener('click', async () => {
        const endpoint = isNew ? `${API_URL}api_owner_servicio_crear.php` : `${API_URL}api_owner_servicio_actualizar.php`;
        const payload = { id_servicio: servicio?.id_servicio, nombre_servicio: document.getElementById('nombre_servicio').value, duracion_valor: document.getElementById('duracion_valor').value, duracion_unidad: document.getElementById('duracion_unidad').value, precio: document.getElementById('precio').value };
        try {
            const saveResponse = await fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const saveData = await saveResponse.json();
            if (!saveResponse.ok) throw new Error(saveData.error);
            modal.hide();
            renderView('servicios');
        } catch (saveError) {
            document.getElementById('modal-error-container').innerHTML = `<div class="alert alert-danger">${saveError.message}</div>`;
        }
    });

    modalElement.addEventListener('hidden.bs.modal', () => modalElement.remove());
}