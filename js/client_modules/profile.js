// c:/xampp/htdocs/zapp_citas/js/client_modules/profile.js

/**
 * Muestra la vista del perfil del cliente, con un formulario completo para editar sus datos.
 * @param {object} context - El contexto global de la aplicación.
 */
export async function renderProfileView(context) {
    context.dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"></div></div>`;

    try {
        const [perfilResponse, paisesResponse] = await Promise.all([
            fetch(`${context.API_URL}api_cliente_perfil.php?id_cliente=${context.state.clienteActual.id_cliente}`),
            fetch(`${context.API_URL}api_paises.php`)
        ]);

        if (!perfilResponse.ok) throw new Error(context.T.client_profile_error_loading);

        const perfil = await perfilResponse.json();
        const paises = await paisesResponse.json();

        const paisesOptions = paises.map(pais =>
            `<option value="${pais.id_pais}" ${perfil.id_pais == pais.id_pais ? 'selected' : ''}>${pais.nombre_pais}</option>`
        ).join('');

        context.dom.appContainer.innerHTML = `
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header"><h3>${context.T.client_nav_profile}</h3></div>
                        <div class="card-body">
                            <div id="feedback-container"></div>
                            <form id="profile-form">
                                <div class="mb-3">
                                    <label for="nombre_completo" class="form-label">${context.T.clients_form_name}</label>
                                    <input type="text" class="form-control" id="nombre_completo" value="${perfil.nombre_completo}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="numero_celular" class="form-label">${context.T.clients_form_phone} (Login)</label>
                                    <input type="tel" class="form-control" id="numero_celular" value="${perfil.numero_celular || ''}" readonly disabled>
                                    <small class="form-text text-muted">${context.T.phone_cannot_be_modified}</small>
                                </div>
                                <div class="mb-3">
                                    <label for="correo_electronico" class="form-label">${context.T.clients_form_email}</label>
                                    <input type="email" class="form-control" id="correo_electronico" value="${perfil.correo_electronico}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="direccion1" class="form-label">${context.T.clients_form_address1}</label>
                                    <input type="text" class="form-control" id="direccion1" value="${perfil.direccion1 || ''}">
                                </div>
                                <div class="mb-3">
                                    <label for="direccion2" class="form-label">${context.T.clients_form_address2}</label>
                                    <input type="text" class="form-control" id="direccion2" value="${perfil.direccion2 || ''}">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="id_pais" class="form-label">${context.T.clients_form_country}</label>
                                        <select class="form-select" id="id_pais" required>${paisesOptions}</select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="id_estado" class="form-label">${context.T.clients_form_state}</label>
                                        <select class="form-select" id="id_estado" required disabled><option>${context.T.businesses_form_loading}</option></select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label for="ciudad" class="form-label">${context.T.clients_form_city}</label>
                                        <input type="text" class="form-control" id="ciudad" value="${perfil.ciudad || ''}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="zip_code" class="form-label">${context.T.clients_form_zip}</label>
                                        <input type="text" class="form-control" id="zip_code" value="${perfil.zip_code || ''}">
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">${context.T.client_profile_update_button}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Lógica para cargar estados dinámicamente
        const paisSelect = document.getElementById('id_pais');
        const estadoSelect = document.getElementById('id_estado');

        async function cargarEstados(idPais, idEstadoSeleccionado) {
            try {
                const response = await fetch(`${context.API_URL}api_estados.php?id_pais=${idPais}`);
                const estados = await response.json();
                estadoSelect.innerHTML = `<option value="">${context.T.businesses_form_select_state}</option>`;
                estados.forEach(estado => {
                    const option = document.createElement('option');
                    option.value = estado.id_estado;
                    option.textContent = estado.nombre_estado;
                    if (idEstadoSeleccionado && estado.id_estado == idEstadoSeleccionado) {
                        option.selected = true;
                    }
                    estadoSelect.appendChild(option);
                });
                estadoSelect.disabled = false;
            } catch (error) { console.error('Error loading states:', error); }
        }
        paisSelect.addEventListener('change', () => cargarEstados(paisSelect.value));
        await cargarEstados(paisSelect.value, perfil.id_estado);

        document.getElementById('profile-form').addEventListener('submit', (e) => handleUpdateProfile(context, e));

    } catch (error) {
        context.dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

/**
 * Maneja el envío del formulario de actualización del perfil.
 * @param {object} context - El contexto global de la aplicación.
 * @param {Event} event - El evento de submit del formulario.
 */
async function handleUpdateProfile(context, event) {
    event.preventDefault();
    const feedbackContainer = document.getElementById('feedback-container');
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    feedbackContainer.innerHTML = '';

    const payload = {
        id_cliente: context.state.clienteActual.id_cliente,
        nombre_completo: form.querySelector('#nombre_completo').value,
        correo_electronico: form.querySelector('#correo_electronico').value,
        id_pais: form.querySelector('#id_pais').value,
        id_estado: form.querySelector('#id_estado').value,
        direccion1: form.querySelector('#direccion1').value,
        direccion2: form.querySelector('#direccion2').value,
        ciudad: form.querySelector('#ciudad').value,
        zip_code: form.querySelector('#zip_code').value,
    };

    submitButton.disabled = true;
    submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${context.T.updating}...`;

    try {
        const response = await fetch(`${context.API_URL}api_cliente_perfil.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || context.T.client_profile_error_updating);
        }

        // Actualizar el nombre en el estado local y en la barra de navegación
        context.state.clienteActual.nombre_completo = payload.nombre_completo;
        await context.updateNavbar(context);

        // MEJORA: Usar una clave de traducción en lugar del mensaje directo de la API.
        const messageKey = data.message_key || 'operation_success';
        feedbackContainer.innerHTML = `<div class="alert alert-success">${context.T[messageKey]}</div>`;

    } catch (error) {
        feedbackContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = context.T.client_profile_update_button;
    }
}
