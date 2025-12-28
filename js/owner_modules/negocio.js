// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
// js/modules/negocio.js

/**
 * Renderiza la vista "Mi Negocio".
 * @param {object} context - El objeto de contexto de la aplicación.
 */
export async function renderNegocioView(context) {
    const { dom, API_URL } = context;
    dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"></div></div>`;

    try {
        const [negocioResponse, paisesResponse] = await Promise.all([
            fetch(`${API_URL}api_owner_negocio_get.php`),
            fetch(`${API_URL}api_paises.php`)
        ]);

        if (!negocioResponse.ok) {
            const errorData = await negocioResponse.json().catch(() => ({}));
            throw new Error(errorData.error || 'No se pudo cargar la configuración del negocio.');
        }
        
        const negocio = await negocioResponse.json();
        const paises = await paisesResponse.json();

        const diasSemana = { '1': 'Lun', '2': 'Mar', '3': 'Mié', '4': 'Jue', '5': 'Vie', '6': 'Sáb', '7': 'Dom' };
        const diasTrabajoActivos = negocio.dias_trabajo ? negocio.dias_trabajo.split(',') : [];
        const diasTrabajoCheckboxes = Object.entries(diasSemana).map(([num, dia]) => `
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="dias_trabajo[]" value="${num}" id="dia_${num}" ${diasTrabajoActivos.includes(num) ? 'checked' : ''}>
                <label class="form-check-label" for="dia_${num}">${dia}</label>
            </div>
        `).join('');

        const paisesOptions = paises.map(pais => 
            `<option value="${pais.id_pais}" ${negocio.id_pais == pais.id_pais ? 'selected' : ''}>${pais.nombre_pais}</option>`
        ).join('');

        dom.appContainer.innerHTML = `
            <div class="row justify-content-center">
                <div class="col-md-10 col-lg-8">
                    <div class="card">
                        <div class="card-header"><h3>Configuración de Mi Negocio</h3></div>
                        <div class="card-body">
                            <div id="error-container-negocio"></div>
                            <form id="negocio-form">
                                <div class="mb-3"><label for="nombre_negocio" class="form-label">Nombre del Negocio</label><input type="text" class="form-control" id="nombre_negocio" value="${negocio.nombre_negocio || ''}"></div>
                                <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Teléfono</label><input type="tel" class="form-control" value="${negocio.telefono || ''}" readonly disabled><small class="form-text text-muted">El teléfono no puede ser modificado.</small></div><div class="col-md-6 mb-3"><label for="email" class="form-label">Email</label><input type="email" class="form-control" id="email" value="${negocio.email || ''}"></div></div>
                                <hr><h5 class="mt-4">Dirección</h5>
                                <div class="mb-3"><label for="direccion1" class="form-label">Dirección Principal</label><input type="text" class="form-control" id="direccion1" value="${negocio.direccion1 || ''}" placeholder="Calle, número, etc."></div>
                                <div class="mb-3"><label for="direccion2" class="form-label">Dirección Secundaria (Opcional)</label><input type="text" class="form-control" id="direccion2" value="${negocio.direccion2 || ''}" placeholder="Apartamento, oficina, etc."></div>
                                <div class="row">
                                    <div class="col-md-6 mb-3"><label for="ciudad" class="form-label">Ciudad</label><input type="text" class="form-control" id="ciudad" value="${negocio.ciudad || ''}"></div>
                                    <div class="col-md-6 mb-3"><label for="zip_code" class="form-label">Código Postal</label><input type="text" class="form-control" id="zip_code" value="${negocio.zip_code || ''}"></div>
                                </div>
                                <div class="row"><div class="col-md-6 mb-3"><label for="id_pais" class="form-label">País</label><select class="form-select" id="id_pais" required>${paisesOptions}</select></div><div class="col-md-6 mb-3"><label for="id_estado" class="form-label">Estado/Provincia</label><select class="form-select" id="id_estado" required disabled><option>Cargando...</option></select></div></div>
                                <hr><h5 class="mt-4">Horario de Trabajo</h5>
                                <div class="mb-3"><label class="form-label">Días de Trabajo</label><div>${diasTrabajoCheckboxes}</div></div>
                                <div class="row"><div class="col-md-4 mb-3"><label for="hora_inicio" class="form-label">Hora de Inicio</label><input type="time" class="form-control" id="hora_inicio" value="${negocio.hora_inicio || ''}" required></div><div class="col-md-4 mb-3"><label for="hora_cierre" class="form-label">Hora de Cierre</label><input type="time" class="form-control" id="hora_cierre" value="${negocio.hora_cierre || ''}" required></div><div class="col-md-4 mb-3"><label for="intervalo_minutos" class="form-label">Intervalo (minutos)</label><input type="number" class="form-control" id="intervalo_minutos" value="${negocio.intervalo_minutos || 30}" required></div></div>
                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <button type="button" class="btn btn-secondary" id="btn-cancelar-negocio">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Guardar Configuración</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const paisSelect = document.getElementById('id_pais');
        const estadoSelect = document.getElementById('id_estado');
        
        async function cargarEstados(idPais, idEstadoSeleccionado) {
            const response = await fetch(`${API_URL}api_estados.php?id_pais=${idPais}`);
            const estados = await response.json().catch(() => []);
            estadoSelect.innerHTML = `<option value="">Seleccione un estado...</option>`;
            estados.forEach(estado => {
                estadoSelect.innerHTML += `<option value="${estado.id_estado}" ${estado.id_estado == idEstadoSeleccionado ? 'selected' : ''}>${estado.nombre_estado}</option>`;
            });
            estadoSelect.disabled = false;
        }

        paisSelect.addEventListener('change', () => cargarEstados(paisSelect.value));
        await cargarEstados(paisSelect.value, negocio.id_estado);

        document.getElementById('btn-cancelar-negocio').addEventListener('click', () => context.renderView('agenda'));
        document.getElementById('negocio-form').addEventListener('submit', (e) => handleUpdateNegocio(e, context));

    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

async function handleUpdateNegocio(e, context) {
    e.preventDefault();
    const { dom, API_URL } = context;
    const errorContainer = document.getElementById('error-container-negocio');
    const submitButton = e.target.querySelector('button[type="submit"]');
    errorContainer.innerHTML = '';

    const payload = {
        nombre_negocio: document.getElementById('nombre_negocio').value,
        email: document.getElementById('email').value,
        dias_trabajo: Array.from(document.querySelectorAll('input[name="dias_trabajo[]"]:checked')).map(cb => cb.value),
        hora_inicio: document.getElementById('hora_inicio').value,
        hora_cierre: document.getElementById('hora_cierre').value,
        intervalo_minutos: document.getElementById('intervalo_minutos').value,
        direccion1: document.getElementById('direccion1').value,
        direccion2: document.getElementById('direccion2').value,
        ciudad: document.getElementById('ciudad').value,
        zip_code: document.getElementById('zip_code').value,
        id_pais: document.getElementById('id_pais').value,
        id_estado: document.getElementById('id_estado').value,
    };

    submitButton.disabled = true;
    submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...`;

    try {
        const response = await fetch(`${API_URL}api_owner_negocio_update.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'No se pudo guardar la configuración.');
        
        errorContainer.innerHTML = `<div class="alert alert-success">${data.message || 'Configuración guardada con éxito.'}</div>`;
        dom.navbarBrand.textContent = payload.nombre_negocio;
    } catch (error) {
        errorContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = 'Guardar Cambios';
    }
}