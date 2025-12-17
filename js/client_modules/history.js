// c:/xampp/htdocs/zapp_citas/js/client_modules/history.js

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
                return `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${fecha.toLocaleDateString('es-ES')}</td>
                        <td>${fecha.toLocaleTimeString('es-ES', { hour: 'numeric', minute: 'numeric' })}</td>
                        <td>${cita.nombre_servicio}</td>
                        <td><span class="badge ${color_clase}">${estado}</span></td>
                        <td><small class="text-muted">${cita.descripcion_trabajo || 'Sin notas'}</small></td>
                    </tr>
                `;
            }).join('');

            historialHtml = `
                <table class="table table-striped table-hover">
                    <thead class="table-dark"><tr><th>#</th><th>Fecha</th><th>Hora</th><th>Servicio</th><th>Estado</th><th>Notas</th></tr></thead>
                    <tbody>${citasRows}</tbody>
                </table>`;
        } else {
            historialHtml = `<div class="alert alert-info">No tienes citas en tu historial.</div>`;
        }
        context.dom.appContainer.innerHTML = `<h3>Mi Historial de Citas</h3>${historialHtml}`;

    } catch (error) {
        context.dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}
