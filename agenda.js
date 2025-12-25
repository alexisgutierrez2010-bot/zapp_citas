// js/owner_modules/agenda.js

/**
 * Renderiza la vista "Mi Agenda".
 * @param {object} context - El objeto de contexto de la aplicación.
 */
export async function renderAgendaView(context) {
    const { dom, state, API_URL } = context;
    
    // Si no hay fecha en el estado, usar hoy.
    if (!state.currentDate) {
        state.currentDate = new Date().toISOString().split('T')[0];
    }

    dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando agenda...</span></div></div>`;

    try {
        const response = await fetch(`${API_URL}api_owner_horario_disponible.php?fecha=${state.currentDate}`);
        if (!response.ok) throw new Error('No se pudo cargar la agenda.');
        
        const data = await response.json();
        const slots = data.slots || [];
        const fechaMostrada = new Date(data.fecha + 'T00:00:00').toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

        let agendaHtml = '';
        if (slots.length > 0) {
            agendaHtml = slots.map(slot => {
                if (slot.status === 'booked') {
                    return `
                        <div class="list-group-item list-group-item-action flex-column align-items-start">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">${slot.start_time} - ${slot.end_time}</h5>
                                <small><span class="badge bg-primary">${slot.cita.estado_cita}</span></small>
                            </div>
                            <p class="mb-1"><strong>Cliente:</strong> ${slot.cita.nombre_cliente}</p>
                            <small><strong>Servicio:</strong> ${slot.cita.nombre_servicio || 'Reunión'}</small>
                        </div>`;
                } else {
                    return `
                        <div class="list-group-item list-group-item-light">
                            <div class="d-flex w-100 justify-content-between">
                                <p class="mb-1 text-muted">${slot.start_time} - ${slot.end_time}</p>
                                <small class="text-success">Disponible</small>
                            </div>
                        </div>`;
                }
            }).join('');
        } else {
            agendaHtml = '<div class="alert alert-info">No hay citas ni horarios disponibles para esta fecha.</div>';
        }

        dom.appContainer.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Agenda para ${fechaMostrada}</h3>
                <button class="btn btn-success" id="btn-agendar-cita">Agendar Nueva Cita</button>
            </div>
            <div class="d-flex justify-content-center align-items-center mb-3 gap-2">
                <button class="btn btn-outline-secondary" id="btn-dia-anterior">« Día Anterior</button>
                <input type="date" class="form-control" style="max-width: 200px;" id="selector-fecha" value="${state.currentDate}">
                <button class="btn btn-outline-secondary" id="btn-dia-siguiente">Día Siguiente »</button>
            </div>
            <div class="list-group">${agendaHtml}</div>
        `;

        // Listeners
        document.getElementById('btn-agendar-cita').addEventListener('click', () => openAgendarCitaModal(context));
        
        const changeDate = (offset) => {
            const currentDate = new Date(state.currentDate + 'T00:00:00');
            currentDate.setDate(currentDate.getDate() + offset);
            state.currentDate = currentDate.toISOString().split('T')[0];
            context.renderView('agenda');
        };

        document.getElementById('btn-dia-anterior').addEventListener('click', () => changeDate(-1));
        document.getElementById('btn-dia-siguiente').addEventListener('click', () => changeDate(1));
        document.getElementById('selector-fecha').addEventListener('change', (e) => {
            state.currentDate = e.target.value;
            context.renderView('agenda');
        });

    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

/**
 * Abre el modal para agendar una nueva cita (Servicio o Reunión).
 * @param {object} context - El contexto de la aplicación.
 */
async function openAgendarCitaModal(context) {
    const { API_URL, state, renderView } = context;
    let clientes = [];
    let servicios = [];

    try {
        const [clientesRes, serviciosRes] = await Promise.all([
            fetch(`${API_URL}api_owner_clientes.php`),
            fetch(`${API_URL}api_owner_servicios.php`)
        ]);
        clientes = (await clientesRes.json()).clientes.filter(c => c.activo == 1);
        servicios = (await serviciosRes.json()).servicios.filter(s => s.activo == 1);
    } catch (error) {
        alert('Error al cargar datos para agendar: ' + error.message);
        return;
    }

    const clientesOptions = clientes.map(c => `<option value="${c.id_cliente}">${c.nombre_completo}</option>`).join('');
    const serviciosOptions = servicios.map(s => `<option value="${s.id_servicio}">${s.nombre_servicio}</option>`).join('');

    const modalHtml = `
        <div class="modal fade" id="agendarCitaModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Agendar Nueva Cita</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="modal-error-container-cita"></div>
                        <form id="agendar-cita-form">
                            <div class="mb-3">
                                <label for="tipo_cita" class="form-label">Tipo de Cita</label>
                                <select class="form-select" id="tipo_cita">
                                    <option value="Servicio" selected>Servicio</option>
                                    <option value="Reunion">Reunión</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="id_cliente" class="form-label">Cliente</label>
                                <select class="form-select" id="id_cliente" required>${clientesOptions}</select>
                            </div>
                            
                            <div id="campos-servicio">
                                <div class="mb-3">
                                    <label for="id_servicio" class="form-label">Servicio</label>
                                    <select class="form-select" id="id_servicio">${serviciosOptions}</select>
                                </div>
                            </div>

                            <div id="campos-reunion" class="d-none">
                                <div class="mb-3">
                                    <label for="asunto" class="form-label">Asunto de la Reunión</label>
                                    <input type="text" class="form-control" id="asunto">
                                </div>
                                <!-- Aquí iría la gestión de invitados si se implementa en el futuro -->
                            </div>

                            <div class="mb-3">
                                <label for="fecha_hora_inicio" class="form-label">Fecha y Hora de Inicio</label>
                                <input type="datetime-local" class="form-control" id="fecha_hora_inicio" required value="${state.currentDate}T09:00">
                            </div>

                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Notas Adicionales</label>
                                <textarea class="form-control" id="descripcion" rows="2"></textarea>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="notificar_cliente" checked>
                                <label class="form-check-label" for="notificar_cliente">Notificar al cliente por Email</label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="save-cita-btn">Guardar Cita</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modalElement = document.getElementById('agendarCitaModal');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    const tipoCitaSelect = document.getElementById('tipo_cita');
    const camposServicio = document.getElementById('campos-servicio');
    const camposReunion = document.getElementById('campos-reunion');

    tipoCitaSelect.addEventListener('change', () => {
        if (tipoCitaSelect.value === 'Reunion') {
            camposServicio.classList.add('d-none');
            camposReunion.classList.remove('d-none');
            document.getElementById('id_servicio').required = false;
            document.getElementById('asunto').required = true;
        } else {
            camposServicio.classList.remove('d-none');
            camposReunion.classList.add('d-none');
            document.getElementById('id_servicio').required = true;
            document.getElementById('asunto').required = false;
        }
    });

    document.getElementById('save-cita-btn').addEventListener('click', async () => {
        const form = document.getElementById('agendar-cita-form');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const payload = {
            tipo_cita: document.getElementById('tipo_cita').value,
            id_cliente: document.getElementById('id_cliente').value,
            id_servicio: document.getElementById('id_servicio').value,
            asunto: document.getElementById('asunto').value,
            fecha_hora_inicio: document.getElementById('fecha_hora_inicio').value,
            descripcion: document.getElementById('descripcion').value,
            notificar_cliente: document.getElementById('notificar_cliente').checked,
            invitados: [] // Placeholder para futuros invitados
        };

        const saveBtn = document.getElementById('save-cita-btn');
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Guardando...`;

        try {
            const response = await fetch(`${API_URL}api_owner_cita_crear.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Error al guardar la cita.');
            }

            modal.hide();
            alert(data.message);
            renderView('agenda');

            // **NUEVA LÓGICA DE NOTIFICACIÓN**
            if (data.notification_payload) {
                handlePostCreationNotifications(data.notification_payload);
            }

        } catch (error) {
            document.getElementById('modal-error-container-cita').innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = `Guardar Cita`;
        }
    });

    modalElement.addEventListener('hidden.bs.modal', () => modalElement.remove());
}

/**
 * Gestiona el envío de notificaciones post-creación de cita (WhatsApp y SMS).
 * @param {object} payload - Los datos para la notificación.
 */
function handlePostCreationNotifications(payload) {
    const { telefono_cliente, nombre_cliente, nombre_negocio, asunto_evento, fecha_hora_inicio } = payload;

    if (!telefono_cliente) {
        console.warn("No se puede enviar notificación SMS/WhatsApp: el cliente no tiene teléfono.");
        return;
    }

    const linkCliente = 'https://appcitas.acticven.com/zapp_citas/spa_client.php';
    const numeroLimpio = telefono_cliente.replace(/[^\d+]/g, '').replace('+', '');

    // Mensaje para WhatsApp
    const whatsappMessage = `¡Hola ${nombre_cliente}! Te confirmamos tu cita en ${nombre_negocio} para "${asunto_evento}" el ${fecha_hora_inicio}. Puedes gestionar tu cita aquí: ${linkCliente}`;
    const whatsappUrl = `https://wa.me/${numeroLimpio}?text=${encodeURIComponent(whatsappMessage)}`;

    // Mensaje para SMS
    const smsMessage = `Cita confirmada en ${nombre_negocio} para ${asunto_evento} el ${fecha_hora_inicio}. Gestiona tu cita: ${linkCliente}`;
    const smsUrl = `sms:${numeroLimpio}?body=${encodeURIComponent(smsMessage)}`;

    // Preguntar al propietario si desea enviar las notificaciones
    // Usamos un pequeño timeout para que no se solape con la alerta de "Cita creada".
    setTimeout(() => {
        if (confirm(`Cita creada con éxito.\n\n¿Deseas enviar una notificación por WhatsApp al cliente ${nombre_cliente}?`)) {
            window.open(whatsappUrl, '_blank');
        }

        setTimeout(() => {
            if (confirm(`¿Deseas enviar también una notificación por SMS?`)) {
                window.open(smsUrl, '_blank');
            }
        }, 500); // Pequeña pausa entre una pregunta y otra

    }, 500);
}

/**
 * Abre el modal de acciones para una cita.
 * @param {object} context - El contexto de la aplicación.
 * @param {object} cita - El objeto de la cita.
 */
async function openCitaActionsModal(context, cita) {
    const { API_URL, renderView } = context;
    const modalId = 'citaActionsModal';
    // ... (Implementación futura para editar, cancelar, etc. desde la agenda)
    alert(`Funcionalidad de acciones para la cita ID ${cita.id_cita} pendiente de implementar.`);
}

/**
 * Abre el modal de acciones para un slot disponible.
 * @param {object} context - El contexto de la aplicación.
 * @param {string} startTime - La hora de inicio del slot.
 */
function openSlotActionsModal(context, startTime) {
    const modalId = 'slotActionsModal';
    // ... (Implementación futura para bloquear horario, etc.)
    alert(`Funcionalidad de acciones para el slot de las ${startTime} pendiente de implementar.`);
}

/**
 * Maneja la actualización del estado de una cita.
 * @param {object} context - El contexto de la aplicación.
 * @param {number} id_cita - El ID de la cita.
 * @param {string} nuevoEstado - El nuevo estado para la cita.
 */
async function handleUpdateCitaStatus(context, id_cita, nuevoEstado) {
    const { API_URL, renderView } = context;
    if (!confirm(`¿Estás seguro de que quieres cambiar el estado de esta cita a "${nuevoEstado}"?`)) {
        return;
    }

    try {
        const response = await fetch(`${API_URL}api_owner_cita_actualizar_estado.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_cita, estado: nuevoEstado })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);
        
        alert(data.message);
        renderView('agenda');

        // Si se cancela, preguntar si se quiere notificar
        if (nuevoEstado === 'Cancelada' && data.notification_payload) {
            if (confirm('¿Deseas notificar al cliente por correo sobre la cancelación?')) {
                // Aquí iría la lógica para llamar a un endpoint que envíe el correo de cancelación
            }
        }

    } catch (error) {
        alert(`Error al actualizar estado: ${error.message}`);
    }
}

/**
 * Maneja la eliminación física de una cita.
 * @param {object} context - El contexto de la aplicación.
 * @param {number} id_cita - El ID de la cita a eliminar.
 */
async function handleDeleteCita(context, id_cita) {
    const { API_URL, renderView } = context;
    if (!confirm('🔥 ¡ADVERTENCIA! ¿Estás seguro de que quieres ELIMINAR esta cita permanentemente? Esta acción no se puede deshacer.')) {
        return;
    }

    try {
        const response = await fetch(`${API_URL}api_owner_cita_eliminar.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_cita })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);
        
        alert(data.message);
        renderView('agenda');
    } catch (error) {
        alert(`Error al eliminar la cita: ${error.message}`);
    }
}