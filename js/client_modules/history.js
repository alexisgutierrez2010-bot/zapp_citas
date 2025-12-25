// c:/xampp/htdocs/zapp_citas/js/client_modules/history.js

import { handleCancelarCita, handleConfirmarCita } from './booking.js';

/**
 * Muestra el historial completo de citas del cliente.
 * @param {object} context - El contexto global de la aplicación.
 */
export async function renderHistoryView(context) {
    context.dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"></div></div>`;

    try {
        const response = await fetch(`${context.API_URL}api_cliente_historial.php?id_cliente=${context.state.clienteActual.id_cliente}`);
        if (!response.ok) throw new Error('Error al cargar el historial de citas.');

        const historial = await response.json();

        let historialHtml = '';
        if (historial.length > 0) {
            const status_colors = {
                'Pendiente': 'bg-info text-dark',
                'Completada': 'bg-success',
                'Cancelada': 'bg-danger',
                'Pospuesta': 'bg-warning text-dark',
                'No Asistió': 'bg-secondary',
                'Confirmada': 'bg-primary',
                'Vencida': 'bg-dark',
            };

            const citasRows = historial.map((cita, index) => {
                const fecha = new Date(cita.fecha_hora_inicio);
                const estado = cita.estado_cita;
                const color_clase = status_colors[estado] ?? 'bg-light text-dark';
                const precioFormateado = cita.precio ? `$${parseFloat(cita.precio).toFixed(2)}` : '-';

                let accionesHtml = '';
                const esFutura = new Date(cita.fecha_hora_inicio) > new Date();

                if (esFutura) {
                    if (estado === 'Pendiente') {
                        accionesHtml += `<button class="btn btn-sm btn-success me-1 btn-confirmar-historial" data-id-cita="${cita.id_cita}">Confirmar</button>`;
                    }
                    if (estado === 'Pendiente' || estado === 'Confirmada') {
                        accionesHtml += `<button class="btn btn-sm btn-danger btn-cancelar-historial" data-id-cita="${cita.id_cita}">Cancelar</button>`;
                    }
                }

                return `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${fecha.toLocaleDateString('es-ES')}</td>
                        <td>${fecha.toLocaleTimeString('es-ES', { hour: 'numeric', minute: 'numeric' })}</td>
                        <td>${cita.nombre_servicio}</td>
                        <td>${precioFormateado}</td>
                        <td><span class="badge rounded-pill ${color_clase}">${estado}</span></td>
                        <td>${accionesHtml || '-'}</td>
                    </tr>
                `;
            }).join('');

            historialHtml = `
                <table class="table table-striped table-hover">
                    <thead class="table-dark"><tr><th>#</th><th>Fecha</th><th>Hora</th><th>Servicio</th><th>Precio</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>${citasRows}</tbody>
                </table>`;
        } else {
            historialHtml = `<div class="alert alert-info">No tienes citas en tu historial.</div>`;
        }
        context.dom.appContainer.innerHTML = `<h3>Mi Historial de Citas</h3>${historialHtml}`;

        // Listeners para los nuevos botones de acción
        document.querySelectorAll('.btn-cancelar-historial').forEach(btn => {
            btn.addEventListener('click', (e) => {
                handleCancelarCita(context, e.target.dataset.idCita, e.target);
            });
        });
        document.querySelectorAll('.btn-confirmar-historial').forEach(btn => {
            btn.addEventListener('click', (e) => {
                handleConfirmarCita(context, e.target.dataset.idCita, e.target);
            });
        });

    } catch (error) {
        context.dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}
