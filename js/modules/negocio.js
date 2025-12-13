// js/modules/negocio.js

/**
 * Renderiza la vista "Mi Negocio".
 * @param {object} context - El objeto de contexto de la aplicación.
 */
export async function renderNegocioView(context) {
    const { dom, T, API_URL } = context;
    dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"></div></div>`;

    try {
        const [negocioResponse, paisesResponse] = await Promise.all([
            fetch(`${API_URL}api_owner_negocio_get.php`),
            fetch(`${API_URL}api_paises.php`)
        ]);

        if (!negocioResponse.ok) {
            const errorData = await negocioResponse.json();
            throw new Error(errorData.error || T.error_loading_config || 'Could not load configuration.');
        }
        
        const negocio = await negocioResponse.json();
        const paises = await paisesResponse.json();

        const diasSemana = { '1': T.day_1, '2': T.day_2, '3': T.day_3, '4': T.day_4, '5': T.day_5, '6': T.day_6, '7': T.day_7 };
        const diasTrabajoActivos = negocio.dias_trabajo ? negocio.dias_trabajo.split(',') : [];
        const diasTrabajoCheckboxes = Object.entries(diasSemana).map(([num, dia]) => `
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="dias_trabajo[]" value="${num}" id="dia_${num}" ${diasTrabajoActivos.includes(num) ? 'checked' : ''}>
                <label class="form-check-label" for="dia_${num}">${dia || num}</label>
            </div>
        `).join('');

        const paisesOptions = paises.map(pais => 
            `<option value="${pais.id_pais}" ${negocio.id_pais == pais.id_pais ? 'selected' : ''}>${pais.nombre_pais}</option>`
        ).join('');

        dom.appContainer.innerHTML = `
            <div class="row justify-content-center">
                <div class="col-md-10 col-lg-8">
                    <div class="card">
                        <div class="card-header"><h3>${T.businesses_title}</h3></div>
                        <div class="card-body">
                            <div id="error-container-negocio"></div>
                            <form id="negocio-form">
                                <div class="mb-3"><label for="nombre_negocio" class="form-label">${T.businesses_form_name || 'Business Name'}</label><input type="text" class="form-control" id="nombre_negocio" value="${negocio.nombre_negocio || ''}"></div>
                                <div class="row"><div class="col-md-6 mb-3"><label class="form-label">${T.businesses_form_phone || 'Phone'}</label><input type="tel" class="form-control" value="${negocio.telefono || ''}" readonly disabled><small class="form-text text-muted">${T.phone_cannot_be_modified || 'Phone cannot be modified.'}</small></div><div class="col-md-6 mb-3"><label for="email" class="form-label">${T.businesses_form_email || 'Email'}</label><input type="email" class="form-control" id="email" value="${negocio.email || ''}"></div></div>
                                <hr><h5 class="mt-4">${T.clients_form_address || 'Address'}</h5>
                                <div class="mb-3"><label for="direccion1" class="form-label">${T.clients_form_address1 || 'Address 1'}</label><input type="text" class="form-control" id="direccion1" value="${negocio.direccion1 || ''}"></div>
                                <div class="row"><div class="col-md-6 mb-3"><label for="id_pais" class="form-label">${T.clients_form_country || 'Country'}</label><select class="form-select" id="id_pais" required>${paisesOptions}</select></div><div class="col-md-6 mb-3"><label for="id_estado" class="form-label">${T.clients_form_state || 'State'}</label><select class="form-select" id="id_estado" required disabled><option>${T.businesses_form_loading}</option></select></div></div>
                                <hr><h5 class="mt-4">${T.businesses_schedule_title}</h5>
                                <div class="mb-3"><label class="form-label">${T.businesses_schedule_days}</label><div>${diasTrabajoCheckboxes}</div></div>
                                <div class="row"><div class="col-md-4 mb-3"><label for="hora_inicio" class="form-label">${T.businesses_schedule_start}</label><input type="time" class="form-control" id="hora_inicio" value="${negocio.hora_inicio || ''}" required></div><div class="col-md-4 mb-3"><label for="hora_cierre" class="form-label">${T.businesses_schedule_end}</label><input type="time" class="form-control" id="hora_cierre" value="${negocio.hora_cierre || ''}" required></div><div class="col-md-4 mb-3"><label for="intervalo_minutos" class="form-label">${T.businesses_schedule_interval}</label><input type="number" class="form-control" id="intervalo_minutos" value="${negocio.intervalo_minutos || 30}" required></div></div>
                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <button type="button" class="btn btn-secondary" id="btn-cancelar-negocio">${T.cancel || 'Cancel'}</button>
                                    <button type="submit" class="btn btn-primary">${T.businesses_form_save}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const paisSelect = document.getElementById('id_pais');
        const estadoSelect = document.getElementById('id_estado');
        
        async function cargarEstados(idPais, idEstadoSeleccionado) {
            const response = await fetch(`${API_URL}api_estados.php?id_pais=${idPais}`);
            const estados = await response.json();
            estadoSelect.innerHTML = `<option value="">${T.businesses_form_select_country}</option>`;
            estados.forEach(estado => {
                estadoSelect.innerHTML += `<option value="${estado.id_estado}" ${estado.id_estado == idEstadoSeleccionado ? 'selected' : ''}>${estado.nombre_estado}</option>`;
            });
            estadoSelect.disabled = false;
        }

        paisSelect.addEventListener('change', () => cargarEstados(paisSelect.value));
        await cargarEstados(paisSelect.value, negocio.id_estado);

        document.getElementById('btn-cancelar-negocio').addEventListener('click', () => context.renderView('agenda'));
        document.getElementById('negocio-form').addEventListener('submit', (e) => handleUpdateNegocio(e, context));

    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

async function handleUpdateNegocio(e, context) {
    e.preventDefault();
    const { dom, T, API_URL } = context;
    const errorContainer = document.getElementById('error-container-negocio');
    const submitButton = e.target.querySelector('button[type="submit"]');
    errorContainer.innerHTML = '';

    const payload = {
        nombre_negocio: document.getElementById('nombre_negocio').value,
        email: document.getElementById('email').value,
        dias_trabajo: Array.from(document.querySelectorAll('input[name="dias_trabajo[]"]:checked')).map(cb => cb.value),
        hora_inicio: document.getElementById('hora_inicio').value,
        hora_cierre: document.getElementById('hora_cierre').value,
        intervalo_minutos: document.getElementById('intervalo_minutos').value,
        direccion1: document.getElementById('direccion1').value,
        id_pais: document.getElementById('id_pais').value,
        id_estado: document.getElementById('id_estado').value,
    };

    submitButton.disabled = true;
    submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${T.save}...`;

    try {
        const response = await fetch(`${API_URL}api_owner_negocio_update.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || T.error_saving || 'There was a problem saving.');
        
        errorContainer.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
        dom.navbarBrand.textContent = payload.nombre_negocio;
    } catch (error) {
        errorContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = T.businesses_form_save;
    }
}