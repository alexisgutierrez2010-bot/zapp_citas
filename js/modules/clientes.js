// js/modules/clientes.js

/**
 * Renderiza la vista "Mis Clientes".
 * @param {object} context - El objeto de contexto de la aplicación.
 */
export async function renderClientesView(context) {
    const { dom, API_URL, renderView } = context;
    dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando clientes...</span></div></div>`;

    try {
        const response = await fetch(`${API_URL}api_owner_clientes.php`);
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'No se pudieron cargar los clientes.');
        }
        // SOLUCIÓN: La API devuelve { "clientes": [...] }, por lo que debemos acceder a esa propiedad.
        const data = await response.json();
        const clientes = data.clientes || [];

        let clientesHtml = '';
        if (clientes.length > 0) {
            const clientesRows = clientes.map((cliente, index) => `
                <tr class="${cliente.activo == 1 ? '' : 'table-secondary text-muted'}">
                    <td>${index + 1}</td>
                    <td>${cliente.nombre_completo}</td>
                    <td>
                        ${
                            // CORRECCIÓN: Asegurar que el número se muestre con el formato estándar.
                            // Si el número ya tiene un '+' al principio, se muestra tal cual. Si no, se asume que es un número sin código y se muestra.
                            (cliente.numero_celular && cliente.numero_celular.includes(' ')) ? cliente.numero_celular.replace(' ', '') : cliente.numero_celular
                        }
                    </td>
                    <td>${cliente.correo_electronico}</td>
                    <td>
                        <span class="badge ${cliente.activo == 1 ? 'bg-success' : 'bg-danger'}">
                            ${cliente.activo == 1 ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-warning btn-edit-cliente" data-id-cliente="${cliente.id_cliente}" ${cliente.activo == 0 ? 'disabled' : ''}>Editar</button>
                        ${cliente.activo == 1 
                            ? `<button class="btn btn-sm btn-danger btn-delete-cliente" data-id-cliente="${cliente.id_cliente}">Desactivar</button>`
                            : `<button class="btn btn-sm btn-success btn-activate-cliente" data-id-cliente="${cliente.id_cliente}">Activar</button>`
                        }
                    </td>
                </tr>
            `).join('');

            clientesHtml = `
                <table class="table table-striped table-hover">
                    <thead class="table-dark"><tr><th>#</th><th>Nombre</th><th>Celular</th><th>Email</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>${clientesRows}</tbody>
                </table>`;
        } else {
            clientesHtml = `<div class="alert alert-info">No hay clientes registrados.</div>`;
        }

        dom.appContainer.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Gestión de Clientes</h3>
                <button class="btn btn-primary" id="btn-crear-cliente">Registrar Nuevo Cliente</button>
            </div>
            ${clientesHtml}
        `;

        // Añadir listeners para los botones de acción
        document.getElementById('btn-crear-cliente').addEventListener('click', () => openClienteModal(context));
        document.querySelectorAll('.btn-edit-cliente').forEach(btn => {
            btn.addEventListener('click', (e) => openClienteModal(context, e.target.dataset.idCliente));
        });
        document.querySelectorAll('.btn-delete-cliente').forEach(btn => {
            btn.addEventListener('click', (e) => handleDeleteCliente(context, e.target.dataset.idCliente));
        });
        document.querySelectorAll('.btn-activate-cliente').forEach(btn => {
            btn.addEventListener('click', (e) => handleActivateCliente(context, e.target.dataset.idCliente));
        });

    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

async function handleDeleteCliente(context, id_cliente) {
    const { API_URL, renderView } = context; // renderView ya está en el contexto
    if (!confirm('¿Estás seguro de que quieres desactivar este cliente? No se podrá usar para nuevas citas.')) {
        return;
    }
    try {
        const response = await fetch(`${API_URL}api_owner_cliente_eliminar.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_cliente: id_cliente }) 
        });
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error || 'Error en la operación');
        }
        alert(data.message);
        renderView('clientes'); // Recargar la lista de clientes
    } catch (error) {
        alert(`Error en la operación: ${error.message}`);
    }
}

async function handleActivateCliente(context, id_cliente) {
    const { API_URL, renderView } = context;
    if (!confirm('¿Estás seguro de que quieres reactivar este cliente?')) {
        return;
    }
    try {
        // Usamos la API de actualizar para cambiar el estado a activo (1)
        const response = await fetch(`${API_URL}api_owner_cliente_actualizar.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_cliente: id_cliente, activo: 1 }) 
        });
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error || 'Error en la operación');
        }
        alert(data.message || 'Cliente activado con éxito.');
        renderView('clientes');
    } catch (error) {
        alert(`Error en la operación: ${error.message}`);
    }
}

async function openClienteModal(context, id_cliente = null) {
    const { API_URL, renderView } = context; // renderView ya está en el contexto
    let cliente = {};
    let modalTitle = 'Registrar Nuevo Cliente';
    let paises = [];
    let estados = [];

    try {
        const paisesResponse = await fetch(`${API_URL}api_paises.php`);
        paises = await paisesResponse.json();

        if (id_cliente) {
            modalTitle = 'Editar Cliente';
            const clienteResponse = await fetch(`${API_URL}api_owner_cliente_detalle.php?id_cliente=${id_cliente}`);
            if (!clienteResponse.ok) throw new Error('No se pudieron cargar los detalles del cliente.');
            cliente = await clienteResponse.json();

            if (cliente.id_pais) {
                const estadosResponse = await fetch(`${API_URL}api_estados.php?id_pais=${cliente.id_pais}`);
                estados = await estadosResponse.json();
            }
        }
    } catch (error) {
        alert(`Error en la operación: ${error.message}`);
        return;
    }

    const paisesOptions = paises.map(p => `<option value="${p.id_pais}" data-codigo-telefono="${p.codigo_telefono}" ${cliente.id_pais == p.id_pais ? 'selected' : ''}>${p.nombre_pais}</option>`).join('');
    const estadosOptions = estados.map(e => `<option value="${e.id_estado}" ${cliente.id_estado == e.id_estado ? 'selected' : ''}>${e.nombre_estado}</option>`).join('');

    let currentCountryCode = '';
    let currentPhoneNumber = cliente.numero_celular || '';
    if (cliente.numero_celular) {
        const parts = cliente.numero_celular.split(' ');
        if (parts.length > 1 && parts[0].startsWith('+')) {
            currentCountryCode = parts[0];
            currentPhoneNumber = parts.slice(1).join(' ');
        }
    }
    if (!currentCountryCode && paises.length > 0) {
        currentCountryCode = paises[0].codigo_telefono;
    }

    const modalHtml = `
        <div class="modal fade" id="clienteModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">${modalTitle}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div id="modal-error-container"></div>
                        <form id="cliente-form">
                            <input type="hidden" id="cliente_id" value="${cliente.id_cliente || ''}">
                            <div class="mb-3"><label for="nombre_completo" class="form-label">Nombre Completo</label><input type="text" class="form-control" id="nombre_completo" value="${cliente.nombre_completo || ''}" required></div>
                            <div class="mb-3">
                                <label for="numero_celular" class="form-label">Celular</label>
                                <div class="input-group">
                                    <select class="form-select" id="country_code_cliente" style="max-width: 120px;">${paises.map(p => `<option value="${p.codigo_telefono}" ${currentCountryCode === p.codigo_telefono ? 'selected' : ''}>${p.codigo_telefono}</option>`).join('')}</select>
                                    <input type="tel" class="form-control" id="numero_celular" value="${currentPhoneNumber}" placeholder="Ej: 4121234567" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="correo_electronico" class="form-label">Email</label>
                                <input type="email" class="form-control" id="correo_electronico" value="${cliente.correo_electronico || ''}" required>
                            </div>
                            <div class="mb-3">
                                <label for="direccion1" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion1" value="${cliente.direccion1 || ''}">
                            </div>
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="ciudad" class="form-label">Ciudad</label>
                                    <input type="text" class="form-control" id="ciudad" value="${cliente.ciudad || ''}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="zip_code" class="form-label">Código Postal</label>
                                    <input type="text" class="form-control" id="zip_code" value="${cliente.zip_code || ''}">
                                </div>
                            </div>
                            <div class="row"><div class="col-md-6 mb-3"><label for="id_pais" class="form-label">País</label><select class="form-select" id="id_pais">${paisesOptions}</select></div><div class="col-md-6 mb-3"><label for="id_estado" class="form-label">Estado</label><select class="form-select" id="id_estado" ${cliente.id_pais ? '' : 'disabled'}>${estadosOptions}</select></div></div>
                            <div class="mb-3"><label for="notas_adicionales" class="form-label">Notas Adicionales</label><textarea class="form-control" id="notas_adicionales" rows="3">${cliente.notas_adicionales || ''}</textarea></div>
                            <hr>
                            <div class="mb-3">
                                <label class="form-label">Preferencias de Comunicación</label>
                                <div class="form-check"><input class="form-check-input" type="checkbox" id="in_sms" ${cliente.in_sms == 1 ? 'checked' : ''}><label class="form-check-label" for="in_sms">Recibir notificaciones por SMS</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" id="in_email" ${cliente.in_email == 1 ? 'checked' : ''}><label class="form-check-label" for="in_email">Recibir notificaciones por Email</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" id="in_whatsapp" ${cliente.in_whatsapp == 1 ? 'checked' : ''}><label class="form-check-label" for="in_whatsapp">Recibir notificaciones por WhatsApp</label></div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="save-cliente-btn">Guardar Cliente</button></div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modalElement = document.getElementById('clienteModal');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    const paisSelectModal = document.getElementById('id_pais');
    const estadoSelectModal = document.getElementById('id_estado');
    const countryCodeClienteSelect = document.getElementById('country_code_cliente');

    async function cargarEstadosModal(idPais, idEstadoSeleccionado = null) {
        if (!idPais) {
            estadoSelectModal.innerHTML = `<option value="">Seleccione un país...</option>`;
            estadoSelectModal.disabled = true;
            return;
        }
        estadoSelectModal.innerHTML = `<option value="">Cargando...</option>`;
        const response = await fetch(`${API_URL}api_estados.php?id_pais=${idPais}`);
        const estadosData = await response.json();
        estadoSelectModal.innerHTML = estadosData.map(estado => `<option value="${estado.id_estado}" ${estado.id_estado == idEstadoSeleccionado ? 'selected' : ''}>${estado.nombre_estado}</option>`).join('');
        estadoSelectModal.disabled = false;
    }

    paisSelectModal.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        countryCodeClienteSelect.value = selectedOption.dataset.codigoTelefono;
        cargarEstadosModal(this.value);
    });

    if (cliente.id_pais) {
        cargarEstadosModal(cliente.id_pais, cliente.id_estado);
    } else if (paises.length > 0) {
        cargarEstadosModal(paises[0].id_pais);
    }

    document.getElementById('save-cliente-btn').addEventListener('click', async () => {
        const isNew = !document.getElementById('cliente_id').value;
        const endpoint = isNew ? `${API_URL}api_owner_cliente_crear.php` : `${API_URL}api_owner_cliente_actualizar.php`;
        
        const payload = {
            id_cliente: document.getElementById('cliente_id').value,
            nombre_completo: document.getElementById('nombre_completo').value,
            numero_celular: `${document.getElementById('country_code_cliente').value} ${document.getElementById('numero_celular').value.trim()}`,
            correo_electronico: document.getElementById('correo_electronico').value,
            direccion1: document.getElementById('direccion1').value,
            ciudad: document.getElementById('ciudad').value,
            zip_code: document.getElementById('zip_code').value,
            id_pais: document.getElementById('id_pais').value,
            id_estado: document.getElementById('id_estado').value,
            notas_adicionales: document.getElementById('notas_adicionales').value,
            in_sms: document.getElementById('in_sms').checked ? 1 : 0,
            in_email: document.getElementById('in_email').checked ? 1 : 0,
            in_whatsapp: document.getElementById('in_whatsapp').checked ? 1 : 0,
            activo: 1
        };

        try {
            const saveResponse = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const saveData = await saveResponse.json();
            if (!saveResponse.ok) throw new Error(saveData.error || 'Hubo un problema al guardar.');
            
            modal.hide();
            renderView('clientes');
            alert(saveData.message); // Notificar al usuario del éxito.
        } catch (saveError) {
            document.getElementById('modal-error-container').innerHTML = `<div class="alert alert-danger">${saveError.message}</div>`;
        }
    });

    modalElement.addEventListener('hidden.bs.modal', () => modalElement.remove());
}