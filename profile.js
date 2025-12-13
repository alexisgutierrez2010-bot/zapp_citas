// c:/xampp/htdocs/zapp_citas/js/client_modules/profile.js

/**
 * Muestra la vista del perfil del cliente, con un formulario para editar sus datos.
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
                                    <label for="id_pais" class="form-label">${context.T.clients_form_country}</label>
                                    <select class="form-select" id="id_pais" required>${paisesOptions}</select>
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
        // Campos no editables en esta vista simplificada, se envían para mantener la consistencia en la API
        id_estado: context.state.clienteActual.id_estado || 1,
        direccion1: context.state.clienteActual.direccion1 || '',
        direccion2: context.state.clienteActual.direccion2 || '',
        ciudad: context.state.clienteActual.ciudad || '',
        zip_code: context.state.clienteActual.zip_code || '',
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

        feedbackContainer.innerHTML = `<div class="alert alert-success">${data.message}</div>`;

    } catch (error) {
        feedbackContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = context.T.client_profile_update_button;
    }
}