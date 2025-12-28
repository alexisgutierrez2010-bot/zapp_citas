// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
// js/owner_modules/clientes.js
export async function renderClientesView(context) {
    const { dom, API_URL, renderView } = context;
    dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando clientes...</span></div></div>`;
    try {
        const response = await fetch(`${API_URL}api_owner_clientes.php`);
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || 'No se pudieron cargar los clientes');
        }
        const data = await response.json();
        const clientes = data.clientes || [];

        const clientesHtml = clientes.length > 0 ? `
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    ${clientes.map(c => `
                        <tr class="${c.activo == 1 ? '' : 'table-secondary text-muted'}">
                            <td>${c.nombre_completo}</td>
                            <td>${c.numero_celular}</td>
                            <td>${c.correo_electronico || '-'}</td>
                            <td>
                                <span class="badge ${c.activo == 1 ? 'bg-success' : 'bg-danger'}">
                                    ${c.activo == 1 ? 'Activo' : 'Inactivo'}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm btn-acciones" data-id="${c.id_cliente}">
                                    Acciones
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        ` : `<div class="alert alert-info">No hay clientes registrados.</div>`;

        dom.appContainer.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Gestión de Clientes</h3>
                <button class="btn btn-primary" id="btn-nuevo-cliente">Registrar Nuevo Cliente</button>
            </div>
            <div class="table-responsive">${clientesHtml}</div>
        `;

        // Añadir listeners para los botones de acción
        document.getElementById('btn-nuevo-cliente').addEventListener('click', () => openClienteModal(context));
        
        document.querySelectorAll('.btn-acciones').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = e.currentTarget.dataset.id;
                const cliente = clientes.find(c => c.id_cliente == id);
                if (cliente) openActionsModal(context, cliente);
            });
        });

    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

/**
 * Maneja el envío de la comunicación seleccionada.
 * @param {object} context - El contexto de la aplicación.
 * @param {object} cliente - El objeto del cliente.
 * @param {string} type - El tipo de comunicación ('email', 'whatsapp', 'sms').
 */
async function handleSendCommunication(context, cliente, type) {
    const { API_URL, state } = context;
    const linkCliente = 'https://appcitas.acticven.com/zapp_citas/spa_client.php';

    if (type === 'email') {
        if (!confirm(`¿Enviar correo de bienvenida a ${cliente.nombre_completo}?`)) return;
        
        try {
            const response = await fetch(`${API_URL}api_owner_comunicacion_cliente.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_cliente: cliente.id_cliente, tipo: 'email' })
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error);
            alert(data.message);
        } catch (error) {
            alert(`Error al enviar el correo: ${error.message}`);
        }

    } else if (type === 'whatsapp' || type === 'sms') {
        const numero = cliente.numero_celular.replace(/[^\d+]/g, '').replace('+', '');

        let message = '';
        if (type === 'whatsapp') {
            message = `¡Hola ${cliente.nombre_completo}! Bienvenido/a a ${state.ownerActual.nombre_negocio}. Puede gestionar sus citas aquí: ${linkCliente}`;
        } else { // sms
            message = `Bienvenido/a a ${state.ownerActual.nombre_negocio}, ${cliente.nombre_completo}. Gestione sus citas en: ${linkCliente}`;
        }

        const encodedMessage = encodeURIComponent(message);
        
        let url = '';
        if (type === 'whatsapp') {
            url = `https://wa.me/${numero}?text=${encodedMessage}`;
        } else { // sms
            url = `sms:${numero}?body=${encodedMessage}`;
        }
        
        window.open(url, '_blank');
    }
}

/**
 * Maneja la desactivación de un cliente.
 * @param {object} context - El contexto de la aplicación.
 * @param {string} id_cliente - El ID del cliente a desactivar.
 */
async function handleDeleteCliente(context, id_cliente) {
    const { API_URL, renderView } = context;
    if (!confirm('¿Estás seguro de que quieres desactivar este cliente? No se podrá usar para nuevas citas.')) {
        return;
    }
    try {
        const response = await fetch(`${API_URL}api_owner_cliente_actualizar.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_cliente: id_cliente, activo: 0 }) 
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Error en la operación');
        
        alert(data.message || 'Cliente desactivado con éxito.');
        renderView('clientes');
    } catch (error) {
        alert(`Error en la operación: ${error.message}`);
    }
}

/**
 * Maneja la activación de un cliente.
 * @param {object} context - El contexto de la aplicación.
 * @param {string} id_cliente - El ID del cliente a activar.
 */
async function handleActivateCliente(context, id_cliente) {
    const { API_URL, renderView } = context;
    if (!confirm('¿Estás seguro de que quieres reactivar este cliente?')) {
        return;
    }
    try {
        const response = await fetch(`${API_URL}api_owner_cliente_actualizar.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_cliente: id_cliente, activo: 1 }) 
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Error en la operación');

        alert(data.message || 'Cliente activado con éxito.');
        renderView('clientes');
    } catch (error) {
        alert(`Error en la operación: ${error.message}`);
    }
}

/**
 * Abre el modal de acciones para un cliente.
 * @param {object} context - El contexto de la aplicación.
 * @param {object} cliente - El objeto del cliente.
 */
function openActionsModal(context, cliente) {
    const modalId = 'actionsModal';
    const existingModal = document.getElementById(modalId);
    if (existingModal) existingModal.remove();

    const modalHtml = `
        <div class="modal fade" id="${modalId}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Acciones: ${cliente.nombre_completo}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body d-grid gap-2">
                        <button class="btn btn-outline-primary btn-lg btn-action-edit">
                            <i class="bi bi-pencil-fill"></i> Editar Datos
                        </button>
                        <hr>
                        <h6 class="text-muted mb-2">Comunicación</h6>
                        <button class="btn btn-outline-info btn-lg btn-action-email">
                            <i class="bi bi-envelope-fill"></i> Enviar Email Bienvenida
                        </button>
                        <button class="btn btn-outline-success btn-lg btn-action-whatsapp">
                            <i class="bi bi-whatsapp"></i> Enviar WhatsApp
                        </button>
                        <button class="btn btn-outline-secondary btn-lg btn-action-sms">
                            <i class="bi bi-chat-dots-fill"></i> Enviar SMS
                        </button>
                        <hr>
                        ${cliente.activo == 1 
                            ? `<button class="btn btn-outline-danger btn-lg btn-action-deactivate"><i class="bi bi-person-x-fill"></i> Desactivar Cliente</button>`
                            : `<button class="btn btn-outline-success btn-lg btn-action-activate"><i class="bi bi-person-check-fill"></i> Activar Cliente</button>`
                        }
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modalElement = document.getElementById(modalId);
    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    modalElement.querySelector('.btn-action-edit').addEventListener('click', () => {
        modal.hide();
        openClienteModal(context, cliente.id_cliente);
    });

    modalElement.querySelector('.btn-action-email').addEventListener('click', () => {
        modal.hide();
        handleSendCommunication(context, cliente, 'email');
    });

    modalElement.querySelector('.btn-action-whatsapp').addEventListener('click', () => {
        modal.hide();
        handleSendCommunication(context, cliente, 'whatsapp');
    });

    modalElement.querySelector('.btn-action-sms').addEventListener('click', () => {
        modal.hide();
        handleSendCommunication(context, cliente, 'sms');
    });

    const btnDeactivate = modalElement.querySelector('.btn-action-deactivate');
    if (btnDeactivate) {
        btnDeactivate.addEventListener('click', () => {
            modal.hide();
            handleDeleteCliente(context, cliente.id_cliente);
        });
    }

    const btnActivate = modalElement.querySelector('.btn-action-activate');
    if (btnActivate) {
        btnActivate.addEventListener('click', () => {
            modal.hide();
            handleActivateCliente(context, cliente.id_cliente);
        });
    }

    modalElement.addEventListener('hidden.bs.modal', () => modalElement.remove());
}

/**
 * Abre el modal para crear o editar un cliente.
 * @param {object} context - El contexto de la aplicación.
 * @param {string|null} id_cliente - El ID del cliente a editar, o null para crear uno nuevo.
 */
async function openClienteModal(context, id_cliente = null) {
    const { API_URL, renderView } = context;
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
    if (cliente.numero_celular && cliente.numero_celular.includes(' ')) {
        const parts = cliente.numero_celular.split(' ');
        if (parts.length > 1 && parts[0].startsWith('+')) {
            currentCountryCode = parts[0];
            currentPhoneNumber = parts.slice(1).join(' ');
        }
    }
    if (!currentCountryCode && paises.length > 0) {
        const venezuela = paises.find(p => p.id_pais == 1);
        currentCountryCode = venezuela ? venezuela.codigo_telefono : paises[0].codigo_telefono;
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
                            <div class="mb-3"><label for="correo_electronico" class="form-label">Email</label><input type="email" class="form-control" id="correo_electronico" value="${cliente.correo_electronico || ''}"></div>
                            <div class="mb-3"><label for="direccion1" class="form-label">Dirección</label><input type="text" class="form-control" id="direccion1" value="${cliente.direccion1 || ''}"></div>
                            <div class="mb-3"><label for="direccion2" class="form-label">Dirección 2 (Opcional)</label><input type="text" class="form-control" id="direccion2" value="${cliente.direccion2 || ''}"></div>
                            <div class="row">
                                <div class="col-md-8 mb-3"><label for="ciudad" class="form-label">Ciudad</label><input type="text" class="form-control" id="ciudad" value="${cliente.ciudad || ''}"></div>
                                <div class="col-md-4 mb-3"><label for="zip_code" class="form-label">Código Postal</label><input type="text" class="form-control" id="zip_code" value="${cliente.zip_code || ''}"></div>
                            </div>
                            <div class="row"><div class="col-md-6 mb-3"><label for="id_pais" class="form-label">País</label><select class="form-select" id="id_pais">${paisesOptions}</select></div><div class="col-md-6 mb-3"><label for="id_estado" class="form-label">Estado</label><select class="form-select" id="id_estado" ${cliente.id_pais ? '' : 'disabled'}>${estadosOptions}</select></div></div>
                            <div class="mb-3"><label for="notas_adicionales" class="form-label">Notas Adicionales (Solo visible para el propietario)</label><textarea class="form-control" id="notas_adicionales" rows="3">${cliente.notas_adicionales || ''}</textarea></div>
                            <hr>
                            <div class="mb-3">
                                <label class="form-label">Preferencias de Comunicación</label>
                                <div class="form-check"><input class="form-check-input" type="checkbox" id="in_email" ${cliente.in_email == 1 ? 'checked' : ''}><label class="form-check-label" for="in_email">Recibir notificaciones por Email</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" id="in_sms" ${cliente.in_sms == 1 ? 'checked' : ''}><label class="form-check-label" for="in_sms">Recibir notificaciones por SMS</label></div>
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
        estadoSelectModal.innerHTML = '<option value="">Seleccione un estado...</option>' + estadosData.map(estado => `<option value="${estado.id_estado}" ${estado.id_estado == idEstadoSeleccionado ? 'selected' : ''}>${estado.nombre_estado}</option>`).join('');
        estadoSelectModal.disabled = false;
    }

    paisSelectModal.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        countryCodeClienteSelect.value = selectedOption.dataset.codigoTelefono;
        cargarEstadosModal(this.value);
    });

    document.getElementById('save-cliente-btn').addEventListener('click', async () => {
        const isNew = !document.getElementById('cliente_id').value;
        const endpoint = isNew ? `${API_URL}api_owner_cliente_crear.php` : `${API_URL}api_owner_cliente_actualizar.php`;
        
        const payload = {
            id_cliente: document.getElementById('cliente_id').value,
            nombre_completo: document.getElementById('nombre_completo').value,
            numero_celular: `${document.getElementById('country_code_cliente').value} ${document.getElementById('numero_celular').value.trim()}`,
            correo_electronico: document.getElementById('correo_electronico').value,
            direccion1: document.getElementById('direccion1').value,
            direccion2: document.getElementById('direccion2').value,
            ciudad: document.getElementById('ciudad').value,
            zip_code: document.getElementById('zip_code').value,
            id_pais: document.getElementById('id_pais').value,
            id_estado: document.getElementById('id_estado').value,
            notas_adicionales: document.getElementById('notas_adicionales').value,
            in_sms: document.getElementById('in_sms').checked ? 1 : 0,
            in_email: document.getElementById('in_email').checked ? 1 : 0,
            in_whatsapp: document.getElementById('in_whatsapp').checked ? 1 : 0,
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
            alert(saveData.message);
        } catch (saveError) {
            document.getElementById('modal-error-container').innerHTML = `<div class="alert alert-danger">${saveError.message}</div>`;
        }
    });

    modalElement.addEventListener('hidden.bs.modal', () => modalElement.remove());
}
