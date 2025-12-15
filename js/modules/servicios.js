// js/modules/servicios.js

/**
 * Renderiza la vista "Mis Servicios".
 * @param {object} context - El objeto de contexto de la aplicación.
 */
export async function renderServiciosView(context) {
    const { dom, API_URL, renderView } = context;
    dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando servicios...</span></div></div>`;

    try {
        const response = await fetch(`${API_URL}api_owner_servicios.php`);
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'No se pudieron cargar los servicios.');
        }
        // SOLUCIÓN: La API devuelve { "servicios": [...] }, por lo que debemos acceder a esa propiedad.
        const data = await response.json();
        const servicios = data.servicios || [];

        let serviciosHtml = '';
        if (servicios && servicios.length > 0) {
            const serviciosRows = servicios.map((servicio, index) => {
                const precioFormateado = servicio.precio ? `$${parseFloat(servicio.precio).toFixed(2)}` : 'N/A';
                return `
                    <tr class="${servicio.activo == 1 ? '' : 'table-secondary text-muted'}">
                        <td>${index + 1}</td>
                        <td>${servicio.nombre_servicio}</td>
                        <td>${servicio.duracion_valor} ${servicio.duracion_unidad}</td>
                        <td>${precioFormateado}</td>
                        <td>
                            <span class="badge ${servicio.activo == 1 ? 'bg-success' : 'bg-danger'}">
                                ${servicio.activo == 1 ? 'Activo' : 'Inactivo'}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-warning btn-edit-servicio" data-servicio-json='${JSON.stringify(servicio)}' ${servicio.activo == 0 ? 'disabled' : ''}>Editar</button>
                            ${servicio.activo == 1
                                ? `<button class="btn btn-sm btn-danger btn-delete-servicio" data-id-servicio="${servicio.id_servicio}">Desactivar</button>`
                                : `<button class="btn btn-sm btn-success btn-activate-servicio" data-id-servicio="${servicio.id_servicio}">Activar</button>`
                            }
                        </td>
                    </tr>
                `;
            }).join('');

            serviciosHtml = `
                <table class="table table-striped table-hover">
                    <thead class="table-dark"><tr><th>#</th><th>Servicio</th><th>Duración</th><th>Precio</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>${serviciosRows}</tbody>
                </table>`;
        } else {
            serviciosHtml = `<div class="alert alert-info">No hay servicios registrados.</div>`;
        }

        dom.appContainer.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Gestión de Servicios</h3>
                <button class="btn btn-primary" id="btn-crear-servicio">Registrar Nuevo Servicio</button>
            </div>
            ${serviciosHtml}
        `;

        document.getElementById('btn-crear-servicio').addEventListener('click', () => openServicioModal(context));
        document.querySelectorAll('.btn-edit-servicio').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const servicioData = JSON.parse(e.currentTarget.dataset.servicioJson);
                openServicioModal(context, servicioData);
            });
        });
        document.querySelectorAll('.btn-delete-servicio').forEach(btn => {
            btn.addEventListener('click', (e) => handleDeleteServicio(context, e.target.dataset.idServicio));
        });
        document.querySelectorAll('.btn-activate-servicio').forEach(btn => {
            btn.addEventListener('click', (e) => handleActivateServicio(context, e.target.dataset.idServicio));
        });

    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

async function handleDeleteServicio(context, id_servicio) {
    const { API_URL, renderView } = context;
    if (!confirm('¿Estás seguro de que quieres desactivar este servicio?')) return;
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
        alert(`Error en la operación: ${error.message}`);
    }
}

async function handleActivateServicio(context, id_servicio) {
    const { API_URL, renderView } = context;
    if (!confirm('¿Estás seguro de que quieres reactivar este servicio?')) return;
    try {
        const response = await fetch(`${API_URL}api_owner_servicio_actualizar.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_servicio: id_servicio, activo: 1 })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);
        alert(data.message || 'Servicio activado con éxito.');
        renderView('servicios');
    } catch (error) {
        alert(`Error en la operación: ${error.message}`);
    }
}

function openServicioModal(context, servicio = null) {
    const { API_URL, renderView } = context;
    const isNew = !servicio;
    const modalTitle = isNew ? 'Registrar Nuevo Servicio' : 'Editar Servicio';
    const duracionUnidades = { 'Minutos': 'Minutos', 'Horas': 'Horas', 'Dias': 'Días' };
    const unidadesOptions = Object.entries(duracionUnidades).map(([key, value]) => `<option value="${key}" ${!isNew && servicio.duracion_unidad === key ? 'selected' : ''}>${value}</option>`).join('');

    const modalHtml = `
        <div class="modal fade" id="servicioModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">${modalTitle}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div id="modal-error-container"></div>
                        <form id="servicio-form">
                            <input type="hidden" id="id_servicio" value="${servicio?.id_servicio || ''}">
                            <div class="mb-3"><label for="nombre_servicio" class="form-label">Nombre del Servicio</label><input type="text" class="form-control" id="nombre_servicio" value="${servicio?.nombre_servicio || ''}" required></div>
                            <div class="mb-3"><label class="form-label">Duración</label><div class="input-group"><input type="number" class="form-control" id="duracion_valor" value="${servicio?.duracion_valor || 30}" required><select class="form-select" id="duracion_unidad">${unidadesOptions}</select></div></div>
                            <div class="mb-3"><label for="precio" class="form-label">Precio (opcional)</label><input type="number" step="0.01" class="form-control" id="precio" value="${servicio?.precio || ''}"></div>
                        </form>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="save-servicio-btn">Guardar</button></div>
                </div>
            </div>
        </div>`;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modalElement = document.getElementById('servicioModal');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    document.getElementById('save-servicio-btn').addEventListener('click', async () => {
        const endpoint = isNew ? `${API_URL}api_owner_servicio_crear.php` : `${API_URL}api_owner_servicio_actualizar.php`;
        const payload = { 
            id_servicio: document.getElementById('id_servicio').value, 
            nombre_servicio: document.getElementById('nombre_servicio').value, 
            duracion_valor: document.getElementById('duracion_valor').value, 
            duracion_unidad: document.getElementById('duracion_unidad').value, 
            precio: document.getElementById('precio').value 
        };
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