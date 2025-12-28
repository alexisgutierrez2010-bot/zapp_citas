// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
// js/owner_modules/agenda.js

/**
 * Renderiza la vista "Mi Agenda".
 * @param {object} context - El objeto de contexto de la aplicación.
 */
export async function renderAgendaView(context) {
    const { dom, state, API_URL } = context;

    // SOLUCIÓN: Estandarizar el formato de fecha a 'YYYY-MM-DD' antes de usarlo.
    // El estado inicial `state.currentDate` es un objeto Date(), pero este módulo lo necesita como string.
    let fechaParaAPI;
    if (state.currentDate instanceof Date) {
        const d = state.currentDate;
        fechaParaAPI = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        // Actualizamos el estado para que las siguientes navegaciones (prev/next) funcionen correctamente.
        state.currentDate = fechaParaAPI;
    } else if (typeof state.currentDate === 'string') {
        fechaParaAPI = state.currentDate;
    } else {
        // Fallback por si el estado es inválido
        const d = new Date();
        fechaParaAPI = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        state.currentDate = fechaParaAPI;
    }

    dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando agenda...</span></div></div>`;

    try {
        const response = await fetch(`${API_URL}api_owner_horario_disponible.php?fecha=${fechaParaAPI}`);
        if (!response.ok) throw new Error('No se pudo cargar la agenda.');
        
        const data = await response.json();
        const slots = data.slots || [];
        
        // Manejo seguro de fecha para visualización
        const [y, m, d] = (data.fecha || state.currentDate).split('-');
        const fechaObj = new Date(y, m - 1, d);
        const fechaMostrada = fechaObj.toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

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
                            <div class="mt-2 d-flex flex-wrap gap-2">
                                <button class="btn btn-sm btn-outline-primary btn-cambiar-estado" data-id-cita="${slot.cita.id_cita}" data-nuevo-estado="Completada">Completar</button>
                                <button class="btn btn-sm btn-outline-danger btn-cambiar-estado" data-id-cita="${slot.cita.id_cita}" data-nuevo-estado="Cancelada">Cancelar</button>
                                <button class="btn btn-sm btn-outline-secondary btn-cambiar-estado" data-id-cita="${slot.cita.id_cita}" data-nuevo-estado="No Asistió">No Asistió</button>
                                <button class="btn btn-sm btn-outline-warning btn-editar-cita" data-id-cita="${slot.cita.id_cita}">✏️ Editar</button>
                                <button class="btn btn-sm btn-outline-info btn-reenviar-email" data-id-cita="${slot.cita.id_cita}">📧 Email</button>
                                <button class="btn btn-sm btn-outline-dark btn-eliminar-cita" data-id-cita="${slot.cita.id_cita}">🗑️ Eliminar</button>
                            </div>
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
        // SOLUCIÓN: Unificar la creación de citas. Se llama a la vista completa 'crear-cita' en lugar del modal simple.
        document.getElementById('btn-agendar-cita').addEventListener('click', () => context.renderView('crear-cita'));
        const changeDate = (offset) => {
            const [yy, mm, dd] = state.currentDate.split('-').map(Number);
            const currentDate = new Date(yy, mm - 1, dd);
            currentDate.setDate(currentDate.getDate() + offset);
            
            const year = currentDate.getFullYear();
            const month = String(currentDate.getMonth() + 1).padStart(2, '0');
            const day = String(currentDate.getDate()).padStart(2, '0');
            state.currentDate = `${year}-${month}-${day}`;
            context.renderView('agenda');
        };

        document.getElementById('btn-dia-anterior').addEventListener('click', () => changeDate(-1));
        document.getElementById('btn-dia-siguiente').addEventListener('click', () => changeDate(1));
        document.getElementById('selector-fecha').addEventListener('change', (e) => {
            state.currentDate = e.target.value;
            context.renderView('agenda');
        });

        document.querySelectorAll('.btn-cambiar-estado').forEach(btn => {
            btn.addEventListener('click', (e) => {
                handleUpdateCitaStatus(context, e.target.dataset.idCita, e.target.dataset.nuevoEstado);
            });
        });

        // Listener para el botón de editar
        document.querySelectorAll('.btn-editar-cita').forEach(btn => {
            btn.addEventListener('click', (e) => {
                context.renderView('editar-cita', { id_cita: e.target.dataset.idCita });
            });
        });

        // Listener para el botón de reenviar email
        document.querySelectorAll('.btn-reenviar-email').forEach(btn => {
            btn.addEventListener('click', (e) => {
                handleResendEmail(context, e.target.dataset.idCita);
            });
        });

        // Listener para el botón de eliminar (Corrección: ahora se usa la función handleDeleteCita)
        document.querySelectorAll('.btn-eliminar-cita').forEach(btn => {
            btn.addEventListener('click', (e) => {
                handleDeleteCita(context, e.target.dataset.idCita);
            });
        });

    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

/**
 * Maneja el reenvío de la notificación por correo para una cita.
 * @param {object} context - El contexto de la aplicación.
 * @param {number} id_cita - El ID de la cita.
 */
async function handleResendEmail(context, id_cita) {
    const { API_URL } = context;
    if (!confirm(`¿Estás seguro de que quieres reenviar el correo de notificación para esta cita?`)) {
        return;
    }

    // Feedback visual para el usuario
    const btn = document.querySelector(`.btn-reenviar-email[data-id-cita="${id_cita}"]`);
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Enviando...`;
    }

    try {
        const response = await fetch(`${API_URL}api_owner_cita_enviar_email.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_cita: id_cita, accion: 'MODIFICADA' }) // 'MODIFICADA' es una acción genérica para reenviar
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);
        
        alert(data.message);
    } catch (error) {
        alert(`Error al enviar el correo: ${error.message}`);
    } finally {
        // Restaurar el botón
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = `📧 Email`;
        }
    }
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
        const response = await fetch(`${API_URL}api_owner_cita_actualizar.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_cita: id_cita, estado_cita: nuevoEstado })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);
        
        alert(data.message);
        renderView('agenda');

        if (data.notification_payload) {
            handleStatusChangeNotifications(data.notification_payload, data.nuevo_estado);
        }

    } catch (error) {
        alert(`Error al actualizar estado: ${error.message}`);
    }
}

/**
 * Gestiona el envío de notificaciones tras un cambio de estado.
 * @param {object} payload - Datos de la cita y cliente.
 * @param {string} nuevoEstado - El nuevo estado asignado.
 */
function handleStatusChangeNotifications(payload, nuevoEstado) {
    const { telefono_cliente, nombre_cliente, nombre_negocio, nombre_servicio, tipo_cita, descripcion_trabajo, fecha_hora_inicio } = payload;
    
    if (!telefono_cliente) return;

    const numeroLimpio = telefono_cliente.replace(/[^\d+]/g, '').replace('+', '');
    const servicio = tipo_cita === 'Reunion' ? descripcion_trabajo : nombre_servicio;
    const fecha = new Date(fecha_hora_inicio).toLocaleDateString('es-ES', { day: 'numeric', month: 'long', hour: 'numeric', minute: '2-digit' });
    const linkCliente = 'https://appcitas.acticven.com/zapp_citas/spa_client.php';

    let mensaje = '';

    switch (nuevoEstado) {
        case 'Completada':
            mensaje = `Hola ${nombre_cliente}, gracias por visitarnos en ${nombre_negocio}. Tu cita de "${servicio}" ha sido completada. ¡Esperamos verte pronto!`;
            break;
        case 'Cancelada':
            mensaje = `Hola ${nombre_cliente}, te informamos que tu cita en ${nombre_negocio} para "${servicio}" el ${fecha} ha sido cancelada. Para reagendar, visita: ${linkCliente}`;
            break;
        case 'No Asistió':
            mensaje = `Hola ${nombre_cliente}, te extrañamos hoy en tu cita de "${servicio}" en ${nombre_negocio}. Por favor contáctanos para reagendar: ${linkCliente}`;
            break;
        case 'Pospuesta':
            mensaje = `Hola ${nombre_cliente}, tu cita en ${nombre_negocio} para "${servicio}" ha sido pospuesta. Por favor revisa tu nueva fecha aquí: ${linkCliente}`;
            break;
        default:
            return; 
    }

    const whatsappUrl = `https://wa.me/${numeroLimpio}?text=${encodeURIComponent(mensaje)}`;
    const smsUrl = `sms:${numeroLimpio}?body=${encodeURIComponent(mensaje)}`;

    setTimeout(() => {
        if (confirm(`El estado cambió a "${nuevoEstado}".\n\n¿Deseas notificar al cliente por WhatsApp?`)) {
            window.open(whatsappUrl, '_blank');
        }
        
        setTimeout(() => {
            if (confirm(`¿Deseas enviar también una notificación por SMS?`)) {
                window.open(smsUrl, '_blank');
            }
        }, 500);
    }, 500);
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
        const response = await fetch(`${API_URL}api_owner_cita_eliminar_fisico.php`, {
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
