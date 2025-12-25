// js/owner_modules/auth.js
import { updateNavbar } from './ui.js'; // CORRECCIÓN: La ruta relativa './ui.js' es correcta dentro del mismo directorio. No se necesita cambio aquí, mi análisis anterior fue incorrecto.

export async function renderLoginView(context) {
    const { dom, API_URL } = context;


    document.title = `Acceso Propietario - ZApp Citas`;
    dom.navbarBrand.innerHTML = `App Propietario <span class="ms-2 fw-normal text-white-50" style="font-size: 0.8em;">Gestión de Citas</span>`;
    updateNavbar(context);

    dom.appContainer.innerHTML = `
        <div class="row justify-content-center mt-5">
            <div class="col-md-5 col-lg-4">

                <div class="card shadow-lg">
                    <div class="card-header text-center bg-primary text-white">
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
                                <button type="submit" class="btn btn-primary btn-lg">Entrar</button>
                            </div>
                            <div class="text-center mt-3">
                                <a href="#" id="forgot-password-link">¿Olvidó su contraseña?</a>
                            </div>
                            <div class="text-center mt-3">
                                <a href="#" data-view="start-register">¿No tienes cuenta? Registra tu negocio</a>
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

export async function renderStartRegistrationView(context) {
    const { dom, API_URL } = context;
    dom.appContainer.innerHTML = `
        <div class="row justify-content-center mt-5">
            <div class="col-md-5 col-lg-4">
                <div class="card shadow-lg">
                    <div class="card-header text-center bg-primary text-white">
                        <h3>Registrar Nuevo Negocio</h3>
                    </div>
                    <div class="card-body p-4">
                        <div id="error-container-start-reg"></div>
                        <p class="text-muted">Para comenzar, ingrese el número de teléfono principal de su negocio.</p>
                        <form id="start-registration-form">
                            <div class="mb-3">
                                <label for="telefono-reg" class="form-label">Teléfono del Negocio</label>
                                <div class="input-group">
                                    <select class="form-select" id="country_code_reg" style="max-width: 120px;" required></select>
                                    <input type="tel" class="form-control" id="telefono-reg" placeholder="Ej: 4121234567" required>
                                </div>
                            </div>
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary">Continuar Registro</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <a href="#" data-view="login">Volver al inicio de sesión</a>
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
        document.getElementById('country_code_reg').innerHTML = paisesOptions;
    } catch (error) {
        document.getElementById('error-container-start-reg').innerHTML = `<div class="alert alert-warning">No se pudieron cargar los códigos de país.</div>`;
    }

    document.getElementById('start-registration-form').addEventListener('submit', (e) => {
        e.preventDefault();
        const telefono = `${document.getElementById('country_code_reg').value} ${document.getElementById('telefono-reg').value.trim()}`;
        // La API de registro ya valida si el teléfono existe, así que podemos ir directo al formulario completo.
        renderOwnerRegistrationView(context, telefono);
    });
}

/**
 * Renderiza una vista informativa cuando el negocio no se encuentra.
 * @param {object} context - El objeto de contexto de la aplicación.
 * @param {string} telefono - El número de teléfono que se intentó usar.
 */
function renderBusinessNotFoundView(context, telefono) {
    const { dom } = context;
    dom.appContainer.innerHTML = `
        <div class="row justify-content-center mt-5">
            <div class="col-md-7 col-lg-6">
                <div class="card text-center shadow-lg">
                    <div class="card-header bg-primary text-white"><h3>Negocio no Encontrado</h3></div>
                    <div class="card-body p-4">
                        <p class="lead">El número de teléfono <strong>${telefono}</strong> no está registrado en nuestra plataforma.</p>
                        <p>¿Deseas registrarlo como un nuevo negocio?</p>
                        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-4">
                            <button class="btn btn-primary btn-lg px-4 gap-3" id="btn-show-register-form" data-telefono="${telefono}">Sí, Registrar Negocio</button>
                            <button class="btn btn-outline-secondary btn-lg px-4" data-view="login">No, Volver</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.getElementById('btn-show-register-form').addEventListener('click', (e) => {
        renderOwnerRegistrationView(context, e.currentTarget.dataset.telefono);

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
                    <div class="card-header text-center bg-primary text-white">
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

                                <button type="submit" class="btn btn-primary">Enviar Instrucciones</button>
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

/**
 * Renderiza el formulario de registro para un nuevo negocio y propietario.
 * @param {object} context - El objeto de contexto de la aplicación.
 * @param {string} telefono - El número de teléfono a registrar.
 */
async function renderOwnerRegistrationView(context, telefono) {
    const { dom, API_URL, renderView } = context;

    dom.appContainer.innerHTML = `<div class="text-center mt-5"><div class="spinner-border" role="status"></div><p>Cargando formulario de registro...</p></div>`;

    try {
        const [paisesResponse, categoriasResponse] = await Promise.all([
            fetch(`${API_URL}api_paises.php`),
            fetch(`${API_URL}api_categorias.php`) // Necesitamos una API para las categorías
        ]);
        const paises = await paisesResponse.json();
        const categorias = await categoriasResponse.json();

        // Generar un código de seguridad aleatorio de 5 caracteres
        const securityCode = Math.random().toString(36).substring(2, 7).toUpperCase();

        const paisesOptions = '<option value="" disabled selected>Seleccione un país...</option>' + paises.map(p => `<option value="${p.id_pais}">${p.nombre_pais}</option>`).join('');
        const categoriasOptions = '<option value="" disabled selected>Seleccione una categoría...</option>' + categorias.map(c => `<option value="${c.id_categoria}">${c.nombre_categoria}</option>`).join('');

        dom.appContainer.innerHTML = `
            <div class="row justify-content-center" >
                <div class="col-md-10 col-lg-8">
                    <div class="card">
                        <div class="card-header text-center bg-primary text-white"><h3>Registro de Nuevo Negocio</h3></div>
                        <div class="card-body">
                            <div id="error-container-registro"></div>
                            <form id="registro-owner-form">
                                <input type="hidden" id="telefono_negocio" value="${telefono}">
                                <div class="alert alert-info"><strong>Teléfono a registrar:</strong> ${telefono}</div>
                                
                                <h4>Datos del Negocio</h4>
                                <div class="row">
                                    <div class="col-md-8 mb-3"><label for="nombre_negocio" class="form-label">Nombre del Negocio</label><input type="text" class="form-control" id="nombre_negocio" required></div>
                                    <div class="col-md-4 mb-3"><label for="id_categoria_negocio" class="form-label">Categoría</label><select class="form-select" id="id_categoria_negocio" required>${categoriasOptions}</select></div>
                                </div>
                                <div class="mb-3"><label for="email_negocio" class="form-label">Email de Contacto del Negocio</label><input type="email" class="form-control" id="email_negocio" required></div>
                                
                                <hr>
                                <h4>Dirección del Negocio</h4>
                                <div class="mb-3"><label for="direccion1" class="form-label">Dirección Principal</label><input type="text" class="form-control" id="direccion1" required></div>
                                <div class="row">
                                    <div class="col-md-6 mb-3"><label for="id_pais" class="form-label">País</label><select class="form-select" id="id_pais" required>${paisesOptions}</select></div>
                                    <div class="col-md-6 mb-3"><label for="id_estado" class="form-label">Estado/Provincia</label><select class="form-select" id="id_estado" required disabled><option value="">Seleccione un país...</option></select></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-8 mb-3"><label for="ciudad" class="form-label">Ciudad</label><input type="text" class="form-control" id="ciudad" required></div>
                                    <div class="col-md-4 mb-3"><label for="zip_code" class="form-label">Código Postal</label><input type="text" class="form-control" id="zip_code"></div>
                                </div>

                                <hr>
                                <h4>Horario de Trabajo</h4>
                                <div class="mb-3"><label class="form-label">Días de Trabajo</label><div><div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="dias_trabajo[]" value="1" id="dia_1" checked><label class="form-check-label" for="dia_1">Lun</label></div><div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="dias_trabajo[]" value="2" id="dia_2" checked><label class="form-check-label" for="dia_2">Mar</label></div><div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="dias_trabajo[]" value="3" id="dia_3" checked><label class="form-check-label" for="dia_3">Mié</label></div><div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="dias_trabajo[]" value="4" id="dia_4" checked><label class="form-check-label" for="dia_4">Jue</label></div><div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="dias_trabajo[]" value="5" id="dia_5" checked><label class="form-check-label" for="dia_5">Vie</label></div><div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="dias_trabajo[]" value="6" id="dia_6"><label class="form-check-label" for="dia_6">Sáb</label></div><div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="dias_trabajo[]" value="7" id="dia_7"><label class="form-check-label" for="dia_7">Dom</label></div></div></div>
                                <div class="row">
                                    <div class="col-md-4 mb-3"><label for="hora_inicio" class="form-label">Hora de Inicio</label><input type="time" class="form-control" id="hora_inicio" value="08:00" required></div>
                                    <div class="col-md-4 mb-3"><label for="hora_cierre" class="form-label">Hora de Cierre</label><input type="time" class="form-control" id="hora_cierre" value="18:00" required></div>
                                    <div class="col-md-4 mb-3"><label for="intervalo_minutos" class="form-label">Intervalo (min)</label><input type="number" class="form-control" id="intervalo_minutos" value="30" required></div>
                                </div>

                                <hr>
                                <h4>Datos del Propietario</h4>
                                <div class="row">
                                    <div class="col-md-6 mb-3"><label for="nombre_usuario" class="form-label">Tu Nombre de Usuario</label><input type="text" class="form-control" id="nombre_usuario" required></div>
                                    <div class="col-md-6 mb-3"><label for="email_usuario" class="form-label">Tu Email de Acceso</label><input type="email" class="form-control" id="email_usuario" required></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3"><label for="password_usuario" class="form-label">Tu Contraseña</label><input type="password" class="form-control" id="password_usuario" required></div>
                                    <div class="col-md-6 mb-3"><label for="confirm_password" class="form-label">Confirmar Contraseña</label><input type="password" class="form-control" id="confirm_password" required></div>
                                </div>

                                <!-- Campo de Seguridad -->
                                <div class="mb-3">
                                    <label class="form-label">Código de Verificación</label>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light border rounded p-2 fw-bold text-center me-3 text-primary" style="letter-spacing: 3px; min-width: 100px; user-select: none; font-size: 1.1em;">${securityCode}</div>
                                        <input type="text" class="form-control" id="input_security_code" placeholder="Repite el código aquí" required>
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

            estadoSelect.disabled = true;
            estadoSelect.innerHTML = '<option value="">Cargando...</option>';
            if (idPais) {
                const response = await fetch(`${API_URL}api_estados.php?id_pais=${idPais}`);
                const estados = await response.json();
                estadoSelect.innerHTML = '<option value="" disabled selected>Seleccione un estado...</option>' + estados.map(e => `<option value="${e.id_estado}">${e.nombre_estado}</option>`).join('');
                estadoSelect.disabled = false;
            }

        });

        document.getElementById('registro-owner-form').addEventListener('submit', (e) => {
            e.preventDefault();
            const userCode = document.getElementById('input_security_code').value.trim().toUpperCase();
            if (userCode !== securityCode) {
                alert('El código de verificación es incorrecto. Por favor verifícalo.');
                return;
            }
            handleOwnerRegistration(e, context);
        });

    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">Error al cargar el formulario de registro: ${error.message}</div>`;
    }
}

async function handleOwnerRegistration(e, context) {
    e.preventDefault();
    const { API_URL, renderView } = context;
    const form = e.target;
    const errorContainer = document.getElementById('error-container-registro');
    const submitButton = form.querySelector('button[type="submit"]');
    errorContainer.innerHTML = '';

    const password = form.querySelector('#password_usuario').value;
    const confirmPassword = form.querySelector('#confirm_password').value;

    if (password !== confirmPassword) {
        errorContainer.innerHTML = `<div class="alert alert-danger">Las contraseñas no coinciden.</div>`;
        return;
    }

    const payload = {
        // Datos del negocio
        nombre_negocio: form.querySelector('#nombre_negocio').value,
        telefono_negocio: form.querySelector('#telefono_negocio').value,
        email_negocio: form.querySelector('#email_negocio').value,
        id_categoria_negocio: form.querySelector('#id_categoria_negocio').value,
        id_pais: form.querySelector('#id_pais').value,
        id_estado: form.querySelector('#id_estado').value,
        direccion1: form.querySelector('#direccion1').value,
        ciudad: form.querySelector('#ciudad').value,
        zip_code: form.querySelector('#zip_code').value,
        dias_trabajo: Array.from(form.querySelectorAll('input[name="dias_trabajo[]"]:checked')).map(cb => cb.value),
        hora_inicio: form.querySelector('#hora_inicio').value,
        hora_cierre: form.querySelector('#hora_cierre').value,
        intervalo_minutos: form.querySelector('#intervalo_minutos').value,
        // Datos del propietario
        nombre_usuario: form.querySelector('#nombre_usuario').value,
        email_usuario: form.querySelector('#email_usuario').value,
        password_usuario: password
    };

    submitButton.disabled = true;
    submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Registrando...`;

    try {
        const response = await fetch(`${API_URL}api_owner_registro.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);

        context.dom.appContainer.innerHTML = `<div class="alert alert-success"><h4>¡Registro Exitoso!</h4><p>${data.message}</p><p>Serás redirigido a la pantalla de inicio de sesión para que puedas acceder.</p></div>`;
        setTimeout(() => renderView('login'), 5000);

    } catch (error) {
        errorContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = 'Completar Registro';
    }
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
    const { state, API_URL, renderView } = context;
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
            let errorMessage = errorData.error || 'Error inesperado. Intente de nuevo.';
            
            // MEJORA: Usar el error_key estandarizado para mensajes más claros.
            if (errorData.error_key === 'login_error_business_not_found') {
                renderBusinessNotFoundView(context, payload.telefono);
                return; // Detenemos la ejecución para mostrar la nueva vista.
            } else if (errorData.error_key === 'login_error_credentials') {
                errorMessage = 'La contraseña es inválida.';
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