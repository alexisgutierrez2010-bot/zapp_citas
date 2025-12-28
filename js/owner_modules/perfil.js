// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
// js/modules/perfil.js

/**
 * Renderiza la vista "Mi Perfil".
 * @param {object} context - El objeto de contexto de la aplicación.
 */
export async function renderPerfilView(context) {
    const { dom, state } = context;
    const owner = state.ownerActual;

    dom.appContainer.innerHTML = `
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-header"><h3>Mi Perfil</h3></div>
                    <div class="card-body">
                        <div id="error-container-perfil"></div>
                        <form id="perfil-form">
                            <div class="row align-items-center">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">${owner.nombre_usuario}</label>
                                    <div class="text-muted small">Propietario</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="password_actual" class="form-label">Contraseña Actual</label>
                                    <input type="password" class="form-control" id="password_actual" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nueva_password" class="form-label">Nueva Contraseña</label>
                                    <input type="password" class="form-control" id="nueva_password" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirmar_password" class="form-label">Confirmar Nueva Contraseña</label>
                                    <input type="password" class="form-control" id="confirmar_password" required>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-3 gap-2">
                                <button type="button" class="btn btn-secondary" id="btn-cancelar-perfil">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>`;

    document.getElementById('perfil-form').addEventListener('submit', (e) => handleUpdatePerfil(e, context));
    document.getElementById('btn-cancelar-perfil').addEventListener('click', () => context.renderView('agenda'));
}

async function handleUpdatePerfil(e, context) {
    e.preventDefault();
    const { API_URL } = context;
    const errorContainer = document.getElementById('error-container-perfil');
    const submitButton = e.target.querySelector('button[type="submit"]');
    errorContainer.innerHTML = '';

    const passwordActual = document.getElementById('password_actual').value;
    const nuevaPassword = document.getElementById('nueva_password').value;
    const confirmarPassword = document.getElementById('confirmar_password').value;

    if (nuevaPassword !== confirmarPassword) {
        errorContainer.innerHTML = `<div class="alert alert-danger">Las contraseñas nuevas no coinciden.</div>`;
        return;
    }

    submitButton.disabled = true;
    submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...`;

    const payload = {
        password_actual: passwordActual,
        nueva_password: nuevaPassword,
    };

    try {
        const response = await fetch(`${API_URL}api_owner_password_update.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'No se pudo actualizar la contraseña.');

        errorContainer.innerHTML = `<div class="alert alert-success">${data.message || 'Contraseña actualizada con éxito.'}</div>`;
        document.getElementById('perfil-form').reset();
    } catch (error) {
        errorContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = 'Guardar Cambios';
    }
}