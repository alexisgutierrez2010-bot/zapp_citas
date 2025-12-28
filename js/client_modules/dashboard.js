// c:/xampp/htdocs/zapp_citas/js/client_modules/dashboard.js
export async function renderDashboardView(context) {
    context.dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div></div>`;

    try {
        const response = await fetch(`${context.API_URL}api_cliente_citas.php?id_cliente=${context.state.clienteActual.id_cliente}`);
        if (!response.ok) {
            throw new Error('Error al cargar tus citas.');
        }
        const data = await response.json();
        const citas = data.citas_proximas;
        const cliente = context.state.clienteActual;

        let citasHtml = '';
        if (citas && citas.length > 0) {
            citasHtml = `
                <h4>Tus Próximas Citas</h4>
                <div class="list-group">
                    ${citas.map(cita => {
                        const fecha = new Date(cita.fecha_hora_inicio);
                        // HOMOLOGACIÓN: Formato dd/mm/yyyy para consistencia con el historial.
                        const fechaStr = fecha.toLocaleDateString('es-ES');
                        const horaStr = fecha.toLocaleTimeString('es-ES', { hour: 'numeric', minute: 'numeric', hour12: true });
                        const status_colors = { 'Pendiente': 'bg-info text-dark', 'Confirmada': 'bg-primary' };
                        const color_clase = status_colors[cita.estado_cita] ?? 'bg-secondary';
                        const precio = parseFloat(cita.precio || 0).toFixed(2);
                        
                        return `
                            <div class="list-group-item list-group-item-action flex-column align-items-start">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">${cita.nombre_servicio}</h5>
                                    <small><span class="badge ${color_clase}">${cita.estado_cita}</span></small>
                                </div>
                                <p class="mb-1">
                                    ${fechaStr} a las ${horaStr}
                                </p>
                                <p class="mb-1"><strong>Costo:</strong> $${precio}</p>
                                ${cita.descripcion_trabajo ? `<p class="mb-1 text-muted"><small><strong>Nota:</strong> ${cita.descripcion_trabajo}</small></p>` : ''}
                                <div class="mt-2">
                                    ${cita.estado_cita === 'Pendiente' ? `<button class="btn btn-sm btn-success" data-action="confirm-appointment" data-id-cita="${cita.id_cita}">Confirmar</button>` : ''}
                                    ${cita.estado_cita === 'Pendiente' ? `<button class="btn btn-sm btn-info ms-2" data-action="reschedule-appointment" data-id-cita="${cita.id_cita}" data-id-servicio="${cita.id_servicio}" data-nombre-servicio="${cita.nombre_servicio}" data-fecha-hora-inicio="${cita.fecha_hora_inicio}">Editar</button>` : ''}
                                    ${cita.estado_cita === 'Pendiente' || cita.estado_cita === 'Confirmada' ? `<button class="btn btn-sm btn-danger ms-2" data-action="cancel-appointment" data-id-cita="${cita.id_cita}">Cancelar</button>` : ''}
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        } else {
            citasHtml = `
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">¡Todo listo para tu próxima visita!</h5>
                        <p class="card-text">No tienes citas próximas agendadas. ¿Te gustaría reservar una ahora?</p>
                        <button class="btn btn-primary" data-view="booking">Agendar Nueva Cita</button>
                    </div>
                </div>
            `;
        }

        context.dom.appContainer.innerHTML = `
            <h3>¡Hola, ${cliente.nombre_completo}!</h3>
            <p class="lead">Bienvenido a tu portal de citas.</p>
            <hr>
            ${citasHtml}
        `;
    } catch (error) {
        context.dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}
