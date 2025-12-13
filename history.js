// c:/xampp/htdocs/zapp_citas/js/client_modules/history.js

/**
 * Muestra el historial completo de citas del cliente.
 * @param {object} context - El contexto global de la aplicación.
 */
export async function renderHistoryView(context) {
    context.dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"></div></div>`;

    try {
        const response = await fetch(`${context.API_URL}api_cliente_historial.php?id_cliente=${context.state.clienteActual.id_cliente}`);
        if (!response.ok) {
            throw new Error(context.T.client_history_error_loading);
        }
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
                        <td>${fecha.toLocaleDateString(context.state.currentLang)}</td>
                        <td>${fecha.toLocaleTimeString(context.state.currentLang, { hour: 'numeric', minute: 'numeric' })}</td>
                        <td>${cita.nombre_servicio}</td>
                        <td><span class="badge ${color_clase}">${estado}</span></td>
                        <td><small class="text-muted">${cita.id_cita}</small></td>
                    </tr>
                `;
            }).join('');

            historialHtml = `
                <table class="table table-striped table-hover">
                    <thead class="table-dark"><tr><th>#</th><th>${context.T.date}</th><th>${context.T.time}</th><th>${context.T.service}</th><th>${context.T.status}</th><th>ID</th></tr></thead>
                    <tbody>${citasRows}</tbody>
                </table>`;
        } else {
            historialHtml = `<div class="alert alert-info">${context.T.client_history_no_records}</div>`;
        }
        context.dom.appContainer.innerHTML = `<h3>${context.T.client_nav_history}</h3>${historialHtml}`;

    } catch (error) {
        context.dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}