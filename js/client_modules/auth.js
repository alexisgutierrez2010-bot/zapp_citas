// js/client_modules/auth.js
import { updateNavbar } from './ui.js';

/**
 * Renderiza la vista de inicio de sesión para el cliente.
 * @param {object} context - El contexto global de la aplicación.
 */
export async function renderLoginView(context) {
    const { dom, API_URL } = context;

    updateNavbar(context);

    dom.appContainer.innerHTML = `
        <div class="row justify-content-center mt-5">
            <div class="col-md-5 col-lg-4">
                <div class="card shadow-lg">
                    <div class="card-header text-center bg-primary text-white">
                        <h3>Acceso de Cliente</h3>
                    </div>
                    <div class="card-body p-4">
                        <div id="error-container" class="mb-3"></div>
                        <p class="text-muted text-center">Ingresa tu número de teléfono para ver tus citas.</p>
                        <form id="login-form">
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Tu Número de Teléfono</label>
                                <div class="input-group">
                                    <select class="form-select" id="country_code" name="country_code" style="max-width: 120px;" required></select>
                                    <input type="tel" class="form-control" id="telefono" placeholder="Ej: 4121234567" required>
                                </div>
                            </div>
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary">Entrar</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <a href="#" id="btn-register-direct">¿Eres nuevo? Regístrate aquí</a>
                        </div>
                        <hr>
                        <div class="text-center mt-2">
                            <a href="index.php" class="text-muted"><small>Volver al Inicio</small></a>
                        </div>
                        <div class="text-center mt-2">
                            <a href="javascript:window.location.reload()" class="text-muted"><small>Recargar Aplicación</small></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    try {
        const response = await fetch(`${API_URL}api_paises.php`);
        const paises = await response.json();
        const paisesOptions = paises.map(pais => `<option value="${pais.codigo_telefono}" ${pais.id_pais == 1 ? 'selected' : ''}>${pais.codigo_telefono}</option>`).join('');
        document.getElementById('country_code').innerHTML = paisesOptions;
    } catch (error) {
        document.getElementById('error-container').innerHTML = `<div class="alert alert-warning">No se pudieron cargar los códigos de país.</div>`;
    }

    document.getElementById('login-form').addEventListener('submit', (e) => handleLogin(e, context));
    
    // Listener específico para el enlace de registro que captura los datos del formulario
    document.getElementById('btn-register-direct').addEventListener('click', (e) => {
        e.preventDefault();
        const countryCode = document.getElementById('country_code').value;
        const telefono = document.getElementById('telefono').value.trim();
        context.renderView('register', { countryCode, telefono });
    });
}

/**
 * Maneja el envío del formulario de login del cliente.
 * @param {Event} e - El evento de submit.
 * @param {object} context - El contexto global de la aplicación.
 */
async function handleLogin(e, context) {
    e.preventDefault();
    const { state, API_URL, renderView, updateNavbar } = context;
    const errorContainer = document.getElementById('error-container');
    errorContainer.innerHTML = '';
    const submitButton = e.target.querySelector('button[type="submit"]');
    submitButton.disabled = true;

    const telefono = `${document.getElementById('country_code').value} ${document.getElementById('telefono').value.trim()}`;

    try {
        const response = await fetch(`${API_URL}api_cliente_login.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ numero_celular: telefono })
        });

        const data = await response.json();
        if (!response.ok) {
            if (response.status === 404) {
                renderView('confirm-register', { telefono: telefono, id_negocio: data.id_negocio });
                return;
            }
            throw new Error(data.error || 'Error desconocido');
        }

        state.clienteActual = data.client;
        await updateNavbar(context);
        renderView('dashboard');

    } catch (error) {
        errorContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    } finally {
        submitButton.disabled = false;
    }
}

/**
 * Renderiza la vista para confirmar si un cliente nuevo quiere registrarse.
 * @param {object} context - El contexto global.
 * @param {object} params - Parámetros, debe contener { telefono: '...', id_negocio: '...' }.
 */
export function renderConfirmacionRegistroView(context, params) {
    context.dom.appContainer.innerHTML = `
        <div class="row justify-content-center mt-5">
            <div class="col-md-7 col-lg-6">
                <div class="card text-center shadow-lg">
                    <div class="card-header bg-primary text-white"><h3>Cliente no Encontrado</h3></div>
                    <div class="card-body p-4">
                        <p class="lead">El número de teléfono <strong>${params.telefono}</strong> no está registrado.</p>
                        <p>¿Deseas registrarte como un nuevo cliente?</p>
                        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-4">
                            <button class="btn btn-primary btn-lg px-4 gap-3" id="btn-show-register-form">Sí, Registrarme</button>
                            <button class="btn btn-outline-secondary btn-lg px-4" data-view="login">No, Volver</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.getElementById('btn-show-register-form').addEventListener('click', () => {
        context.renderView('register', params);
    });
}

/**
 * Renderiza el formulario de registro para un nuevo cliente.
 * @param {object} context - El contexto global.
 * @param {object} params - Parámetros, debe contener { telefono: '...', id_negocio: '...' }.
 */
export async function renderRegistroView(context, params = {}) {
    const { dom, API_URL, renderView } = context;
    dom.appContainer.innerHTML = `<div class="text-center mt-5"><div class="spinner-border"></div></div>`;

    // Lógica para pre-llenar y separar el teléfono y el código
    let preCountry = params.countryCode || '';
    let prePhone = params.telefono || '';

    // Si viene combinado (desde el flujo de error 404), intentamos separarlo
    if (!preCountry && prePhone.includes(' ')) {
        const parts = prePhone.split(' ');
        if (parts.length >= 2) {
            preCountry = parts[0];
            prePhone = parts.slice(1).join(' ');
        }
    }

    // Generar un código de seguridad aleatorio de 5 caracteres
    const securityCode = Math.random().toString(36).substring(2, 7).toUpperCase();

    try {
        const [paisesResponse, negociosResponse] = await Promise.all([
            fetch(`${API_URL}api_paises.php`),
            fetch(`${API_URL}api_negocios_lista_publica.php`)
        ]);
        const paises = await paisesResponse.json();
        const negocios = await negociosResponse.json();

        const paisesOptions = '<option value="" disabled selected>Seleccione un país...</option>' + paises.map(p => `<option value="${p.id_pais}">${p.nombre_pais}</option>`).join('');
        const phoneCountryOptions = paises.map(p => `<option value="${p.codigo_telefono}" ${p.codigo_telefono == preCountry || (!preCountry && p.id_pais == 1) ? 'selected' : ''}>${p.codigo_telefono}</option>`).join('');
        const negociosOptions = '<option value="" disabled selected>Seleccione un negocio...</option>' + negocios.map(n => `<option value="${n.id_negocio}" ${params.id_negocio == n.id_negocio ? 'selected' : ''}>${n.nombre_negocio}</option>`).join('');

        dom.appContainer.innerHTML = `
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header"><h3>Registro de Nuevo Cliente</h3></div>
                        <div class="card-body">
                            <div id="error-container-registro"></div>
                            <form id="registro-cliente-form">
                                <div class="mb-3">
                                    <label class="form-label">Teléfono Móvil</label>
                                    <div class="input-group">
                                        <select class="form-select" id="reg_country_code" style="max-width: 120px;">${phoneCountryOptions}</select>
                                        <input type="tel" class="form-control" id="reg_telefono" value="${prePhone}" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3"><label for="nombre_completo" class="form-label">Nombre Completo</label><input type="text" class="form-control" id="nombre_completo" required></div>
                                    <div class="col-md-6 mb-3"><label for="correo_electronico" class="form-label">Correo Electrónico</label><input type="email" class="form-control" id="correo_electronico" required></div>
                                </div>
                                <div class="mb-3"><label for="id_negocio" class="form-label">Negocio donde te registras</label><select class="form-select" id="id_negocio" required>${negociosOptions}</select></div>
                                <hr>
                                <div class="mb-3"><label for="direccion1" class="form-label">Dirección (Opcional)</label><input type="text" class="form-control" id="direccion1"></div>
                                <div class="mb-3"><label for="direccion2" class="form-label">Dirección 2 (Opcional)</label><input type="text" class="form-control" id="direccion2"></div>
                                <div class="row">
                                    <div class="col-md-8 mb-3"><label for="ciudad" class="form-label">Ciudad</label><input type="text" class="form-control" id="ciudad"></div>
                                    <div class="col-md-4 mb-3"><label for="zip_code" class="form-label">Código Postal</label><input type="text" class="form-control" id="zip_code"></div>
                                </div>
                                
                                <!-- Campo de Seguridad -->
                                <div class="mb-3">
                                    <label class="form-label">Código de Verificación</label>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light border rounded p-2 fw-bold text-center me-3 text-primary" style="letter-spacing: 3px; min-width: 100px; user-select: none; font-size: 1.1em;">${securityCode}</div>
                                        <input type="text" class="form-control" id="input_security_code" placeholder="Repite el código aquí" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3"><label for="id_pais" class="form-label">País</label><select class="form-select" id="id_pais">${paisesOptions}</select></div>
                                    <div class="col-md-6 mb-3"><label for="id_estado" class="form-label">Estado/Provincia</label><select class="form-select" id="id_estado" disabled><option>Seleccione un país...</option></select></div>
                                </div>
                                <div class="d-flex justify-content-between mt-4">
                                
                                <hr>
                                <h5 class="mt-3">Preferencias de Comunicación</h5>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="in_email" checked>
                                    <label class="form-check-label" for="in_email">Recibir correos electrónicos</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="in_sms" checked>
                                    <label class="form-check-label" for="in_sms">Recibir SMS</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="in_whatsapp" checked>
                                    <label class="form-check-label" for="in_whatsapp">Recibir WhatsApp</label>
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
            estadoSelect.disabled = true;
            estadoSelect.innerHTML = '<option>Cargando...</option>';
            if (paisSelect.value) {
                const response = await fetch(`${API_URL}api_estados.php?id_pais=${paisSelect.value}`);
                const estados = await response.json();
                estadoSelect.innerHTML = '<option value="">Seleccione un estado...</option>' + estados.map(e => `<option value="${e.id_estado}">${e.nombre_estado}</option>`).join('');
                estadoSelect.disabled = false;
            }
        });

        document.getElementById('registro-cliente-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;

            // Validar Código de Seguridad
            const userCode = document.getElementById('input_security_code').value.trim().toUpperCase();
            if (userCode !== securityCode) {
                alert('El código de verificación es incorrecto. Por favor verifícalo.');
                return;
            }

            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;

            const payload = {
                id_negocio: form.querySelector('#id_negocio').value,
                nombre_completo: form.querySelector('#nombre_completo').value,
                correo_electronico: form.querySelector('#correo_electronico').value,
                numero_celular: `${form.querySelector('#reg_country_code').value} ${form.querySelector('#reg_telefono').value.trim()}`,
                direccion1: form.querySelector('#direccion1').value,
                direccion2: form.querySelector('#direccion2').value,
                ciudad: form.querySelector('#ciudad').value,
                zip_code: form.querySelector('#zip_code').value,
                id_pais: form.querySelector('#id_pais').value,
                id_estado: form.querySelector('#id_estado').value,
                codigo_verificacion: userCode, // Enviamos el código para evitar errores si el backend lo espera
                in_email: form.querySelector('#in_email').checked,
                in_sms: form.querySelector('#in_sms').checked,
                in_whatsapp: form.querySelector('#in_whatsapp').checked
            };

            try {
                const response = await fetch(`${API_URL}api_cliente_registro.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.error);
                alert(data.message);
                renderView('login');
            } catch (error) {
                document.getElementById('error-container-registro').innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
            } finally {
                submitButton.disabled = false;
            }
        });

    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">Error al cargar el formulario de registro: ${error.message}</div>`;
    }
}

/**
 * Maneja el cierre de sesión del cliente.
 * @param {object} context - El contexto global.
 */
export async function handleLogout(context) {
    const { state, API_URL, renderView, updateNavbar } = context;
    try {
        await fetch(`${API_URL}api_cliente_logout.php`);
    } catch (error) {
        console.error("Error during logout, but proceeding with client-side logout:", error);
    } finally {
        state.clienteActual = null;
        state.negocioInfo = null;
        await updateNavbar(context);
        renderView('login');
    }
}