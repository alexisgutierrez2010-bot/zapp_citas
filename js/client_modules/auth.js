// c:/xampp/htdocs/zapp_citas/js/client_modules/auth.js

/**
 * Muestra la vista de login, cargando el CAPTCHA y la lista de países.
 * @param {object} context - El contexto global de la aplicación.
 */
export async function renderLoginView(context) {
    try {
        const [captchaResponse, paisesResponse] = await Promise.all([
            fetch(`${context.API_URL}api_cliente_login.php`),
            fetch(`${context.API_URL}api_paises.php`)
        ]);

        if (!captchaResponse.ok || !paisesResponse.ok) {
            throw new Error(context.T.client_login_error_loading_data);
        }

        const captchaData = await captchaResponse.json();
        const paises = await paisesResponse.json();
        const captchaHash = captchaData.captcha_hash;

        const paisesOptions = paises.map(pais =>
            `<option value="${pais.codigo_telefono}" ${pais.id_pais == 1 ? 'selected' : ''}>${pais.nombre_pais} (${pais.codigo_telefono})</option>`
        ).join('');

        context.dom.appContainer.innerHTML = `
            <div class="row justify-content-center">
                <div class="col-md-5 col-lg-4">
                    <div class="card">
                        <div class="card-header text-center bg-primary text-white"><h3>${context.T.welcome}</h3></div>
                        <div class="card-body">
                            <div id="error-container"></div>
                            <form id="login-form">
                                <div class="mb-3">
                                    <label for="numero_celular" class="form-label">${context.T.client_login_phone_label}</label>
                                    <div class="input-group">
                                        <select class="form-select" id="country_code" name="country_code" style="max-width: 150px;">
                                            ${paisesOptions}
                                        </select>
                                        <input type="tel" class="form-control" id="numero_celular" name="numero_celular" placeholder="${context.T.client_login_phone_placeholder}" required>
                                    </div>
                                </div>
                                <div class="mb-3 text-center">
                                    <label for="captcha" class="form-label">${context.T.client_login_captcha_label}</label>
                                    <div class="p-2 bg-dark text-white rounded font-monospace fs-4" style="letter-spacing: 5px;">
                                        ${captchaHash}
                                    </div>
                                    <div class="mx-auto" style="max-width: 200px;">
                                        <input type="text" name="captcha" id="captcha" class="form-control mt-2 text-center" autocomplete="off" required>
                                    </div>
                                <small class="form-text text-muted">${context.T.client_login_captcha_expires}</small>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">${context.T.login_button}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('login-form').addEventListener('submit', (e) => handleLogin(context, e));
    } catch (error) {
        context.dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        console.error("Error in renderLoginView:", error);
    }
}

/**
 * Maneja el envío del formulario de login.
 * @param {object} context - El contexto global de la aplicación.
 * @param {Event} event - El evento de submit del formulario.
 */
async function handleLogin(context, event) {
    event.preventDefault();
    const form = event.target;
    const countryCode = form.querySelector('#country_code').value;
    const phoneNumber = form.querySelector('#numero_celular').value;
    const captcha = form.querySelector('#captcha').value;
    const errorContainer = document.getElementById('error-container');

    const fullPhoneNumber = `${countryCode.trim()} ${phoneNumber.replace(/\s/g, '')}`;

    try {
        const response = await fetch(`${context.API_URL}api_cliente_login.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ numero_celular: fullPhoneNumber, captcha: captcha })
        });

        const data = await response.json();

        if (!response.ok) {
            if (response.status === 404) {
                context.renderView('confirm-register', { numeroCelular: fullPhoneNumber });
                return;
            } else {
                throw new Error(data.error || context.T.client_login_error_unknown);
            }
        }

        context.state.clienteActual = data;
        await context.updateNavbar(context);
        context.renderView('dashboard');

    } catch (error) {
        errorContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        setTimeout(() => context.renderView('login'), 2000);
    }
}

/**
 * Cierra la sesión del cliente.
 * @param {object} context - El contexto global de la aplicación.
 */
export function handleLogout(context) {
    context.state.clienteActual = null;
    context.updateNavbar(context);
    context.renderView('login');
}

/**
 * Muestra la vista de confirmación para registrar un nuevo cliente.
 * @param {object} context - El contexto global de la aplicación.
 * @param {object} params - Parámetros, debe incluir `numeroCelular`.
 */
export function renderConfirmacionRegistroView(context, params) {
    const { numeroCelular } = params;
    context.dom.appContainer.innerHTML = `
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card text-center">
                    <div class="card-header"><h3>${context.T.client_register_not_found_title}</h3></div>
                    <div class="card-body">
                        <p class="lead">${context.T.client_register_not_found_lead.replace('{phone}', `<strong>${numeroCelular}</strong>`)}</p>
                        <p>${context.T.client_register_not_found_prompt}</p>
                        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-4">
                            <button class="btn btn-primary btn-lg px-4 gap-3" data-view="register" data-numero-celular="${numeroCelular}">${context.T.yes_register}</button>
                            <button class="btn btn-outline-secondary btn-lg px-4" data-view="login">${context.T.no_back}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Muestra el formulario de registro para un nuevo cliente.
 * @param {object} context - El contexto global de la aplicación.
 * @param {object} params - Parámetros, debe incluir `numeroCelular`.
 */
export async function renderRegistroView(context, params) {
    const { numeroCelular } = params;
    try {
        const [paisesResponse, negociosResponse] = await Promise.all([
            fetch(`${context.API_URL}api_paises.php`),
            fetch(`${context.API_URL}api_negocios_lista_publica.php`)
        ]);

        const paises = await paisesResponse.json();
        const negocios = await negociosResponse.json();

        const paisesOptions = paises.map(pais => `<option value="${pais.id_pais}" ${pais.id_pais == 1 ? 'selected' : ''}>${pais.nombre_pais}</option>`).join('');
        const negociosOptions = negocios.map(negocio => `<option value="${negocio.id_negocio}">${negocio.nombre_negocio}</option>`).join('');

        context.dom.appContainer.innerHTML = `
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header text-center"><h3>${context.T.client_register_form_title}</h3></div>
                        <div class="card-body">
                            <p class="text-muted">${context.T.client_register_form_subtitle}</p>
                            <div id="error-container"></div>
                            <form id="registro-form">
                                <input type="hidden" id="numero_celular_registro" value="${numeroCelular}">
                                <div class="mb-3"><label class="form-label">${context.T.clients_form_phone}</label><input type="tel" class="form-control" value="${numeroCelular}" readonly></div>
                                <div class="mb-3"><label for="nombre_completo" class="form-label">${context.T.clients_form_name}</label><input type="text" class="form-control" id="nombre_completo" required></div>
                                <div class="mb-3"><label for="correo_electronico" class="form-label">${context.T.clients_form_email}</label><input type="email" class="form-control" id="correo_electronico" required></div>
                                <div class="mb-3"><label for="id_negocio" class="form-label">${context.T.client_register_form_business_label}</label><select class="form-select" id="id_negocio" required>${negociosOptions}</select></div>
                                <div class="d-grid"><button type="submit" class="btn btn-success">${context.T.client_register_form_button}</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('registro-form').addEventListener('submit', (e) => handleRegistro(context, e));

    } catch (error) {
        context.dom.appContainer.innerHTML = `<div class="alert alert-danger">${context.T.client_register_error_loading}</div>`;
        console.error("Error in renderRegistroView:", error);
    }
}

/**
 * Maneja el envío del formulario de registro.
 * @param {object} context - El contexto global de la aplicación.
 * @param {Event} event - El evento de submit del formulario.
 */
async function handleRegistro(context, event) {
    event.preventDefault();
    const form = event.target;
    const errorContainer = document.getElementById('error-container');
    errorContainer.innerHTML = '';

    const payload = {
        numero_celular: form.querySelector('#numero_celular_registro').value,
        nombre_completo: form.querySelector('#nombre_completo').value,
        correo_electronico: form.querySelector('#correo_electronico').value,
        id_negocio: form.querySelector('#id_negocio').value,
        // Campos opcionales/no solicitados en este formulario simplificado
        id_pais: 1, // Valor por defecto o se podría añadir al form
        id_estado: 1, // Valor por defecto
    };

    if (!payload.nombre_completo || !payload.correo_electronico || !payload.id_negocio) {
        errorContainer.innerHTML = `<div class="alert alert-danger">${context.T.error_all_fields_required}</div>`;
        return;
    }

    try {
        const response = await fetch(`${context.API_URL}api_cliente_registro.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || context.T.client_register_error_generic);
        }

        context.dom.appContainer.innerHTML = `<div class="alert alert-success"><h4>${context.T.client_register_success_title}</h4><p>${data.message}</p><p>${context.T.client_register_success_redirect}</p></div>`;
        setTimeout(() => context.renderView('login'), 4000);

    } catch (error) {
        errorContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}
