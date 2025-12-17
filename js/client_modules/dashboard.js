// c:/xampp/htdocs/zapp_citas/js/client_modules/dashboard.js
export async function renderDashboardView(context) {
    context.dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div></div>`;

    try {
        const response = await fetch(`${context.API_URL}api_cliente_citas.php?id_cliente=${context.state.clienteActual.id_cliente}`);
        if (!response.ok) {
            throw new Error('Error al cargar tus citas.');
        }
        const data = await response.json();
        const cita = data.cita_reciente;
        const cliente = context.state.clienteActual;

        let citaHtml = '';
        if (cita) {
            const fecha = new Date(cita.fecha_hora_inicio);
            const opcionesFecha = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const opcionesHora = { hour: 'numeric', minute: 'numeric', hour12: true };

            citaHtml = `
                <div class="card">
                    <div class="card-header">Tu Próxima Cita</div>
                    <div class="card-body">
                        <h5 class="card-title">${cita.nombre_servicio}</h5>
                        <p class="card-text">
                            <strong>Fecha:</strong> ${fecha.toLocaleDateString('es-ES', opcionesFecha)}<br>
                            <strong>Hora:</strong> ${fecha.toLocaleTimeString('es-ES', opcionesHora)}<br>
                            <strong>Estado:</strong> <span class="badge bg-info text-dark">${cita.estado_cita}</span><br>
                            ${cita.descripcion_trabajo ? `<strong>Notas:</strong> ${cita.descripcion_trabajo}` : ''}
                        </p>
                        ${cita.estado_cita === 'Pendiente' ? `<a href="#" class="btn btn-success" data-action="confirm-appointment" data-id-cita="${cita.id_cita}">Confirmar Asistencia</a>` : ''}
                        ${cita.estado_cita === 'Pendiente' || cita.estado_cita === 'Confirmada' ? `<a href="#" class="btn btn-danger ms-2" data-action="cancel-appointment" data-id-cita="${cita.id_cita}">Cancelar Cita</a>` : ''}
                    </div>
                </div>
            `;
        } else {
            citaHtml = `
                <div class="alert alert-info">No tienes citas próximas.</div>
                <button class="btn btn-primary" data-view="booking">Agenda tu primera cita</button>
            `;
        }

        context.dom.appContainer.innerHTML = `
            <div class="row">
                <div class="col-12">
                    <h3>¡Hola, ${cliente.nombre_completo}!</h3>
                    <hr>
                </div>
                <div class="col-md-8">${citaHtml}</div>
            </div>
        `;
    } catch (error) {
        context.dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}
