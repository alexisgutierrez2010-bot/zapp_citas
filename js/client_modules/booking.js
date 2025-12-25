// c:/xampp/htdocs/zapp_citas/js/client_modules/booking.js

/**
 * Inicia el flujo de agendamiento de citas, mostrando primero la lista de servicios.
 * @param {object} context - El contexto global de la aplicación.
 */
export async function renderBookingView(context) {
    context.dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div></div>`;

    try {
        const response = await fetch(`${context.API_URL}api_servicios_publicos.php?id_negocio=${context.state.clienteActual.id_negocio}`);
        if (!response.ok) throw new Error('Error al cargar los servicios.');
        const servicios = await response.json();

        if (servicios.length === 0) {
            context.dom.appContainer.innerHTML = `<div class="alert alert-warning">No hay servicios disponibles para agendar en este momento.</div>`;
            return;
        }

        const serviciosHtml = servicios.map(s => {
            const imgHtml = s.foto_servicio 
                ? `<img src="${s.foto_servicio}" class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">`
                : `<div class="rounded me-3 bg-light d-flex align-items-center justify-content-center text-secondary" style="width: 60px; height: 60px;"><i class="bi bi-scissors"></i></div>`;

            return `
            <a href="#" class="list-group-item list-group-item-action btn-seleccionar-servicio" data-id-servicio="${s.id_servicio}" data-nombre-servicio="${s.nombre_servicio}">
                <div class="d-flex align-items-center">
                    ${imgHtml}
                    <div class="flex-grow-1">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">${s.nombre_servicio}</h5>
                            <small>Duración: ${s.duracion_valor} ${s.duracion_unidad}</small>
                        </div>
                    </div>
                </div>
                <p class="mb-1">Precio: ${s.precio ? `$${parseFloat(s.precio).toFixed(2)}` : 'Consultar'}</p>
            </a>
        `}).join('');

        context.dom.appContainer.innerHTML = `
            <h3>Agendar Nueva Cita: <span class="text-muted fw-normal fs-5">¿Qué servicio quieres?</span></h3>
            <div class="card">
                <div class="card-header">Paso 1: Elige un servicio de la lista</div>
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
        context.dom.appContainer.innerHTML = `<div class="alert alert-danger">Error al cargar la página de agendamiento: ${error.message}</div>`;
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
        <h3>Agendar: <span class="text-muted fw-normal fs-5">¿Cuándo lo quieres?</span></h3>
        <div class="card">
            <div class="card-header">Paso 2: Elige fecha y hora para "${nombreServicio}"</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label for="fecha-cita" class="form-label">Selecciona una fecha</label>
                        <input type="date" id="fecha-cita" class="form-control" min="${hoy}">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Horarios disponibles</label>
                        <div id="slots-container" class="p-3 bg-light rounded" style="min-height: 100px;">
                            <p class="text-muted text-center">Selecciona una fecha para ver los horarios.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-secondary" data-view="booking">« Volver a Servicios</button>
            </div>
        </div>
    `;

    const fechaInput = document.getElementById('fecha-cita');
    const slotsContainer = document.getElementById('slots-container');

    fechaInput.addEventListener('change', async () => {
        const fechaSeleccionada = fechaInput.value;
        if (!fechaSeleccionada) return;

        slotsContainer.innerHTML = `<div class="text-center"><div class="spinner-border spinner-border-sm"></div> Buscando horarios...</div>`;

        try {
            // SOLUCIÓN ANTI-CACHÉ: Añadimos un timestamp para asegurar que la petición a la API sea siempre nueva.
            const cacheBuster = `&_=${new Date().getTime()}`;
            const response = await fetch(`${context.API_URL}api_cliente_horario_disponible.php?id_negocio=${context.state.clienteActual.id_negocio}&id_servicio=${idServicio}&fecha=${fechaSeleccionada}${cacheBuster}`);
            if (!response.ok) throw new Error('Error al cargar los horarios.');
            const slots = await response.json();

            if (slots.length === 0) {
                slotsContainer.innerHTML = `<p class="text-muted text-center">No hay horarios disponibles para esta fecha.</p>`;
            } else {
                const slotsHtml = slots.map(slot => `<button class="btn btn-outline-primary m-1 btn-seleccionar-slot" data-fecha-hora="${fechaSeleccionada} ${slot}">${slot}</button>`).join('');
                slotsContainer.innerHTML = `<div class="d-flex flex-wrap">${slotsHtml}</div>`;

                document.querySelectorAll('.btn-seleccionar-slot').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const fechaHora = e.target.dataset.fechaHora;
                        renderBookingConfirmationView(context, idServicio, fechaHora, nombreServicio);
                    });
                });
            }
        } catch (error) {
            // CENTINELA FRONTEND: Mensaje de error único para confirmar que el JS está actualizado.
            slotsContainer.innerHTML = `<p class="text-danger text-center"><b>[Centinela JS v4]</b> Fallo al buscar horarios. El JS está actualizado. Revisa la consola (F12) para ver el error del servidor.</p>`;
            console.error("Error al llamar a la API de horarios:", error);
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
function renderBookingConfirmationView(context, idServicio, fechaHora, nombreServicio) {
    const fechaObj = new Date(fechaHora);
    const fechaFormateada = fechaObj.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' });
    const horaFormateada = fechaObj.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });

    context.dom.appContainer.innerHTML = `
        <h3>Confirmar Cita</h3>
        <div class="card">
            <div class="card-header">Paso 3: Revisa y confirma los detalles</div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Servicio:</strong> ${nombreServicio}</li>
                    <li class="list-group-item"><strong>Fecha:</strong> ${fechaFormateada}</li>
                    <li class="list-group-item"><strong>Hora:</strong> ${horaFormateada}</li>
                </ul>
                <div class="mt-3">
                    <label for="cita-notas" class="form-label">Notas adicionales (opcional)</label>
                    <textarea id="cita-notas" class="form-control" rows="3" placeholder="Ej: Prefiero que me contacten por correo, tengo una alergia, etc."></textarea>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <button id="btn-volver-slots" class="btn btn-secondary">« Volver</button>
                <button id="btn-confirmar-reserva" class="btn btn-primary">Confirmar y Agendar Cita</button>
            </div>
        </div>
    `;

    document.getElementById('btn-confirmar-reserva').addEventListener('click', () => {
        const notas = document.getElementById('cita-notas').value;
        processBooking(context, idServicio, fechaHora, notas);
    });

    document.getElementById('btn-volver-slots').addEventListener('click', () => {
        renderTimeSlotSelection(context, idServicio, nombreServicio);
    });
}

/**
 * Procesa el agendamiento final de la cita enviando los datos a la API.
 * @param {object} context - El contexto global de la aplicación.
 * @param {string} idServicio - El ID del servicio.
 * @param {string} fechaHora - La fecha y hora seleccionadas.
 * @param {string} notas - Las notas opcionales del cliente.
 */
async function processBooking(context, idServicio, fechaHora, notas) {
    const btnConfirmar = document.getElementById('btn-confirmar-reserva');
    btnConfirmar.disabled = true;
    btnConfirmar.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Agendando...`;
    
    try {
        const payload = { 
            id_cliente: context.state.clienteActual.id_cliente, 
            id_negocio: context.state.clienteActual.id_negocio, 
            id_servicio: idServicio, 
            fecha_hora_inicio: fechaHora,
            descripcion_trabajo: notas // <-- AÑADIDO: Enviamos las notas
        };
        const response = await fetch(`${context.API_URL}api_cliente_agendar_cita.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);

        alert(data.message);
        context.renderView('dashboard');
    } catch (error) {
        alert(`Error al agendar la cita: ${error.message}`);
        btnConfirmar.disabled = false;
        btnConfirmar.textContent = 'Confirmar y Agendar Cita';
    }
}

/**
 * Maneja la cancelación de una cita.
 * @param {object} context - El contexto global de la aplicación.
 * @param {string} idCita - El ID de la cita a cancelar.
 * @param {HTMLElement} button - El botón que disparó la acción, para dar feedback.
 */
export async function handleCancelarCita(context, idCita, button) {
    if (!confirm('¿Estás seguro de que deseas cancelar esta cita?')) return;

    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Procesando...`;

    try {
        const response = await fetch(`${context.API_URL}api_cliente_accion_cita.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id_cita: idCita, id_cliente: context.state.clienteActual.id_cliente, accion: 'cancelar' }) });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);

        // Notificar al propietario sobre la cancelación
        if (data.notification_payload) {
            handleClientCancellationNotification(data.notification_payload);
        }

        // Recargar la vista para mostrar el estado actualizado.
        context.renderView('dashboard');
    } catch (error) {
        alert(`Error al cancelar la cita: ${error.message}`);
        button.disabled = false;
        button.innerHTML = originalText;
    }
}

/**
 * Gestiona el envío de notificaciones al propietario cuando un cliente cancela.
 * @param {object} payload - Datos para la notificación (teléfono del propietario, etc.).
 */
function handleClientCancellationNotification(payload) {
    const { telefono_propietario, nombre_cliente, nombre_servicio, fecha_hora_inicio } = payload;

    if (!telefono_propietario) return;

    const numeroLimpio = telefono_propietario.replace(/[^\d+]/g, '').replace('+', '');
    const fecha = new Date(fecha_hora_inicio).toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });

    const mensaje = `ZApp Citas: El cliente ${nombre_cliente} ha CANCELADO su cita para "${nombre_servicio}" del ${fecha}.`;

    const whatsappUrl = `https://wa.me/${numeroLimpio}?text=${encodeURIComponent(mensaje)}`;
    const smsUrl = `sms:${numeroLimpio}?body=${encodeURIComponent(mensaje)}`;

    setTimeout(() => {
        if (confirm('Cita cancelada. ¿Deseas notificar al propietario por WhatsApp?')) {
            window.open(whatsappUrl, '_blank');
        } else if (confirm('¿Deseas notificar al propietario por SMS?')) {
            window.open(smsUrl, '_blank');
        }
    }, 500);
}

/**
 * Maneja la confirmación de una cita.
 * @param {object} context - El contexto global de la aplicación.
 * @param {string} idCita - El ID de la cita a confirmar.
 * @param {HTMLElement} button - El botón que disparó la acción, para dar feedback.
 */
export async function handleConfirmarCita(context, idCita, button) {
    if (!confirm('¿Deseas confirmar tu asistencia a esta cita?')) return;

    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Procesando...`;

    try {
        const response = await fetch(`${context.API_URL}api_cliente_accion_cita.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id_cita: idCita, id_cliente: context.state.clienteActual.id_cliente, accion: 'confirmar' }) });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);

        // Recargar la vista para mostrar el estado actualizado.
        context.renderView('dashboard');
    } catch (error) {
        alert(`Error al confirmar la cita: ${error.message}`);
        button.disabled = false;
        button.innerHTML = originalText;
    }
}
