// js/modules/citas.js

/**
 * Renderiza la vista para crear una nueva cita.
 * @param {object} context - El objeto de contexto de la aplicación.
 * @param {object} [prefillData={}] - Datos para pre-rellenar el formulario (ej. fecha).
 */
export async function renderCrearCitaView(context, prefillData = {}) {
    const { dom, API_URL } = context;
    dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"></div></div>`;

    try {
        // Cargar clientes y servicios en paralelo para optimizar
        const [clientesResponse, serviciosResponse] = await Promise.all([
            fetch(`${API_URL}api_owner_clientes.php`),
            fetch(`${API_URL}api_owner_servicios.php`)
        ]);

        if (!clientesResponse.ok || !serviciosResponse.ok) {
            throw new Error('No se pudieron cargar los datos necesarios para agendar.');
        }

        const clientesData = await clientesResponse.json();
        const serviciosData = await serviciosResponse.json();

        // FILTRADO: Mostrar solo clientes y servicios activos.
        const clientesActivos = (clientesData.clientes || []).filter(c => c.activo == 1);
        const serviciosActivos = (serviciosData.servicios || []).filter(s => s.activo == 1);

        // SOLUCIÓN: Añadir una opción por defecto deshabilitada para forzar la selección del usuario.
        const clientesOptions = '<option value="" disabled selected>Seleccione un cliente...</option>' +
            clientesActivos.map(c => `<option value="${c.id_cliente}">${c.nombre_completo}</option>`).join('');
        const serviciosOptions = '<option value="" disabled selected>Seleccione un servicio...</option>' +
            serviciosActivos.map(s => `<option value="${s.id_servicio}">${s.nombre_servicio}</option>`).join('');

        // Formatear fecha y hora pre-rellenadas si existen
        const ahora = new Date();
        let fechaPrefill = prefillData.fecha || ahora.toISOString().split('T')[0];
        let horaPrefill = prefillData.hora || ahora.toTimeString().substring(0, 5);

        dom.appContainer.innerHTML = `
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header"><h3>Agendar Nueva Cita</h3></div>
                        <div class="card-body">
                            <div id="error-container-cita"></div>
                            <form id="crear-cita-form">
                                <div class="mb-3">
                                    <label for="id_cliente" class="form-label">Cliente</label>
                                    <select id="id_cliente" class="form-select" required>${clientesOptions}</select>
                                </div>
                                <!-- INICIO: Selector de Tipo de Cita -->
                                <div class="mb-3">
                                    <label for="tipo_cita" class="form-label">Tipo de Cita</label>
                                    <select id="tipo_cita" class="form-select">
                                        <option value="Servicio" selected>Servicio</option>
                                        <option value="Reunion">Reunión</option>
                                    </select>
                                </div>
                                <!-- FIN: Selector de Tipo de Cita -->

                                <!-- Campo para Servicio (se oculta para Reunión) -->
                                <div class="mb-3" id="campo-servicio">
                                    <label for="id_servicio" class="form-label">Servicio</label>
                                    <select id="id_servicio" class="form-select">${serviciosOptions}</select>
                                </div>

                                <!-- Campo para Invitados (se muestra para Reunión) -->
                                <div class="mb-3" id="campo-invitados" style="display: none;">
                                    <label class="form-label">Añadir Invitados</label>
                                    <div class="input-group mb-2">
                                        <input type="text" id="nombre_invitado" class="form-control" placeholder="Nombre">
                                        <input type="email" id="email_invitado" class="form-control" placeholder="Correo">
                                        <input type="tel" id="telefono_invitado" class="form-control" placeholder="Teléfono (Opcional)">
                                        <div id="invitado-actions-container">
                                            <button class="btn btn-outline-secondary" type="button" id="btn-add-invitado">Añadir</button>
                                            <button class="btn btn-success" type="button" id="btn-update-invitado" style="display: none;">Actualizar</button>
                                            <button class="btn btn-secondary" type="button" id="btn-cancel-update-invitado" style="display: none;">Cancelar</button>
                                        </div>
                                    </div>
                                    <div id="lista-invitados-container">
                                        <ul class="list-group" id="lista-invitados">
                                            <!-- Los invitados añadidos aparecerán aquí -->
                                        </ul>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="fecha_cita" class="form-label">Fecha</label>
                                        <input type="date" id="fecha_cita" class="form-control" value="${fechaPrefill}" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="hora_cita" class="form-label">Hora</label>
                                        <!-- CAMBIO: Ahora es un select que se llenará dinámicamente -->
                                        <select id="hora_cita" class="form-select" required><option value="">Seleccione una fecha primero...</option></select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="descripcion_trabajo" class="form-label">Notas / Asunto de la Reunión</label>
                                    <textarea id="descripcion_trabajo" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="notificar_cliente" checked>
                                    <label class="form-check-label" for="notificar_cliente">
                                        Notificar al cliente por correo
                                    </label>
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary" id="btn-cancelar-crear-cita">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Guardar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // --- INICIO DE LÓGICA DE DISPONIBILIDAD ---
        const fechaInput = document.getElementById('fecha_cita');
        const horaSelect = document.getElementById('hora_cita');

        async function actualizarHorasDisponibles() {
            const fechaSeleccionada = fechaInput.value;
            if (!fechaSeleccionada) return;

            horaSelect.innerHTML = '<option value="">Cargando horas...</option>';
            horaSelect.disabled = true;

            try {
                const response = await fetch(`${API_URL}api_owner_available_slots.php?fecha=${fechaSeleccionada}`);
                const slots = await response.json();
                horaSelect.innerHTML = slots.length > 0 ? slots.map(slot => `<option value="${slot}">${slot}</option>`).join('') : '<option value="">No hay horas disponibles</option>';
            } catch (error) {
                horaSelect.innerHTML = '<option value="">Error al cargar horas</option>';
            } finally {
                horaSelect.disabled = false;
            }
        }

        // Listeners del formulario
        document.getElementById('btn-cancelar-crear-cita').addEventListener('click', () => context.renderView('agenda'));
        document.getElementById('crear-cita-form').addEventListener('submit', (e) => handleCrearCita(e, context));
        
        // Listener para cambiar campos según el tipo de cita
        document.getElementById('tipo_cita').addEventListener('change', (e) => {
            const esReunion = e.target.value === 'Reunion';
            document.getElementById('campo-servicio').style.display = esReunion ? 'none' : 'block';
            document.getElementById('campo-invitados').style.display = esReunion ? 'block' : 'none';
            // Hacer el servicio no-requerido si es reunión y viceversa
            document.getElementById('id_servicio').required = !esReunion;
            // Cambiar el label de las notas
            const notesLabel = document.querySelector('label[for="descripcion_trabajo"]');
            if (esReunion) {
                notesLabel.textContent = 'Asunto de la Reunión';
            } else {
                notesLabel.textContent = 'Notas (Opcional)';
            }
        });

        // Listener para actualizar las horas cuando cambia la fecha
        fechaInput.addEventListener('change', actualizarHorasDisponibles);
        // Carga inicial de horas para la fecha pre-seleccionada
        actualizarHorasDisponibles();

        // --- LÓGICA PARA LA NUEVA INTERFAZ DE INVITADOS ---
        const btnAddInvitado = document.getElementById('btn-add-invitado');
        const nombreInvitadoInput = document.getElementById('nombre_invitado');
        const emailInvitadoInput = document.getElementById('email_invitado');
        const telefonoInvitadoInput = document.getElementById('telefono_invitado');
        const listaInvitados = document.getElementById('lista-invitados');
        const btnUpdateInvitado = document.getElementById('btn-update-invitado');
        const btnCancelUpdate = document.getElementById('btn-cancel-update-invitado');
        let liEnEdicion = null; // Variable para saber qué 'li' estamos editando

        function resetearFormularioInvitado() {
            nombreInvitadoInput.value = '';
            emailInvitadoInput.value = '';
            telefonoInvitadoInput.value = '';
            btnAddInvitado.style.display = 'inline-block';
            btnUpdateInvitado.style.display = 'none';
            btnCancelUpdate.style.display = 'none';
            if (liEnEdicion) {
                liEnEdicion.classList.remove('list-group-item-warning');
                liEnEdicion = null;
            }
        }

        function agregarInvitado() {
            const nombre = nombreInvitadoInput.value.trim();
            const email = emailInvitadoInput.value.trim();
            const telefono = telefonoInvitadoInput.value.trim();

            if (nombre && email) {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center';
                li.dataset.nombre = nombre;
                li.dataset.email = email;
                li.dataset.telefono = telefono;
                li.innerHTML = `
                    <div class="me-auto">
                        <strong>${nombre}</strong><br>
                        <small class="text-muted">${email}${telefono ? ` / ${telefono}` : ''}</small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary me-2 btn-edit-invitado">Editar</button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-invitado">X</button>
                    </div>
                `;
                listaInvitados.appendChild(li);
                resetearFormularioInvitado();
            }
        }

        function actualizarInvitado() {
            if (!liEnEdicion) return;
            const nombre = nombreInvitadoInput.value.trim();
            const email = emailInvitadoInput.value.trim();
            const telefono = telefonoInvitadoInput.value.trim();

            if (nombre && email) {
                liEnEdicion.dataset.nombre = nombre;
                liEnEdicion.dataset.email = email;
                liEnEdicion.dataset.telefono = telefono;
                liEnEdicion.querySelector('div.me-auto').innerHTML = `
                    <strong>${nombre}</strong><br>
                    <small class="text-muted">${email}${telefono ? ` / ${telefono}` : ''}</small>
                `;
                resetearFormularioInvitado();
            }
        }

        btnAddInvitado.addEventListener('click', agregarInvitado);
        btnUpdateInvitado.addEventListener('click', actualizarInvitado);
        btnCancelUpdate.addEventListener('click', resetearFormularioInvitado);

        listaInvitados.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-delete-invitado')) {
                e.target.closest('li').remove();
            } else if (e.target.classList.contains('btn-edit-invitado')) {
                if (liEnEdicion) liEnEdicion.classList.remove('list-group-item-warning');
                liEnEdicion = e.target.closest('li');
                liEnEdicion.classList.add('list-group-item-warning');
                nombreInvitadoInput.value = liEnEdicion.dataset.nombre;
                emailInvitadoInput.value = liEnEdicion.dataset.email;
                telefonoInvitadoInput.value = liEnEdicion.dataset.telefono;
                btnAddInvitado.style.display = 'none';
                btnUpdateInvitado.style.display = 'inline-block';
                btnCancelUpdate.style.display = 'inline-block';
            }
        });
    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

/**
 * Maneja el envío del formulario de creación de citas.
 * @param {Event} e - El evento de submit.
 * @param {object} context - El objeto de contexto de la aplicación.
 */
async function handleCrearCita(e, context) {
    e.preventDefault();
    const { API_URL } = context;
    const errorContainer = document.getElementById('error-container-cita');
    const submitButton = e.target.querySelector('button[type="submit"]');
    errorContainer.innerHTML = '';

    const fecha = document.getElementById('fecha_cita').value;
    const hora = document.getElementById('hora_cita').value;

    const tipoCita = document.getElementById('tipo_cita').value;

    const payload = {
        id_cliente: document.getElementById('id_cliente').value,
        fecha_hora_inicio: `${fecha} ${hora}`,
        descripcion_trabajo: document.getElementById('descripcion_trabajo').value,
        notificar_cliente: document.getElementById('notificar_cliente').checked,
        tipo_cita: tipoCita
    };

    if (tipoCita === 'Servicio') {
        payload.id_servicio = document.getElementById('id_servicio').value;
        if (!payload.id_servicio) throw new Error("Por favor, seleccione un servicio.");
    } else { // Reunión
        // Recolectar invitados desde la nueva lista dinámica
        const invitados = [];
        document.querySelectorAll('#lista-invitados li').forEach(li => {
            invitados.push({
                nombre: li.dataset.nombre,
                email: li.dataset.email,
                telefono: li.dataset.telefono || ''
            });
        });
        payload.invitados = invitados;
    }

    submitButton.disabled = true;
    submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Guardando...`;

    try {
        const response = await fetch(`${API_URL}api_owner_cita_crear.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Ocurrió un error desconocido.');
        }

        alert(data.message || 'Cita creada con éxito.');
        // SOLUCIÓN: Al volver a la agenda, nos aseguramos de que muestre el día de la cita recién creada.
        const [year, month, day] = fecha.split('-').map(Number);
        context.state.currentDate = new Date(year, month - 1, day, 12, 0, 0); // Fijar a mediodía para evitar errores de TZ
        context.renderView('agenda');

    } catch (error) {
        errorContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = 'Guardar';
    }
}

/**
 * Renderiza la vista para editar una cita existente.
 * @param {object} context - El objeto de contexto de la aplicación.
 * @param {object} params - Parámetros, debe contener { id_cita: '...' }.
 */
export async function renderEditarCitaView(context, params) {
    const { dom, API_URL } = context;
    const id_cita = params.id_cita;

    // --- DESPACHADOR INTELIGENTE ---
    // 1. Primero, obtenemos el tipo de cita para decidir qué vista de edición mostrar.
    try {
        const response = await fetch(`${API_URL}api_owner_cita_detalle.php?id_cita=${id_cita}`);
        if (!response.ok) throw new Error('No se pudo determinar el tipo de cita.');
        const cita = await response.json();

        // 2. Llamamos a la función de renderizado específica.
        if (cita.tipo_cita === 'Reunion') {
            await renderEditarReunionView(context, cita, params);
        } else {
            await renderEditarCitaServicioView(context, cita, params);
        }
    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

/**
 * Renderiza la vista para editar una CITA DE SERVICIO.
 * @param {object} context - El objeto de contexto de la aplicación.
 * @param {object} cita - El objeto de la cita ya cargado.
 * @param {object} params - Parámetros, debe contener { id_cita: '...' }.
 */
async function renderEditarCitaServicioView(context, cita, params) {
    const { dom, API_URL } = context;
    const id_cita = params.id_cita;

    if (!id_cita) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">Error: No se especificó un ID de cita para editar.</div>`;
        return;
    }

    dom.appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"></div></div>`;

    try {
        // Los datos de la cita ya vienen pre-cargados. Solo necesitamos los servicios.
        const serviciosResponse = await fetch(`${API_URL}api_owner_servicios.php`);
        if (!serviciosResponse.ok) throw new Error('No se pudieron cargar los servicios para editar.');
        
        const serviciosData = await serviciosResponse.json(); // Objeto { servicios: [...] }

        // FILTRADO: Mostrar solo servicios activos, pero asegurando que el servicio
        // de la cita actual siempre aparezca, incluso si fue desactivado después.
        const serviciosActivos = (serviciosData.servicios || []).filter(s => s.activo == 1 || s.id_servicio == cita.id_servicio);

        const serviciosOptions = serviciosActivos.map(s => `<option value="${s.id_servicio}" ${s.id_servicio == cita.id_servicio ? 'selected' : ''}>${s.nombre_servicio}</option>`).join('');

        const fechaHora = new Date(cita.fecha_hora_inicio);
        const fechaPrefill = fechaHora.toISOString().split('T')[0];
        // const horaPrefill = fechaHora.toTimeString().substring(0, 5); // Ya no se usa, se carga dinámicamente

        dom.appContainer.innerHTML = `
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header"><h3>Editar Cita</h3></div>
                        <div class="card-body">
                            <div id="error-container-cita"></div>
                            <form id="editar-cita-form">
                                <input type="hidden" id="id_cita" value="${cita.id_cita}">
                                <div class="mb-3">
                                    <label class="form-label">Cliente</label>
                                    <input type="text" class="form-control" value="${cita.nombre_cliente}" readonly disabled>
                                    <input type="hidden" id="id_cliente" value="${cita.id_cliente}">
                                </div>
                                <div class="mb-3">
                                    <label for="id_servicio" class="form-label">Servicio</label>
                                    <select id="id_servicio" class="form-select" required>${serviciosOptions}</select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="fecha_cita" class="form-label">Fecha</label>
                                        <input type="date" id="fecha_cita" class="form-control" value="${fechaPrefill}" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="hora_cita" class="form-label">Hora</label>
                                        <!-- CAMBIO: Ahora es un select que se llenará dinámicamente -->
                                        <select id="hora_cita" class="form-select" required><option value="">Seleccione una fecha...</option></select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="descripcion_trabajo" class="form-label">Notas (Opcional)</label>
                                    <textarea id="descripcion_trabajo" class="form-control" rows="3">${cita.descripcion_trabajo || ''}</textarea>
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary" id="btn-cancelar-editar-cita">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // --- INICIO DE LÓGICA DE DISPONIBILIDAD PARA EDICIÓN ---
        const fechaInput = document.getElementById('fecha_cita');
        const horaSelect = document.getElementById('hora_cita');
        const horaOriginal = new Date(cita.fecha_hora_inicio).toTimeString().substring(0, 5);

        async function actualizarHorasDisponiblesEditar() {
            const fechaSeleccionada = fechaInput.value;
            if (!fechaSeleccionada) return;

            horaSelect.innerHTML = '<option value="">Cargando horas...</option>';
            horaSelect.disabled = true;

            try {
                // Se añade el ID de la cita actual para que la API no la considere como "ocupada"
                const response = await fetch(`${API_URL}api_owner_available_slots.php?fecha=${fechaSeleccionada}&except_id_cita=${id_cita}`);
                const slots = await response.json();
                
                // Si la fecha seleccionada es la original de la cita, nos aseguramos que la hora original esté en la lista
                if (fechaSeleccionada === fechaPrefill && !slots.includes(horaOriginal)) {
                    slots.push(horaOriginal);
                    slots.sort(); // Reordenar
                }

                horaSelect.innerHTML = slots.length > 0 ? slots.map(slot => `<option value="${slot}" ${slot === horaOriginal && fechaSeleccionada === fechaPrefill ? 'selected' : ''}>${slot}</option>`).join('') : '<option value="">No hay horas disponibles</option>';
            } catch (error) {
                horaSelect.innerHTML = '<option value="">Error al cargar horas</option>';
            } finally {
                horaSelect.disabled = false;
            }
        }

        document.getElementById('btn-cancelar-editar-cita').addEventListener('click', () => context.renderView('agenda'));
        document.getElementById('editar-cita-form').addEventListener('submit', (e) => handleEditarCita(e, context));

        // Listener para actualizar las horas cuando cambia la fecha
        fechaInput.addEventListener('change', actualizarHorasDisponiblesEditar);
        // Carga inicial de horas
        await actualizarHorasDisponiblesEditar();

    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

/**
 * Renderiza la vista para editar una REUNIÓN.
 * @param {object} context - El objeto de contexto de la aplicación.
 * @param {object} cita - El objeto de la cita ya cargado.
 * @param {object} params - Parámetros, debe contener { id_cita: '...' }.
 */
async function renderEditarReunionView(context, cita, params) {
    const { dom, API_URL } = context;
    const id_cita = params.id_cita;

    // --- LÓGICA PARA INVITADOS (SI ES REUNIÓN) ---
    let invitadosHtml = '';
    if (cita.invitados) {
        invitadosHtml = cita.invitados.map(inv => `
            <li class="list-group-item d-flex justify-content-between align-items-center" 
                data-nombre="${inv.nombre_invitado}" 
                data-email="${inv.correo_electronico_invitado}" 
                data-telefono="${inv.numero_celular_invitado || ''}">
                <div class="me-auto">
                    <strong>${inv.nombre_invitado}</strong><br>
                    <small class="text-muted">${inv.correo_electronico_invitado}${inv.numero_celular_invitado ? ` / ${inv.numero_celular_invitado}` : ''}</small>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary me-2 btn-edit-invitado">Editar</button>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-invitado">X</button>
                </div>
            </li>
        `).join('');
    }

    const fechaHora = new Date(cita.fecha_hora_inicio);
    const fechaPrefill = fechaHora.toISOString().split('T')[0];

    dom.appContainer.innerHTML = `
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><h3>Editar Reunión</h3></div>
                    <div class="card-body">
                        <div id="error-container-cita"></div>
                        <form id="editar-cita-form">
                            <input type="hidden" id="id_cita" value="${cita.id_cita}">
                            <div class="mb-3">
                                <label class="form-label">Cliente Principal</label>
                                <input type="text" class="form-control" value="${cita.nombre_cliente}" readonly disabled>
                            </div>

                            <div class="mb-3">
                                 <label class="form-label">Gestionar Invitados</label>
                                 <div class="input-group mb-2">
                                     <input type="text" id="nombre_invitado" class="form-control" placeholder="Nombre">
                                     <input type="email" id="email_invitado" class="form-control" placeholder="Correo">
                                     <input type="tel" id="telefono_invitado" class="form-control" placeholder="Teléfono (Opcional)">
                                     <div id="invitado-actions-container">
                                         <button class="btn btn-outline-secondary" type="button" id="btn-add-invitado">Añadir</button>
                                         <button class="btn btn-success" type="button" id="btn-update-invitado" style="display: none;">Actualizar</button>
                                         <button class="btn btn-secondary" type="button" id="btn-cancel-update-invitado" style="display: none;">Cancelar</button>
                                     </div>
                                 </div>
                                 <div id="lista-invitados-container">
                                     <ul class="list-group" id="lista-invitados">${invitadosHtml}</ul>
                                 </div>
                             </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="fecha_cita" class="form-label">Fecha</label>
                                    <input type="date" id="fecha_cita" class="form-control" value="${fechaPrefill}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="hora_cita" class="form-label">Hora</label>
                                    <select id="hora_cita" class="form-select" required><option value="">Seleccione una fecha...</option></select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="descripcion_trabajo" class="form-label">Asunto de la Reunión</label>
                                <textarea id="descripcion_trabajo" class="form-control" rows="3">${cita.descripcion_trabajo || ''}</textarea>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary" id="btn-cancelar-editar-cita">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    `;

    // --- LÓGICA PARA INVITADOS ---
    const btnAddInvitado = document.getElementById('btn-add-invitado');
    const nombreInvitadoInput = document.getElementById('nombre_invitado');
    const emailInvitadoInput = document.getElementById('email_invitado');
    const telefonoInvitadoInput = document.getElementById('telefono_invitado');
    const listaInvitados = document.getElementById('lista-invitados');
    const btnUpdateInvitado = document.getElementById('btn-update-invitado');
    const btnCancelUpdate = document.getElementById('btn-cancel-update-invitado');
    let liEnEdicion = null;

    function resetearFormularioInvitado() {
        nombreInvitadoInput.value = ''; emailInvitadoInput.value = ''; telefonoInvitadoInput.value = '';
        btnAddInvitado.style.display = 'inline-block';
        btnUpdateInvitado.style.display = 'none'; btnCancelUpdate.style.display = 'none';
        if (liEnEdicion) { liEnEdicion.classList.remove('list-group-item-warning'); liEnEdicion = null; }
    }

    function agregarInvitado() {
        const nombre = nombreInvitadoInput.value.trim(); const email = emailInvitadoInput.value.trim(); const telefono = telefonoInvitadoInput.value.trim();
        if (nombre && email) {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            li.dataset.nombre = nombre; li.dataset.email = email; li.dataset.telefono = telefono;
            li.innerHTML = `<div class="me-auto"><strong>${nombre}</strong><br><small class="text-muted">${email}${telefono ? ` / ${telefono}` : ''}</small></div><div><button type="button" class="btn btn-sm btn-outline-primary me-2 btn-edit-invitado">Editar</button><button type="button" class="btn btn-sm btn-outline-danger btn-delete-invitado">X</button></div>`;
            listaInvitados.appendChild(li);
            resetearFormularioInvitado();
        }
    }

    function actualizarInvitado() {
        if (!liEnEdicion) return;
        const nombre = nombreInvitadoInput.value.trim(); const email = emailInvitadoInput.value.trim(); const telefono = telefonoInvitadoInput.value.trim();
        if (nombre && email) {
            liEnEdicion.dataset.nombre = nombre; liEnEdicion.dataset.email = email; liEnEdicion.dataset.telefono = telefono;
            liEnEdicion.querySelector('div.me-auto').innerHTML = `<strong>${nombre}</strong><br><small class="text-muted">${email}${telefono ? ` / ${telefono}` : ''}</small>`;
            resetearFormularioInvitado();
        }
    }

    btnAddInvitado.addEventListener('click', agregarInvitado);
    btnUpdateInvitado.addEventListener('click', actualizarInvitado);
    btnCancelUpdate.addEventListener('click', resetearFormularioInvitado);

    listaInvitados.addEventListener('click', (e) => {
        if (e.target.classList.contains('btn-delete-invitado')) {
            e.target.closest('li').remove();
        } else if (e.target.classList.contains('btn-edit-invitado')) {
            if (liEnEdicion) liEnEdicion.classList.remove('list-group-item-warning');
            liEnEdicion = e.target.closest('li');
            liEnEdicion.classList.add('list-group-item-warning');
            nombreInvitadoInput.value = liEnEdicion.dataset.nombre; emailInvitadoInput.value = liEnEdicion.dataset.email; telefonoInvitadoInput.value = liEnEdicion.dataset.telefono;
            btnAddInvitado.style.display = 'none'; btnUpdateInvitado.style.display = 'inline-block'; btnCancelUpdate.style.display = 'inline-block';
        }
    });

    // --- LÓGICA DE DISPONIBILIDAD ---
    const fechaInput = document.getElementById('fecha_cita');
    const horaSelect = document.getElementById('hora_cita');
    const horaOriginal = new Date(cita.fecha_hora_inicio).toTimeString().substring(0, 5);

    async function actualizarHorasDisponiblesEditar() {
        const fechaSeleccionada = fechaInput.value;
        if (!fechaSeleccionada) return;
        horaSelect.innerHTML = '<option value="">Cargando horas...</option>';
        horaSelect.disabled = true;
        try {
            const response = await fetch(`${API_URL}api_owner_available_slots.php?fecha=${fechaSeleccionada}&except_id_cita=${id_cita}`);
            const slots = await response.json();
            if (fechaSeleccionada === fechaPrefill && !slots.includes(horaOriginal)) {
                slots.push(horaOriginal);
                slots.sort();
            }
            horaSelect.innerHTML = slots.length > 0 ? slots.map(slot => `<option value="${slot}" ${slot === horaOriginal && fechaSeleccionada === fechaPrefill ? 'selected' : ''}>${slot}</option>`).join('') : '<option value="">No hay horas disponibles</option>';
        } catch (error) {
            horaSelect.innerHTML = '<option value="">Error al cargar horas</option>';
        } finally {
            horaSelect.disabled = false;
        }
    }

    document.getElementById('btn-cancelar-editar-cita').addEventListener('click', () => context.renderView('agenda'));
    document.getElementById('editar-cita-form').addEventListener('submit', (e) => handleEditarCita(e, context));
    fechaInput.addEventListener('change', actualizarHorasDisponiblesEditar);
    await actualizarHorasDisponiblesEditar();
}

/**
 * Maneja el envío del formulario de edición de citas.
 * @param {Event} e - El evento de submit.
 * @param {object} context - El objeto de contexto de la aplicación.
 */
async function handleEditarCita(e, context) {
    e.preventDefault();
    const { API_URL } = context;
    const errorContainer = document.getElementById('error-container-cita');
    const submitButton = e.target.querySelector('button[type="submit"]');
    errorContainer.innerHTML = '';

    const fecha = document.getElementById('fecha_cita').value;
    const hora = document.getElementById('hora_cita').value;

    // Deducir el tipo de cita basado en los campos presentes en el formulario
    const esReunion = !!document.getElementById('lista-invitados');
    const tipoCita = esReunion ? 'Reunion' : 'Servicio';

    const payload = {
        id_cita: document.getElementById('id_cita').value,
        fecha_hora_inicio: `${fecha} ${hora}`,
        descripcion_trabajo: document.getElementById('descripcion_trabajo').value,
        tipo_cita: tipoCita
    };

    if (esReunion) {
        payload.invitados = [];
        document.querySelectorAll('#lista-invitados li').forEach(li => payload.invitados.push({ nombre: li.dataset.nombre, email: li.dataset.email, telefono: li.dataset.telefono || '' }));
    } else {
        payload.id_servicio = document.getElementById('id_servicio').value;
        payload.id_cliente = document.getElementById('id_cliente').value; // El cliente solo es relevante para citas de servicio en este contexto
    }

    submitButton.disabled = true;
    submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Guardando...`;

    try {
        const response = await fetch(`${API_URL}api_owner_cita_actualizar.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error || 'Ocurrió un error desconocido.');
        }

        alert(data.message || 'Cita actualizada con éxito.');
        context.renderView('agenda');

    } catch (error) {
        errorContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = 'Guardar Cambios';
    }
}