// js/client_modules/profile.js
// Módulo de perfil para la App del Cliente
/**
 * Renderiza la vista del perfil del cliente.
 * @param {object} context - El contexto global de la aplicación.
 */
export async function renderProfileView(context) {
    context.dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border"></div></div>`;

    try {
        // 1. Cargar datos del perfil del cliente
        const profileResponse = await fetch(`${context.API_URL}api_cliente_perfil.php?id_cliente=${context.state.clienteActual.id_cliente}`);
        if (!profileResponse.ok) throw new Error('No se pudo cargar el perfil.');
        const profileData = await profileResponse.json();

        // 2. Cargar lista de países
        const paisesResponse = await fetch(`${context.API_URL}api_paises.php`);
        const paises = await paisesResponse.json();

        // --- LÓGICA PARA SEPARAR CÓDIGO DE PAÍS Y NÚMERO ---
        let currentCountryCode = '';
        let currentPhoneNumber = profileData.numero_celular || '';
        if (profileData.numero_celular && profileData.numero_celular.includes(' ')) {
            const parts = profileData.numero_celular.split(' ');
            if (parts.length > 1 && parts[0].startsWith('+')) {
                currentCountryCode = parts[0];
                currentPhoneNumber = parts.slice(1).join(' ');
            }
        } else if (paises.length > 0) {
            // Si no hay código, se asigna uno por defecto (el del primer país de la lista)
            currentCountryCode = paises.find(p => p.id_pais == profileData.id_pais)?.codigo_telefono || paises[0].codigo_telefono;
        }

        // 3. Construir el HTML del formulario
        const paisesOptions = paises.map(p => `<option value="${p.id_pais}" ${profileData.id_pais == p.id_pais ? 'selected' : ''}>${p.nombre_pais}</option>`).join('');

        context.dom.appContainer.innerHTML = `
            <h3>Mi Perfil</h3>
            <form id="profile-form">
                <div class="card">
                    <div class="card-header">Mis Datos Personales</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre_completo" class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" id="nombre_completo" value="${profileData.nombre_completo || ''}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="correo_electronico" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="correo_electronico" value="${profileData.correo_electronico || ''}" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="numero_celular" class="form-label">Número de Celular (para Login y Notificaciones)</label>
                                <div class="input-group">
                                    <select class="form-select" id="country_code_profile" style="max-width: 120px;">${paises.map(p => `<option value="${p.codigo_telefono}" ${p.codigo_telefono == currentCountryCode ? 'selected' : ''}>${p.codigo_telefono}</option>`).join('')}</select>
                                    <input type="tel" class="form-control" id="numero_celular" value="${currentPhoneNumber}" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">Mi Dirección</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="direccion1" class="form-label">Dirección 1</label>
                            <input type="text" class="form-control" id="direccion1" value="${profileData.direccion1 || ''}">
                        </div>
                        <div class="mb-3">
                            <label for="direccion2" class="form-label">Dirección 2 (Opcional)</label>
                            <input type="text" class="form-control" id="direccion2" value="${profileData.direccion2 || ''}">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="id_pais" class="form-label">País</label>
                                <select class="form-select" id="id_pais" required>
                                    <option value="">Seleccione un país...</option>
                                    ${paisesOptions}
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="id_estado" class="form-label">Estado / Provincia</label>
                                <select class="form-select" id="id_estado" required>
                                    <option value="">Seleccione un país primero...</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ciudad" class="form-label">Ciudad</label>
                                <input type="text" class="form-control" id="ciudad" value="${profileData.ciudad || ''}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="zip_code" class="form-label">Código Postal</label>
                                <input type="text" class="form-control" id="zip_code" value="${profileData.zip_code || ''}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">Preferencias de Comunicación</div>
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="in_email" ${profileData.in_email == 1 ? 'checked' : ''}>
                            <label class="form-check-label" for="in_email">
                                Deseo recibir notificaciones y recordatorios por correo electrónico.
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="in_sms" ${profileData.in_sms == 1 ? 'checked' : ''}>
                            <label class="form-check-label" for="in_sms">
                                Deseo recibir notificaciones y recordatorios por SMS (pueden aplicarse cargos).
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="in_whatsapp" ${profileData.in_whatsapp == 1 ? 'checked' : ''}>
                            <label class="form-check-label" for="in_whatsapp">
                                Deseo recibir notificaciones por WhatsApp.
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        `;

        // 4. Lógica para cargar estados dinámicamente
        const paisSelect = document.getElementById('id_pais');
        const estadoSelect = document.getElementById('id_estado');

        const cargarEstados = async (idPais, idEstadoSeleccionado = null) => {
            if (!idPais) {
                estadoSelect.innerHTML = '<option value="">Seleccione un país primero...</option>';
                return;
            }
            estadoSelect.innerHTML = '<option value="">Cargando...</option>';
            const response = await fetch(`${context.API_URL}api_estados.php?id_pais=${idPais}`);
            const estados = await response.json();
            estadoSelect.innerHTML = estados.map(e => `<option value="${e.id_estado}" ${idEstadoSeleccionado == e.id_estado ? 'selected' : ''}>${e.nombre_estado}</option>`).join('');
        };

        paisSelect.addEventListener('change', () => cargarEstados(paisSelect.value));
        if (profileData.id_pais) {
            cargarEstados(profileData.id_pais, profileData.id_estado);
        }

        // 5. Lógica para enviar el formulario
        document.getElementById('profile-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Guardando...`;

            const payload = {
                id_cliente: context.state.clienteActual.id_cliente,
                nombre_completo: document.getElementById('nombre_completo').value,
                correo_electronico: document.getElementById('correo_electronico').value,
                numero_celular: `${document.getElementById('country_code_profile').value} ${document.getElementById('numero_celular').value.trim()}`,
                direccion1: document.getElementById('direccion1').value,
                direccion2: document.getElementById('direccion2').value,
                id_pais: document.getElementById('id_pais').value,
                id_estado: document.getElementById('id_estado').value,
                ciudad: document.getElementById('ciudad').value,
                zip_code: document.getElementById('zip_code').value,
                in_email: document.getElementById('in_email').checked,
                in_sms: document.getElementById('in_sms').checked,
                in_whatsapp: document.getElementById('in_whatsapp').checked,
            };

            try {
                const response = await fetch(`${context.API_URL}api_cliente_perfil.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.error);

                alert(result.message);
                context.renderView('dashboard'); // Volver al dashboard
            } catch (error) {
                alert(`Error al actualizar el perfil: ${error.message}`);
                btn.disabled = false;
                btn.innerHTML = 'Guardar Cambios';
            }
        });

    } catch (error) {
        context.dom.appContainer.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
    }
}
