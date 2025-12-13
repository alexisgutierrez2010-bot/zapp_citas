// js/modules/perfil.js

/**
 * Renderiza la vista "Mi Perfil".
 * @param {object} context - El objeto de contexto de la aplicación.
 */
export async function renderPerfilView(context) {
    const { dom, T, API_URL } = context;
    dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"></div></div>`;

    try {
        const response = await fetch(`${API_URL}api_owner_perfil_detalle.php`);
        if (!response.ok) throw new Error(T.profile_error_loading || 'Could not load your profile.');
        const usuario = await response.json();

        dom.appContainer.innerHTML = `
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header"><h3>${T.profile_title || 'My Profile'}</h3></div>
                        <div class="card-body">
                            <div id="error-container-perfil"></div>
                            <form id="perfil-form">
                                <div class="mb-3"><label for="nombre_usuario_perfil" class="form-label">${T.users_form_username || 'Username'}</label><input type="text" class="form-control" id="nombre_usuario_perfil" value="${usuario.nombre_usuario}" required></div>
                                <div class="mb-3"><label for="correo_electronico_perfil" class="form-label">${T.users_form_email || 'Email'}</label><input type="email" class="form-control" id="correo_electronico_perfil" value="${usuario.correo_electronico}" required></div>
                                <hr><h5 class="mt-4">${T.profile_change_password || 'Change Password'} (${T.optional || 'Optional'})</h5>
                                <div class="mb-3"><label for="current_password" class="form-label">${T.profile_current_password || 'Current Password'}</label><input type="password" class="form-control" id="current_password" placeholder="${T.profile_current_password_placeholder || 'Enter your current password to change it'}"></div>
                                <div class="mb-3"><label for="new_password" class="form-label">${T.users_form_new_password || 'New Password'}</label><input type="password" class="form-control" id="new_password"></div>
                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <button type="button" class="btn btn-secondary" id="btn-cancelar-perfil">${T.cancel || 'Cancel'}</button>
                                    <button type="submit" class="btn btn-primary">${T.users_form_update || 'Update User'}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('btn-cancelar-perfil').addEventListener('click', () => context.renderView('agenda'));
        document.getElementById('perfil-form').addEventListener('submit', (e) => handleUpdatePerfil(e, context));

    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

async function handleUpdatePerfil(e, context) {
    e.preventDefault();
    const { API_URL } = context;
    const errorContainer = document.getElementById('error-container-perfil');
    errorContainer.innerHTML = '';

    const payload = {
        nombre_usuario: document.getElementById('nombre_usuario_perfil').value,
        correo_electronico: document.getElementById('correo_electronico_perfil').value,
        current_password: document.getElementById('current_password').value,
        new_password: document.getElementById('new_password').value,
    };

    try {
        const response = await fetch(`${API_URL}api_owner_perfil_actualizar.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);

        errorContainer.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
        document.getElementById('current_password').value = '';
        document.getElementById('new_password').value = '';
    } catch (error) {
        errorContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}