// js/modules/auth.js
import { updateNavbar } from './ui.js';

/**
 * Renderiza la vista de Login.
 * @param {object} context - El objeto de contexto de la aplicación.
 */
export function renderLoginView(context) {
    const { dom, T, API_URL, state } = context;

    // Actualizar título y menú para la vista de login
    const mainTitle = T.spa_owner_app_title || "Owner App";
    const subTitle = T.spa_owner_app_subtitle || "Business Appointment Management";
    document.title = `${mainTitle} - ${subTitle}`;
    dom.navbarBrand.innerHTML = `${mainTitle} <span class="ms-2 fw-normal text-white-50" style="font-size: 0.8em;">${subTitle}</span>`;
    updateNavbar(context); // Llama a updateNavbar para mostrar el menú de idioma

    // Cargar datos necesarios (países) y luego renderizar el formulario
    fetch(`${API_URL}api_paises.php`)
        .then(response => {
            if (!response.ok) throw new Error('Error al cargar países.');
            return response.json();
        })
        .then(paises => {
            const paisesOptions = paises.map(pais => 
                `<option value="${pais.codigo_telefono}" ${pais.id_pais == 1 ? 'selected' : ''}>${pais.nombre_pais} (${pais.codigo_telefono})</option>`
            ).join('');

            dom.appContainer.innerHTML = `
                <div class="row justify-content-center">
                    <div class="col-md-5 col-lg-4">
                        <div class="card shadow">
                            <div class="card-header text-center bg-primary text-white"><h3>${T.spa_owner_welcome || "Owner Access"}</h3></div>
                            <div class="card-body p-4">
                                <div id="error-container"></div>
                                <form id="owner-login-form">
                                    <div class="mb-3">
                                        <label for="telefono" class="form-label">${T.spa_owner_business_phone || "Business Phone"}</label>
                                        <div class="input-group">
                                            <select class="form-select" id="country_code" name="country_code" style="max-width: 150px;" required>${paisesOptions}</select>
                                            <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="${T.phone_placeholder || 'Ej: 4121234567'}" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">${T.spa_owner_your_password || "Your Password"}</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    <div class="d-grid mt-3">
                                        <button type="submit" class="btn btn-primary btn-lg">${T.spa_owner_login_button || "Login"}</button>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="#" id="forgot-password-link">${T.spa_owner_forgot_password || "Forgot your password?"}</a>
                                    </div>
                                    <hr>
                                    <div class="text-center mt-2">
                                        <a href="index.php?lang=${state.currentLang}" class="text-muted"><small>${T.spa_owner_back_to_home || "Back to Home"}</small></a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('owner-login-form').addEventListener('submit', (e) => handleLogin(e, context));
            document.getElementById('forgot-password-link').addEventListener('click', (e) => {
                e.preventDefault();
                alert(T.feature_in_construction || 'Feature in construction.');
            });
        })
        .catch(error => {
            dom.appContainer.innerHTML = `<div class="alert alert-danger">${T.operation_error || 'Operation Error'}: ${error.message}</div>`;
        });
}

/**
 * Maneja el envío del formulario de login.
 * @param {Event} e - El evento de submit.
 * @param {object} context - El objeto de contexto de la aplicación.
 */
async function handleLogin(e, context) {
    e.preventDefault();
    const { state, API_URL, T } = context; // CORRECCIÓN: No desestructurar renderView
    const errorContainer = document.getElementById('error-container');
    errorContainer.innerHTML = '';
    const submitButton = e.target.querySelector('button[type="submit"]');

    const payload = {
        telefono: `${document.getElementById('country_code').value.trim()} ${document.getElementById('telefono').value.trim()}`,
        password: document.getElementById('password').value
    };
    submitButton.disabled = true;
    try {
        const response = await fetch(`${API_URL}api_owner_login.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);

        state.ownerActual = data;
        updateNavbar(context);
        context.renderView('agenda'); // CORRECCIÓN: Llamar a través de context

    } catch (error) {
        errorContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    } finally {
        submitButton.disabled = false;
    }
}

/**
 * Maneja el cierre de sesión.
 * @param {object} context - El objeto de contexto de la aplicación.
 */
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