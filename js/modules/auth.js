// js/modules/auth.js
import { updateNavbar } from './ui.js';

export async function renderLoginView(context) {
    const { dom, API_URL } = context;

    document.title = `Acceso Propietario - ZApp Citas`;
    dom.navbarBrand.innerHTML = `App Propietario <span class="ms-2 fw-normal text-white-50" style="font-size: 0.8em;">Gestión de Citas</span>`;
    updateNavbar(context);

    dom.appContainer.innerHTML = `
        <div class="row justify-content-center mt-5">
            <div class="col-md-5 col-lg-4">
                <div class="card shadow-lg">
                    <div class="card-header text-center bg-success text-white">
                        <h3>Acceso Propietario</h3>
                    </div>
                    <div class="card-body p-4">
                        <div id="error-container" class="mb-3"></div>
                        <form id="login-form-simple">
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono del Negocio</label>
                                <div class="input-group">
                                    <select class="form-select" id="country_code" name="country_code" style="max-width: 120px;" required></select>
                                    <input type="tel" class="form-control" id="telefono" placeholder="Ej: 4121234567" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" required>
                            </div>
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-success btn-lg">Entrar</button>
                            </div>
                            <div class="text-center mt-3">
                                <a href="#" id="forgot-password-link">¿Olvidó su contraseña?</a>
                            </div>
                            <hr>
                            <div class="text-center mt-2">
                                <a href="index.php" class="text-muted"><small>Volver al Inicio</small></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Cargar y poblar el selector de países
    try {
        const response = await fetch(`${API_URL}api_paises.php`);
        const paises = await response.json();
        const paisesOptions = paises.map(pais => `<option value="${pais.codigo_telefono}" ${pais.id_pais == 1 ? 'selected' : ''}>${pais.codigo_telefono}</option>`).join('');
        document.getElementById('country_code').innerHTML = paisesOptions;
    } catch (error) {
        console.error("Error cargando códigos de país:", error);
        document.getElementById('error-container').innerHTML = `<div class="alert alert-warning">No se pudieron cargar los códigos de país.</div>`;
    }

    document.getElementById('login-form-simple').addEventListener('submit', (e) => handleLogin(e, context));
    document.getElementById('forgot-password-link').addEventListener('click', (e) => {
        e.preventDefault();
        renderForgotPasswordView(context);
    });
}

/**
 * Renderiza la vista para recuperar la contraseña.
 * @param {object} context - El objeto de contexto de la aplicación.
 */
async function renderForgotPasswordView(context) {
    const { dom, API_URL } = context;

    dom.appContainer.innerHTML = `
        <div class="row justify-content-center mt-5">
            <div class="col-md-5 col-lg-4">
                <div class="card shadow-lg">
                    <div class="card-header text-center bg-info text-dark">
                        <h3>Recuperar Contraseña</h3>
                    </div>
                    <div class="card-body p-4">
                        <div id="recovery-message-container" class="mb-3"></div>
                        <p class="text-muted">Ingrese el teléfono de su negocio. Si existe una cuenta, se enviarán las instrucciones de recuperación al correo del propietario.</p>
                        <form id="recovery-form">
                            <div class="mb-3">
                                <label for="telefono-recovery" class="form-label">Teléfono del Negocio</label>
                                <div class="input-group">
                                    <select class="form-select" id="country_code_recovery" name="country_code_recovery" style="max-width: 120px;" required></select>
                                    <input type="tel" class="form-control" id="telefono-recovery" placeholder="Ej: 4121234567" required>
                                </div>
                            </div>
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-info">Enviar Instrucciones</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <a href="#" id="back-to-login-link">Volver al inicio de sesión</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Cargar y poblar el selector de países
    try {
        const response = await fetch(`${API_URL}api_paises.php`);
        const paises = await response.json();
        const paisesOptions = paises.map(pais => `<option value="${pais.codigo_telefono}" ${pais.id_pais == 1 ? 'selected' : ''}>${pais.codigo_telefono}</option>`).join('');
        document.getElementById('country_code_recovery').innerHTML = paisesOptions;
    } catch (error) {
        console.error("Error cargando códigos de país:", error);
        document.getElementById('recovery-message-container').innerHTML = `<div class="alert alert-warning">No se pudieron cargar los códigos de país.</div>`;
    }

    document.getElementById('recovery-form').addEventListener('submit', (e) => handlePasswordRecovery(e, context));
    document.getElementById('back-to-login-link').addEventListener('click', (e) => {
        e.preventDefault();
        renderLoginView(context);
    });
}

async function handlePasswordRecovery(e, context) {
    e.preventDefault();
    const { API_URL } = context;
    const messageContainer = document.getElementById('recovery-message-container');
    const submitButton = e.target.querySelector('button[type="submit"]');
    const telefono = `${document.getElementById('country_code_recovery').value} ${document.getElementById('telefono-recovery').value.trim()}`;

    messageContainer.innerHTML = '';
    submitButton.disabled = true;

    try {
        const response = await fetch(`${API_URL}api_owner_recuperar_clave.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ telefono: telefono })
        });

        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error || 'No se pudo procesar la solicitud.');
        }

        // SOLUCIÓN: Mostrar un mensaje específico si el backend devuelve el correo.
        let successMessage = 'Si el teléfono está registrado, se han enviado las instrucciones de recuperación.';
        if (data.email_sent_to) {
            successMessage = `Se han enviado las instrucciones de recuperación al correo: <strong>${data.email_sent_to}</strong>`;
        }
        messageContainer.innerHTML = `<div class="alert alert-success">${successMessage}</div>`;
    } catch (error) {
        messageContainer.innerHTML = `<div class="alert alert-danger">Falla de conexión con el servidor.</div>`;
    } finally {
        submitButton.disabled = false;
    }
}

async function handleLogin(e, context) {
    e.preventDefault();
    const { state, API_URL } = context;
    const errorContainer = document.getElementById('error-container');
    errorContainer.innerHTML = '';
    const submitButton = e.target.querySelector('button[type="submit"]');

    const payload = {
        telefono: `${document.getElementById('country_code').value} ${document.getElementById('telefono').value.trim()}`,
        password: document.getElementById('password').value
    };
    submitButton.disabled = true;

    try {
        const response = await fetch(`${API_URL}api_owner_login.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        // SOLUCIÓN: Unificar todo el manejo de errores dentro del bloque 'try'.
        if (!response.ok) {
            // Si la respuesta no es OK, intentamos leer el error del JSON.
            const errorData = await response.json().catch(() => ({})); // Si no hay JSON, devuelve objeto vacío.
            let errorMessage = 'Error inesperado. Intente de nuevo.';
            
            switch (errorData.error_key) {
                case 'login_error_business_not_found':
                    errorMessage = 'El teléfono del negocio no fue encontrado.';
                    break;
                case 'login_error_credentials':
                    errorMessage = 'La contraseña es inválida.';
                    break;
                case 'login_error_fields_required':
                    errorMessage = 'El teléfono y la contraseña son obligatorios.';
                    break;
            }
            // Lanzamos un error con el mensaje correcto para que sea atrapado por el 'catch'.
            throw new Error(errorMessage);
        }

        // Si todo está bien, continuamos.
        const data = await response.json();
        state.ownerActual = data;
        updateNavbar(context);
        context.renderView('agenda');

    } catch (error) {
        // Este bloque 'catch' ahora atrapa tanto los errores de red como los de autenticación.
        // Si el error no tiene un mensaje específico, muestra el de falla de conexión.
        const displayMessage = error.message || 'Falla de conexión con la Base de Datos o el servidor.';
        errorContainer.innerHTML = `<div class="alert alert-danger">${displayMessage}</div>`;
    } finally {
        submitButton.disabled = false;
    }
}

export async function handleLogout(context) {
    const { state, API_URL } = context;
    try {
        await fetch(`${API_URL}api_owner_logout.php`);
    } catch (error) {
        console.error("Error during logout, but proceeding with client-side logout:", error);
    } finally {
        state.ownerActual = null;
        if (state.clockInterval) {
            clearInterval(state.clockInterval);
            state.clockInterval = null;
        }
        updateNavbar(context);
        renderLoginView(context);
    }
}