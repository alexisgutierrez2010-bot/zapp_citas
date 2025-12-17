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
            throw new Error('Error al cargar datos iniciales. Por favor, recarga la página.');
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
                        <div class="card-header text-center bg-primary text-white"><h3>Bienvenido</h3></div>
                        <div class="card-body">
                            <div id="error-container"></div>
                            <form id="login-form">
                                <div class="mb-3">
                                    <label for="numero_celular" class="form-label">Tu Número de Teléfono</label>
                                    <div class="input-group">
                                        <select class="form-select" id="country_code" name="country_code" style="max-width: 150px;">
                                            ${paisesOptions}
                                        </select>
                                        <input type="tel" class="form-control" id="numero_celular" name="numero_celular" placeholder="Ej: 555 123 4567" required>
                                    </div>
                                </div>
                                <div class="mb-3 text-center">
                                    <label for="captcha" class="form-label">Ingresa el código de seguridad</label>
                                    <div class="p-2 bg-dark text-white rounded font-monospace fs-4" style="letter-spacing: 5px;">
                                        ${captchaHash}
                                    </div>
                                    <div class="mx-auto" style="max-width: 200px;">
                                        <input type="text" name="captcha" id="captcha" class="form-control mt-2 text-center" autocomplete="off" required>
                                    </div>
                                <small class="form-text text-muted">El código expira en 2 minutos.</small>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Ingresar</button>
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
                // CORRECCIÓN: Volver al flujo original de confirmación.
                context.renderView('confirm-register', { numeroCelular: fullPhoneNumber });
                return;
            } else {
                throw new Error(data.error || 'Ocurrió un error desconocido.');
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
                    <div class="card-header"><h3>Cliente no Encontrado</h3></div>
                    <div class="card-body">
                        <p class="lead">El número <strong>${numeroCelular}</strong> no está registrado.</p>
                        <p>¿Deseas registrarte como un nuevo cliente?</p>
                        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-4">
                            <button class="btn btn-primary btn-lg px-4 gap-3" data-view="register" data-numero-celular="${numeroCelular}">Sí, registrarme</button>
                            <button class="btn btn-outline-secondary btn-lg px-4" data-view="login">No, volver</button>
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
    const { numeroCelular } = params; // El número ya viene con el formato "+código número"
    try {
        const [paisesResponse, negociosResponse] = await Promise.all([
            fetch(`${context.API_URL}api_paises.php`),
            fetch(`${context.API_URL}api_negocios_lista_publica.php`)
        ]);
 
        const paises = await paisesResponse.json();
        const negocios = await negociosResponse.json();
 
        const paisesOptions = '<option value="" disabled selected>Seleccione un país...</option>' + paises.map(pais => `<option value="${pais.id_pais}">${pais.nombre_pais}</option>`).join('');
        const negociosOptions = '<option value="" disabled selected>Seleccione un negocio...</option>' + negocios.map(negocio => `<option value="${negocio.id_negocio}">${negocio.nombre_negocio}</option>`).join('');
 
        context.dom.appContainer.innerHTML = `
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header text-center"><h3>Registro de Nuevo Cliente</h3></div>
                        <div class="card-body">
                            <div id="error-container"></div>
                            <form id="registro-form">
                                <input type="hidden" id="numero_celular_registro" value="${numeroCelular}">
                                
                                <div class="alert alert-light">
                                    <strong>Número a registrar:</strong> ${numeroCelular}
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nombre_completo" class="form-label">Nombre Completo</label>
                                        <input type="text" class="form-control" id="nombre_completo" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="correo_electronico" class="form-label">Correo Electrónico</label>
                                        <input type="email" class="form-control" id="correo_electronico" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="id_negocio" class="form-label">¿Para qué negocio te registras?</label>
                                    <select class="form-select" id="id_negocio" required>${negociosOptions}</select>
                                </div>

                                <hr>
                                <h5 class="mt-3">Datos Adicionales (Opcional)</h5>

                                <div class="mb-3">
                                    <label for="direccion1" class="form-label">Dirección 1</label>
                                    <input type="text" class="form-control" id="direccion1">
                                </div>
                                <div class="mb-3">
                                    <label for="direccion2" class="form-label">Dirección 2 (Opcional)</label>
                                    <input type="text" class="form-control" id="direccion2">
                                </div>

                                <div class="row">
                                    <div class="col-md-8 mb-3"><label for="ciudad" class="form-label">Ciudad</label><input type="text" class="form-control" id="ciudad"></div>
                                    <div class="col-md-4 mb-3"><label for="zip_code" class="form-label">Código Postal</label><input type="text" class="form-control" id="zip_code"></div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="id_pais" class="form-label">País</label>
                                        <select class="form-select" id="id_pais">${paisesOptions}</select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="id_estado" class="form-label">Estado / Provincia</label>
                                        <select class="form-select" id="id_estado" disabled>
                                            <option value="">Seleccione un país primero...</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Preferencias de Comunicación</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="in_email" checked>
                                        <label class="form-check-label" for="in_email">Recibir notificaciones por correo.</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="in_sms">
                                        <label class="form-check-label" for="in_sms">Recibir notificaciones por SMS.</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="in_whatsapp">
                                        <label class="form-check-label" for="in_whatsapp">Recibir notificaciones por WhatsApp.</label>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" class="btn btn-secondary" data-view="login">Cancelar</button>
                                    <button type="submit" class="btn btn-success">Completar Registro</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;
 
        const paisSelect = document.getElementById('id_pais');
        const estadoSelect = document.getElementById('id_estado');
 
        paisSelect.addEventListener('change', async () => {
            const idPais = paisSelect.value;
            if (!idPais) {
                estadoSelect.innerHTML = '<option value="">Seleccione un país primero...</option>';
                estadoSelect.disabled = true;
                return;
            }
            estadoSelect.disabled = false;
            estadoSelect.innerHTML = '<option value="">Cargando...</option>';
            try {
                const response = await fetch(`${context.API_URL}api_estados.php?id_pais=${idPais}`);
                const estados = await response.json();
                estadoSelect.innerHTML = '<option value="" disabled selected>Seleccione un estado...</option>' + estados.map(e => `<option value="${e.id_estado}">${e.nombre_estado}</option>`).join('');
            } catch (error) {
                estadoSelect.innerHTML = '<option value="">Error al cargar</option>';
            }
        });
 
        document.getElementById('registro-form').addEventListener('submit', (e) => handleRegistro(context, e));
 
    } catch (error) {
        context.dom.appContainer.innerHTML = `<div class="alert alert-danger">Error al cargar el formulario de registro.</div>`;
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
        direccion1: form.querySelector('#direccion1').value,
        direccion2: form.querySelector('#direccion2').value,
        ciudad: form.querySelector('#ciudad').value,
        zip_code: form.querySelector('#zip_code').value,
        id_pais: form.querySelector('#id_pais').value,
        id_estado: form.querySelector('#id_estado').value,
        in_email: form.querySelector('#in_email').checked ? 1 : 0,
        in_sms: form.querySelector('#in_sms').checked ? 1 : 0,
        in_whatsapp: form.querySelector('#in_whatsapp').checked ? 1 : 0,
    };

    if (!payload.nombre_completo || !payload.correo_electronico || !payload.id_negocio) {
        errorContainer.innerHTML = `<div class="alert alert-danger">Todos los campos son obligatorios.</div>`;
        return;
    }
    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    try {
        const response = await fetch(`${context.API_URL}api_cliente_registro.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Ocurrió un error durante el registro.');
        }

        context.dom.appContainer.innerHTML = `<div class="alert alert-success"><h4>¡Registro Exitoso!</h4><p>${data.message}</p><p>Serás redirigido a la pantalla de inicio de sesión en unos segundos.</p></div>`;
        setTimeout(() => context.renderView('login'), 4000);

    } catch (error) {
        errorContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    } finally {
        submitButton.disabled = false;
    }
}
