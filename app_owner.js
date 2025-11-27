document.addEventListener('DOMContentLoaded', function() {
    const API_URL = ''; // Todo está en la misma carpeta raíz
    const navbarBrand = document.getElementById('navbar-brand-title'); // CORRECCIÓN: Obtener el elemento del título
    const appContainer = document.getElementById('app-container');
    const navMenu = document.getElementById('nav-menu');

    let ownerActual = null; // Para mantener el estado del propietario logueado
    let currentDate = new Date(); // Para mantener la fecha actual de la vista de citas
    let clockInterval = null; // Para controlar el intervalo del reloj

    // Función para renderizar la vista de Login
    async function renderLoginView() {
        let captchaHash = '';
        let paisesOptions = '';
        let errorLogin = '';

        try {
            // Obtener CAPTCHA y lista de países en paralelo
            const [captchaResponse, paisesResponse] = await Promise.all([
                fetch(`${API_URL}api_owner_login.php`), // GET request for CAPTCHA
                fetch(`${API_URL}api_paises.php`)
            ]);

            if (!captchaResponse.ok) {
                throw new Error(`Error al cargar CAPTCHA: ${captchaResponse.status} ${captchaResponse.statusText}`);
            }
            if (!paisesResponse.ok) {
                throw new Error(`Error al cargar países: ${paisesResponse.status} ${paisesResponse.statusText}`);
            }

            const captchaData = await captchaResponse.json();
            const paises = await paisesResponse.json();
            captchaHash = captchaData.captcha_hash;

            paisesOptions = paises.map(pais => 
                `<option value="${pais.codigo_telefono}" ${pais.id_pais == 1 ? 'selected' : ''}>${pais.nombre_pais} (${pais.codigo_telefono})</option>`
            ).join('');

        } catch (error) {
            console.error("Error al cargar datos iniciales para el login del propietario:", error);
            errorLogin = `<div class="alert alert-danger mt-3">Error al cargar el formulario de acceso: ${error.message}</div>`;
        }

        document.title = 'Gestión de Citas del Negocio - Login';
        navbarBrand.textContent = 'Gestión de Citas del Negocio';
        navMenu.innerHTML = ''; // Menú vacío en el login

        appContainer.innerHTML = `
            <div class="row justify-content-center">
                <div class="col-md-5 col-lg-4">
                    <div class="card shadow">
                        <div class="card-header text-center bg-primary text-white"><h3>Bienvenido</h3></div>
                        <div class="card-body p-4">
                            <div id="error-container">${errorLogin}</div>
                            <form id="owner-login-form">
                                <div class="mb-3">
                                    <label for="telefono" class="form-label">Teléfono del Negocio</label>
                                    <div class="input-group">
                                        <select class="form-select" id="country_code" name="country_code" style="max-width: 150px;" required>
                                            ${paisesOptions}
                                        </select>
                                        <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="Ej: 4121234567" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Tu Contraseña de Usuario</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <!-- SECCIÓN DE CAPTCHA DESACTIVADA PARA DESARROLLO -->
                                <input type="hidden" name="captcha" id="captcha" value="dev"> <!-- Valor dummy para que el POST no falle -->
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">Ingresar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('owner-login-form').addEventListener('submit', handleLogin);
    }

    // Función para manejar el intento de login
    async function handleLogin(event) {
        event.preventDefault();
        const form = event.target;
        const submitButton = form.querySelector('button[type="submit"]');
        const errorContainer = document.getElementById('error-container');
        errorContainer.innerHTML = '';

        const payload = {
            // Combinar código de país y número de teléfono
            telefono: `${document.getElementById('country_code').value.trim()} ${document.getElementById('telefono').value.trim()}`,
            password: document.getElementById('password').value,
            captcha: document.getElementById('captcha').value,
        };

        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verificando...`;

        try {
            const response = await fetch(`${API_URL}api_owner_login.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Ocurrió un error desconocido.');
            }

            // ¡Login exitoso!
            ownerActual = data;
            document.title = `Portal - ${ownerActual.nombre_negocio}`; // CORRECCIÓN: Actualizar título de la página
            navbarBrand.textContent = ownerActual.nombre_negocio; 
            updateNavbar(); // 1. Dibuja el menú de navegación primero.
            if (clockInterval) clearInterval(clockInterval); // Limpiar cualquier intervalo anterior
            clockInterval = setInterval(() => {
                updateClock('owner-clock');
            }, 1000);
            renderMiAgendaView(); // 2. CORRECCIÓN: Renderiza "Mi Agenda" (la lista de citas) como vista inicial.

        } catch (error) {
            errorContainer.innerHTML = `<div class="alert alert-danger mt-3">${error.message}</div>`;
            // Recargar CAPTCHA en caso de error
            try {
                const captchaResponse = await fetch(`${API_URL}api_owner_login.php`);
                const captchaData = await captchaResponse.json();
                document.getElementById('captcha-display').textContent = captchaData.captcha_hash;
                document.getElementById('captcha').value = ''; // Limpiar el input del CAPTCHA
            } catch (captchaError) { /* Ignorar errores al recargar CAPTCHA */ }
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Ingresar';
        }
    }

    // --- VISTAS POST-LOGIN (POR AHORA, SOLO UN ESQUELETO) ---
    function updateNavbar() {
        navMenu.innerHTML = ''; // Limpiar el menú

        // Lógica para mostrar días restantes
        let trialInfoHtml = '';
        if (ownerActual && ownerActual.fecha_desactivacion) {
            const hoy = new Date();
            const fin = new Date(ownerActual.fecha_desactivacion);
            const diffTime = fin - hoy;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            let badgeClass = 'bg-success';
            if (diffDays <= 7) badgeClass = 'bg-warning';
            if (diffDays <= 0) badgeClass = 'bg-danger';
            trialInfoHtml = `<span class="navbar-text me-3"><span class="badge ${badgeClass}">${diffDays > 0 ? `Quedan ${diffDays} días` : 'Prueba finalizada'}</span></span>`;
        }

        if (ownerActual) {
            // Si hay un propietario logueado, mostrar su menú
            navMenu.innerHTML = `
                <li class="nav-item"><a class="nav-link active" href="#" id="nav-mi-agenda">Mi Agenda</a></li>
                <li class="nav-item"><a class="nav-link" href="#" id="nav-disponibilidad">Disponibilidad del Día</a></li>
                <li class="nav-item"><a class="nav-link" href="#" id="nav-calendario">Calendario</a></li>
                <li class="nav-item"><a class="nav-link" href="#" id="nav-clientes">Mis Clientes</a></li>
                <li class="nav-item"><a class="nav-link" href="#" id="nav-servicios">Mis Servicios</a></li>
                <li class="nav-item ms-lg-3"><a class="nav-link" href="#" id="nav-mi-negocio">Mi Negocio</a></li>
                <li class="nav-item"><a class="nav-link" href="#" id="nav-mi-perfil">Mi Perfil</a></li>
                <li class="nav-item"><a class="nav-link" href="#" id="nav-salir">Salir</a></li>
                <li class="nav-item ms-lg-auto">${trialInfoHtml}</li>
                <li class="nav-item"><span class="navbar-text" id="owner-clock">--:--:--</span></li>
            `;

            // Añadir listeners a los elementos del menú
            document.getElementById('nav-calendario').addEventListener('click', renderCalendarioView);
            document.getElementById('nav-servicios').addEventListener('click', renderServiciosView);
            document.getElementById('nav-clientes').addEventListener('click', renderClientesView);
            document.getElementById('nav-mi-negocio').addEventListener('click', renderMiNegocioView);
            document.getElementById('nav-mi-agenda').addEventListener('click', renderMiAgendaView);
            document.getElementById('nav-disponibilidad').addEventListener('click', renderDisponibilidadView);
            document.getElementById('nav-mi-perfil').addEventListener('click', renderMiPerfilView);
            document.getElementById('nav-salir').addEventListener('click', handleLogout);
        }
    }

    function handleLogout(event) {
        event.preventDefault();
        fetch(`${API_URL}api_owner_logout.php`) // Llamamos a la API de logout
            .then(() => clearInterval(clockInterval)) // Detener el reloj
            .finally(() => {
                ownerActual = null; // Limpiar el estado del propietario
                navbarBrand.textContent = 'Gestión de Citas del Negocio - Login';
                navMenu.innerHTML = ''; // Asegurarse de que el menú esté vacío
                renderLoginView(); // Volver a la vista de login
            });
    }

    // --- VISTA "MI AGENDA" (GESTOR DE CITAS DIARIAS) ---
    async function renderMiAgendaView(event) {
        if (event) event.preventDefault();

        // Si se pasa una nueva fecha, la actualizamos. Si no, usamos la que ya tenemos.
        if (event && event.detail && event.detail.newDate) {
            currentDate = event.detail.newDate;
        }

        setActiveNavLink('nav-mi-agenda');
        appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando citas...</span></div></div>`;

        try {
            const fechaFiltro = currentDate.toISOString().split('T')[0];
            
            // Usamos la API que devuelve la lista de citas para un día
            const response = await fetch(`${API_URL}api_owner_citas.php?fecha=${fechaFiltro}`);
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'No se pudieron cargar las citas.');
            }
            const citas = await response.json();

            let citasHtml = '';
            if (citas.length > 0) {
                const status_colors = {
                    'Pendiente': 'bg-info text-dark', 'Completada': 'bg-success',
                    'Cancelada': 'bg-danger', 'Pospuesta': 'bg-warning text-dark',
                    'No Asistió': 'bg-secondary', 'Confirmada': 'bg-primary',
                };

                const citasRows = citas.map((cita, index) => {
                    const inicio = new Date(cita.fecha_hora_inicio);
                    const fin = new Date(cita.fecha_hora_fin);
                    const estado = cita.estado_cita;
                    const color_clase = status_colors[estado] ?? 'bg-light text-dark';
                    return `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${inicio.toLocaleTimeString('es-ES', { hour: 'numeric', minute: '2-digit' })} - ${fin.toLocaleTimeString('es-ES', { hour: 'numeric', minute: '2-digit' })}</td>
                            <td>${cita.nombre_cliente}</td>
                            <td>${cita.nombre_servicio}</td>
                            <td><span class="badge ${color_clase}">${estado}</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary btn-edit-cita" data-id-cita="${cita.id_cita}">Ver/Editar</button>
                                <button class="btn btn-sm btn-outline-danger ms-1 btn-delete-cita" data-id-cita="${cita.id_cita}">Eliminar</button>
                            </td>
                        </tr>
                    `;
                }).join('');

                citasHtml = `
                    <table class="table table-striped table-hover">
                        <thead class="table-dark"><tr><th>#</th><th>Horario</th><th>Cliente</th><th>Servicio</th><th>Estado</th><th>Acciones</th></tr></thead>
                        <tbody>${citasRows}</tbody>
                    </table>`;
            } else {
                citasHtml = `<div class="alert alert-info">No hay citas agendadas para esta fecha.</div>`;
            }

            appContainer.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Mi Agenda <small class="text-muted fs-5">(${currentDate.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' })})</small></h3>
                    <div class="btn-group">
                        <button class="btn btn-secondary" id="prev-day-btn">&laquo; Día Anterior</button>
                        <input type="date" class="form-control" id="date-picker" value="${fechaFiltro}">
                        <button class="btn btn-secondary" id="next-day-btn">Día Siguiente &raquo;</button>
                    </div>
                </div>
                ${citasHtml}
                <div class="d-flex justify-content-end">
                     <button class="btn btn-primary" id="btn-agendar-desde-lista">Agendar Nueva Cita</button>
                </div>
            `;

            // Añadir listeners
            document.getElementById('prev-day-btn').addEventListener('click', () => { currentDate.setDate(currentDate.getDate() - 1); renderMiAgendaView(); });
            document.getElementById('next-day-btn').addEventListener('click', () => { currentDate.setDate(currentDate.getDate() + 1); renderMiAgendaView(); });
            document.getElementById('date-picker').addEventListener('change', (e) => {
                const [year, month, day] = e.target.value.split('-').map(Number);
                currentDate = new Date(year, month - 1, day);
                renderMiAgendaView();
            });
            document.getElementById('btn-agendar-desde-lista').addEventListener('click', () => openAgendarCitaModal());
            document.querySelectorAll('.btn-edit-cita').forEach(btn => btn.addEventListener('click', (e) => openEditCitaModal(e.target.dataset.idCita)));
            document.querySelectorAll('.btn-delete-cita').forEach(btn => btn.addEventListener('click', (e) => handleDeleteCita(e.target.dataset.idCita)));

        } catch (error) {
            appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    }

    function renderDashboardView() {
        document.title = `Portal - ${ownerActual.nombre_negocio}`;
        navbarBrand.textContent = ownerActual.nombre_negocio;
        // Vista principal (por ahora, un simple saludo)
        appContainer.innerHTML = `
            <div class="p-5 mb-4 bg-light rounded-3">
                <div class="container-fluid py-5">
                    <h1 class="display-5 fw-bold">¡Bienvenido, ${ownerActual.nombre_usuario}!</h1>
                    <p class="col-md-8 fs-4">Desde aquí podrás gestionar las citas, servicios y clientes de tu negocio de forma rápida y sencilla.</p>
                </div>
            </div>
        `;
    }

    // --- VISTA DE CALENDARIO (NUEVA VISTA PRINCIPAL) ---
    async function renderCalendarioView(event) {
        if (event) event.preventDefault();
        setActiveNavLink('nav-calendario');
        appContainer.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Calendario de Citas</h3>
                <button class="btn btn-primary" id="btn-agendar-desde-calendario">Agendar Nueva Cita</button>
            </div>
            <div id="calendar-container" class="bg-white p-3 rounded shadow-sm">
                <div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando calendario...</span></div></div>
            </div>
        `;

        document.getElementById('btn-agendar-desde-calendario').addEventListener('click', () => openAgendarCitaModal());

        try {
            // 1. Obtener la configuración del negocio para saber las horas de trabajo
            const negocioResponse = await fetch(`${API_URL}api_owner_negocio_get.php`);
            if (!negocioResponse.ok) throw new Error('No se pudo cargar la configuración del negocio.');
            const negocio = await negocioResponse.json();

            // 2. Inicializar el calendario con las horas de trabajo
            const calendarEl = document.getElementById('calendar-container');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                themeSystem: 'bootstrap5',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                locale: 'es',
                initialView: 'dayGridMonth',
                events: `${API_URL}api_owner_calendario_eventos.php`,
                // --- ¡AQUÍ ESTÁ LA MAGIA! ---
                slotMinTime: negocio.hora_inicio, // Ej: "09:00:00"
                slotMaxTime: negocio.hora_cierre, // Ej: "18:00:00"
                businessHours: {
                    daysOfWeek: negocio.dias_trabajo.split(',').map(Number), // [1, 2, 3, 4, 5] para L-V
                    startTime: negocio.hora_inicio,
                    endTime: negocio.hora_cierre,
                },
                eventClick: function(info) {
                    openEditCitaModal(info.event.id);
                },
                dateClick: function(info) {
                    openAgendarCitaModal(info.dateStr);
                }
            });
            calendar.render();
        } catch (error) {
            appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    }

    async function openEditCitaModal(idCita) {
        try {
            const response = await fetch(`${API_URL}api_owner_cita_detalle.php?id_cita=${idCita}`);
            if (!response.ok) throw new Error('No se pudo cargar el detalle de la cita.');
            const cita = await response.json();

            const estados = ['Pendiente', 'Confirmada', 'Completada', 'Cancelada', 'Pospuesta', 'No Asistió'];
            const opcionesEstado = estados.map(e => `<option value="${e}" ${e === cita.estado_cita ? 'selected' : ''}>${e}</option>`).join('');

            const modalHtml = `
                <div class="modal fade" id="editCitaModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Detalle de Cita #${cita.id_cita}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div id="modal-error-container"></div>
                                <p><strong>Cliente:</strong> ${cita.nombre_cliente} (${cita.telefono_cliente})</p>
                                <p><strong>Servicio:</strong> ${cita.nombre_servicio} (${new Date(cita.fecha_hora_inicio).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})})</p>
                                <form id="edit-cita-form">
                                    <div class="mb-3">
                                        <label for="estado_cita" class="form-label">Estado de la Cita</label>
                                        <select class="form-select" id="estado_cita">${opcionesEstado}</select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="descripcion_trabajo" class="form-label">Descripción / Notas (Opcional)</label>
                                        <textarea class="form-control" id="descripcion_trabajo" rows="3">${cita.descripcion_trabajo || ''}</textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                <button type="button" class="btn btn-primary" id="save-cita-btn">Guardar Cambios</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            // Añadir el modal al body y mostrarlo
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modalElement = document.getElementById('editCitaModal');
            const modal = new bootstrap.Modal(modalElement);
            modal.show();

            // Listener para guardar
            document.getElementById('save-cita-btn').addEventListener('click', async () => {
                const payload = {
                    id_cita: idCita,
                    estado_cita: document.getElementById('estado_cita').value,
                    descripcion_trabajo: document.getElementById('descripcion_trabajo').value
                };
                try {
                    const saveResponse = await fetch(`${API_URL}api_owner_cita_actualizar.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const saveData = await saveResponse.json();
                    if (!saveResponse.ok) throw new Error(saveData.error);
                    
                    modal.hide();
                    // Recargar la vista activa
                    const activeLink = navMenu.querySelector('.nav-link.active');
                    if (activeLink && activeLink.id === 'nav-mi-agenda') {
                        renderMiAgendaView();
                    } else {
                        renderCalendarioView();
                    }
                } catch (saveError) {
                    document.getElementById('modal-error-container').innerHTML = `<div class="alert alert-danger">${saveError.message}</div>`;
                }
            });

            // Limpiar el HTML del modal del DOM cuando se cierre
            modalElement.addEventListener('hidden.bs.modal', () => {
                modalElement.remove();
            });

        } catch (error) {
            alert(error.message);
        }
    }

    async function handleUpdateNegocio(e) {
        e.preventDefault();
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
            id_pais: document.getElementById('id_pais').value,
            id_estado: document.getElementById('id_estado').value,
            zip_code: document.getElementById('zip_code').value
        };

        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Guardando...`;

        try {
            const response = await fetch(`${API_URL}api_owner_negocio_update.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (!response.ok) throw new Error(data.error || 'Hubo un problema al guardar.');
            
            errorContainer.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            navbarBrand.textContent = payload.nombre_negocio; // Actualizar el nombre en la barra de navegación
        } catch (error) {
            errorContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Guardar Configuración';
        }
    }

    // --- VISTA DE CLIENTES ---
    async function renderClientesView(event) {
        if (event) event.preventDefault();
        setActiveNavLink('nav-clientes');
        appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando clientes...</span></div></div>`;

        try {
            const response = await fetch(`${API_URL}api_owner_clientes.php`);
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'No se pudieron cargar los clientes.');
            }
            const clientes = await response.json();

            let clientesHtml = '';
            if (clientes.length > 0) {
                const clientesRows = clientes.map((cliente, index) => {
                    return `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${cliente.nombre_completo}</td>
                            <td>${cliente.numero_celular}</td>
                            <td>${cliente.correo_electronico}</td>
                            <td>
                                <button class="btn btn-sm btn-warning btn-edit-cliente" data-id-cliente="${cliente.id_cliente}">Editar</button>
                                <button class="btn btn-sm btn-danger btn-delete-cliente" data-id-cliente="${cliente.id_cliente}">Eliminar</button>
                            </td>
                        </tr>
                    `;
                }).join('');

                clientesHtml = `
                    <table class="table table-striped table-hover">
                        <thead class="table-dark"><tr><th>#</th><th>Nombre</th><th>Celular</th><th>Email</th><th>Acciones</th></tr></thead>
                        <tbody>${clientesRows}</tbody>
                    </table>`;
            } else {
                clientesHtml = `<div class="alert alert-info">No hay clientes registrados en su negocio.</div>`;
            }

            appContainer.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Gestión de Clientes</h3>
                    <button class="btn btn-primary" id="btn-crear-cliente">Registrar Nuevo Cliente</button>
                </div>
                ${clientesHtml}
            `;

            // Añadir listeners para los botones de acción
            document.getElementById('btn-crear-cliente').addEventListener('click', () => openClienteModal());
            document.querySelectorAll('.btn-edit-cliente').forEach(btn => {
                btn.addEventListener('click', (e) => openClienteModal(e.target.dataset.idCliente));
            });
            document.querySelectorAll('.btn-delete-cliente').forEach(btn => {
                btn.addEventListener('click', (e) => handleDeleteCliente(e.target.dataset.idCliente));
            });

        } catch (error) {
            appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    }

    async function handleDeleteCliente(id_cliente) {
        if (!confirm('¿Estás seguro de que quieres eliminar este cliente? Se eliminarán también todas sus citas.')) {
            return;
        }
        try {
            const response = await fetch(`${API_URL}api_owner_cliente_eliminar.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_cliente: id_cliente })
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error);
            
            alert(data.message);
            renderClientesView(); // Recargar la lista de clientes
        } catch (error) {
            alert('Error al eliminar el cliente: ' + error.message);
        }
    }

    async function openClienteModal(id_cliente = null) {
        let cliente = {};
        let modalTitle = 'Registrar Nuevo Cliente';
        let paises = [];
        let estados = [];

        try {
            const paisesResponse = await fetch(`${API_URL}api_paises.php`);
            paises = await paisesResponse.json();

            if (id_cliente) {
                modalTitle = 'Editar Cliente';
                const clienteResponse = await fetch(`${API_URL}api_owner_cliente_detalle.php?id_cliente=${id_cliente}`);
                if (!clienteResponse.ok) throw new Error('No se pudo cargar el detalle del cliente.');
                cliente = await clienteResponse.json();

                // Cargar estados para el país del cliente
                if (cliente.id_pais) {
                    const estadosResponse = await fetch(`${API_URL}api_estados.php?id_pais=${cliente.id_pais}`);
                    estados = await estadosResponse.json();
                }
            }
        } catch (error) {
            alert('Error al preparar el formulario de cliente: ' + error.message);
            return;
        }

        const paisesOptions = paises.map(p => `<option value="${p.id_pais}" data-codigo-telefono="${p.codigo_telefono}" ${cliente.id_pais == p.id_pais ? 'selected' : ''}>${p.nombre_pais}</option>`).join('');
        const estadosOptions = estados.map(e => `<option value="${e.id_estado}" ${cliente.id_estado == e.id_estado ? 'selected' : ''}>${e.nombre_estado}</option>`).join('');

        // Separar código de país y número local para el formulario
        let currentCountryCode = '';
        let currentPhoneNumber = cliente.numero_celular || '';
        if (cliente.numero_celular) {
            const parts = cliente.numero_celular.split(' ');
            if (parts.length > 1 && parts[0].startsWith('+')) {
                currentCountryCode = parts[0];
                currentPhoneNumber = parts.slice(1).join(' ');
            }
        }
        // Asegurar que el código de país por defecto sea el del primer país si no hay uno seleccionado
        if (!currentCountryCode && paises.length > 0) {
            currentCountryCode = paises[0].codigo_telefono;
        }

        const modalHtml = `
            <div class="modal fade" id="clienteModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${modalTitle}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="modal-error-container"></div>
                            <form id="cliente-form">
                                <input type="hidden" id="cliente_id" value="${cliente.id_cliente || ''}">
                                <div class="mb-3">
                                    <label for="nombre_completo" class="form-label">Nombre Completo</label>
                                    <input type="text" class="form-control" id="nombre_completo" value="${cliente.nombre_completo || ''}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="numero_celular" class="form-label">Número de Celular</label>
                                    <div class="input-group">
                                        <select class="form-select" id="country_code_cliente" style="max-width: 120px;">
                                            ${paises.map(p => `<option value="${p.codigo_telefono}" ${currentCountryCode === p.codigo_telefono ? 'selected' : ''}>${p.codigo_telefono}</option>`).join('')}
                                        </select>
                                        <input type="tel" class="form-control" id="numero_celular" value="${currentPhoneNumber}" placeholder="Ej: 4121234567" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="correo_electronico" class="form-label">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="correo_electronico" value="${cliente.correo_electronico || ''}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="direccion1" class="form-label">Dirección 1</label>
                                    <input type="text" class="form-control" id="direccion1" value="${cliente.direccion1 || ''}">
                                </div>
                                <div class="mb-3">
                                    <label for="direccion2" class="form-label">Dirección 2 (Opcional)</label>
                                    <input type="text" class="form-control" id="direccion2" value="${cliente.direccion2 || ''}">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="ciudad" class="form-label">Ciudad</label>
                                        <input type="text" class="form-control" id="ciudad" value="${cliente.ciudad || ''}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="zip_code" class="form-label">Código Postal</label>
                                        <input type="text" class="form-control" id="zip_code" value="${cliente.zip_code || ''}">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="id_pais" class="form-label">País</label>
                                        <select class="form-select" id="id_pais">${paisesOptions}</select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="id_estado" class="form-label">Estado / Provincia</label>
                                        <select class="form-select" id="id_estado" ${cliente.id_pais ? '' : 'disabled'}>${estadosOptions}</select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="notas_adicionales" class="form-label">Notas Adicionales</label>
                                    <textarea class="form-control" id="notas_adicionales" rows="3">${cliente.notas_adicionales || ''}</textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="save-cliente-btn">Guardar Cliente</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modalElement = document.getElementById('clienteModal');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        // Lógica para cargar estados dinámicamente en el modal
        const paisSelectModal = document.getElementById('id_pais');
        const estadoSelectModal = document.getElementById('id_estado');
        const countryCodeClienteSelect = document.getElementById('country_code_cliente');

        async function cargarEstadosModal(idPais, idEstadoSeleccionado = null) {
            if (!idPais) {
                estadoSelectModal.innerHTML = '<option value="">Seleccione un país primero</option>';
                estadoSelectModal.disabled = true;
                return;
            }
            const response = await fetch(`${API_URL}api_estados.php?id_pais=${idPais}`);
            const estadosData = await response.json();
            estadoSelectModal.innerHTML = '<option value="">Seleccione un estado...</option>';
            estadosData.forEach(estado => {
                estadoSelectModal.innerHTML += `<option value="${estado.id_estado}" ${estado.id_estado == idEstadoSeleccionado ? 'selected' : ''}>${estado.nombre_estado}</option>`;
            });
            estadoSelectModal.disabled = false;
        }

        paisSelectModal.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const newCountryCode = selectedOption.dataset.codigoTelefono;
            countryCodeClienteSelect.value = newCountryCode; // Actualizar el selector de código de teléfono
            cargarEstadosModal(this.value);
        });

        // Carga inicial de estados para el modal
        if (cliente.id_pais) {
            cargarEstadosModal(cliente.id_pais, cliente.id_estado);
        } else if (paises.length > 0) {
            // Si es nuevo cliente, cargar estados del primer país por defecto
            cargarEstadosModal(paises[0].id_pais);
        }

        document.getElementById('save-cliente-btn').addEventListener('click', async () => {
            const isNew = !document.getElementById('cliente_id').value;
            const endpoint = isNew ? `${API_URL}api_owner_cliente_crear.php` : `${API_URL}api_owner_cliente_actualizar.php`;
            const method = 'POST';

            const payload = {
                id_cliente: document.getElementById('cliente_id').value,
                nombre_completo: document.getElementById('nombre_completo').value,
                numero_celular: `${document.getElementById('country_code_cliente').value} ${document.getElementById('numero_celular').value.trim()}`,
                correo_electronico: document.getElementById('correo_electronico').value,
                direccion1: document.getElementById('direccion1').value,
                direccion2: document.getElementById('direccion2').value,
                ciudad: document.getElementById('ciudad').value,
                id_pais: document.getElementById('id_pais').value,
                id_estado: document.getElementById('id_estado').value,
                zip_code: document.getElementById('zip_code').value,
                notas_adicionales: document.getElementById('notas_adicionales').value,
            };

            try {
                const saveResponse = await fetch(endpoint, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const saveData = await saveResponse.json();
                if (!saveResponse.ok) throw new Error(saveData.error);
                
                modal.hide();
                renderClientesView(); // Recargar la lista de clientes
            } catch (saveError) {
                document.getElementById('modal-error-container').innerHTML = `<div class="alert alert-danger">${saveError.message}</div>`;
            }
        });

        modalElement.addEventListener('hidden.bs.modal', () => {
            modalElement.remove();
        });
    }

    // --- VISTA DE SERVICIOS ---
    async function renderServiciosView(event) {
        if (event) event.preventDefault();
        setActiveNavLink('nav-servicios');
        appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando servicios...</span></div></div>`;

        try {
            const response = await fetch(`${API_URL}api_owner_servicios.php`);
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'No se pudieron cargar los servicios.');
            }
            const servicios = await response.json();

            let serviciosHtml = '';
            if (servicios.length > 0) {
                const serviciosRows = servicios.map((servicio, index) => {
                    const precioFormateado = servicio.precio ? `$${parseFloat(servicio.precio).toFixed(2)}` : 'N/A';
                    return `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${servicio.nombre_servicio}</td>
                            <td>${servicio.duracion_valor} ${servicio.duracion_unidad}</td>
                            <td>${precioFormateado}</td>
                            <td>
                                <button class="btn btn-sm btn-warning btn-edit-servicio" data-id-servicio='${JSON.stringify(servicio)}'>Editar</button>
                                <button class="btn btn-sm btn-danger btn-delete-servicio" data-id-servicio="${servicio.id_servicio}">Eliminar</button>
                            </td>
                        </tr>
                    `;
                }).join('');

                serviciosHtml = `
                    <table class="table table-striped table-hover">
                        <thead class="table-dark"><tr><th>#</th><th>Nombre</th><th>Duración</th><th>Precio</th><th>Acciones</th></tr></thead>
                        <tbody>${serviciosRows}</tbody>
                    </table>`;
            } else {
                serviciosHtml = `<div class="alert alert-info">No hay servicios registrados en su negocio.</div>`;
            }

            appContainer.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Gestión de Servicios</h3>
                    <button class="btn btn-primary" id="btn-crear-servicio">Crear Nuevo Servicio</button>
                </div>
                ${serviciosHtml}
            `;

            // Añadir listeners para los botones de acción
            document.getElementById('btn-crear-servicio').addEventListener('click', () => openServicioModal());
            document.querySelectorAll('.btn-edit-servicio').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const servicioData = JSON.parse(e.currentTarget.dataset.idServicio);
                    openServicioModal(servicioData);
                });
            });
            document.querySelectorAll('.btn-delete-servicio').forEach(btn => {
                btn.addEventListener('click', (e) => handleDeleteServicio(e.target.dataset.idServicio));
            });

        } catch (error) {
            appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    }

    async function handleDeleteServicio(id_servicio) {
        if (!confirm('¿Estás seguro de que quieres eliminar este servicio?')) return;
        try {
            const response = await fetch(`${API_URL}api_owner_servicio_eliminar.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_servicio: id_servicio })
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error);
            renderServiciosView(); // Recargar la lista
        } catch (error) {
            alert('Error al eliminar el servicio: ' + error.message);
        }
    }

    function openServicioModal(servicio = null) {
        const isNew = !servicio;
        const modalTitle = isNew ? 'Crear Nuevo Servicio' : 'Editar Servicio';
        const duracionUnidades = ['Minutos', 'Horas', 'Dias'];
        const unidadesOptions = duracionUnidades.map(u => `<option value="${u}" ${!isNew && servicio.duracion_unidad === u ? 'selected' : ''}>${u}</option>`).join('');

        const modalHtml = `
            <div class="modal fade" id="servicioModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${modalTitle}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="modal-error-container"></div>
                            <form id="servicio-form">
                                <div class="mb-3">
                                    <label for="nombre_servicio" class="form-label">Nombre del Servicio</label>
                                    <input type="text" class="form-control" id="nombre_servicio" value="${servicio?.nombre_servicio || ''}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Duración</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="duracion_valor" value="${servicio?.duracion_valor || 30}" required>
                                        <select class="form-select" id="duracion_unidad">${unidadesOptions}</select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="precio" class="form-label">Precio (opcional)</label>
                                    <input type="number" step="0.01" class="form-control" id="precio" value="${servicio?.precio || ''}">
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="save-servicio-btn">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>`;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modalElement = document.getElementById('servicioModal');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        document.getElementById('save-servicio-btn').addEventListener('click', async () => {
            const endpoint = isNew ? `${API_URL}api_owner_servicio_crear.php` : `${API_URL}api_owner_servicio_actualizar.php`;
            const payload = {
                id_servicio: servicio?.id_servicio,
                nombre_servicio: document.getElementById('nombre_servicio').value,
                duracion_valor: document.getElementById('duracion_valor').value,
                duracion_unidad: document.getElementById('duracion_unidad').value,
                precio: document.getElementById('precio').value,
            };
            try {
                const saveResponse = await fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const saveData = await saveResponse.json();
                if (!saveResponse.ok) throw new Error(saveData.error);
                modal.hide();
                renderServiciosView();
            } catch (saveError) {
                document.getElementById('modal-error-container').innerHTML = `<div class="alert alert-danger">${saveError.message}</div>`;
            }
        });

        modalElement.addEventListener('hidden.bs.modal', () => modalElement.remove());
    }

    // --- VISTA DE CRONOGRAMA ---
    async function renderCronogramaView(event) {
        if (event) event.preventDefault();

        // Si se pasa una nueva fecha, la actualizamos. Si no, usamos la que ya tenemos.
        if (event && event.detail && event.detail.newDate) {
            currentDate = event.detail.newDate;
        }

        setActiveNavLink('nav-calendario');
        appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando cronograma...</span></div></div>`;

        try {
            const fechaFiltro = currentDate.toISOString().split('T')[0];
            const response = await fetch(`${API_URL}api_owner_horario_disponible.php?fecha=${fechaFiltro}`);
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'No se pudo cargar el cronograma.');
            }
            const data = await response.json();
            const slots = data.slots;
            const infoNegocio = data.info_negocio;

            let cronogramaHtml = '';
            if (slots.length === 0 && infoNegocio.dias_trabajo && !infoNegocio.dias_trabajo.includes(currentDate.getDay().toString())) {
                cronogramaHtml = `<div class="alert alert-warning">El negocio no trabaja este día (${currentDate.toLocaleDateString('es-ES', { weekday: 'long' })}).</div>`;
            } else if (slots.length === 0) {
                cronogramaHtml = `<div class="alert alert-info">No hay slots disponibles para este día. Revise la configuración de horario.</div>`;
            } else {
                cronogramaHtml = `<div class="list-group">`;
                slots.forEach(slot => {
                    let slotClass = 'list-group-item';
                    let slotContent = `<strong>${slot.start_time} - ${slot.end_time}</strong>`;
                    let actionButton = '';

                    if (slot.status === 'booked') {
                        slotClass += ' list-group-item-danger'; // Cita ocupada
                        slotContent += `<br>Cita: ${slot.cita.nombre_cliente} - ${slot.cita.nombre_servicio} (${slot.cita.estado_cita})`;
                        actionButton = `<button class="btn btn-sm btn-outline-light btn-edit-cita" data-id-cita="${slot.cita.id_cita}">Ver/Editar</button>`;
                    } else {
                        slotClass += ' list-group-item-success'; // Slot disponible
                        slotContent += `<br>Disponible`;
                        actionButton = `<button class="btn btn-sm btn-outline-light btn-agendar-cita" data-start-time="${fechaFiltro} ${slot.start_time}">Agendar</button>`;
                    }

                    cronogramaHtml += `
                        <div class="${slotClass} d-flex justify-content-between align-items-center">
                            <div>${slotContent}</div>
                            <div>${actionButton}</div>
                        </div>
                    `;
                });
                cronogramaHtml += `</div>`;
            }

            appContainer.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Disponibilidad del Día <small class="text-muted fs-5">(${currentDate.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' })})</small></h3>
                    <div class="btn-group">
                        <button class="btn btn-secondary" id="prev-day-cronograma-btn">&laquo; Día Anterior</button>
                        <input type="date" class="form-control" id="date-picker-cronograma" value="${fechaFiltro}">
                        <button class="btn btn-secondary" id="next-day-cronograma-btn">Día Siguiente &raquo;</button>
                    </div>
                </div>
                ${cronogramaHtml}
            `;

            // Añadir listeners para la navegación por fecha
            document.getElementById('prev-day-cronograma-btn').addEventListener('click', () => {
                currentDate.setDate(currentDate.getDate() - 1);
                renderDisponibilidadView();
            });
            document.getElementById('next-day-cronograma-btn').addEventListener('click', () => {
                currentDate.setDate(currentDate.getDate() + 1);
                renderDisponibilidadView();
            });
            document.getElementById('date-picker-cronograma').addEventListener('change', (e) => {
                const [year, month, day] = e.target.value.split('-').map(Number);
                currentDate = new Date(year, month - 1, day);
                renderDisponibilidadView();
            });

            // Añadir listeners para los botones de acción en los slots
            document.querySelectorAll('.btn-edit-cita').forEach(btn => {
                btn.addEventListener('click', (e) => openEditCitaModal(e.target.dataset.idCita));
            });
            document.querySelectorAll('.btn-agendar-cita').forEach(btn => {
                btn.addEventListener('click', (e) => openAgendarCitaModal(e.target.dataset.startTime));
            });

        } catch (error) {
            appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    }

    // --- VISTA DE DISPONIBILIDAD DEL DÍA ---
    async function renderDisponibilidadView(event) {
        if (event) event.preventDefault();

        // Si se pasa una nueva fecha, la actualizamos. Si no, usamos la que ya tenemos.
        if (event && event.detail && event.detail.newDate) {
            currentDate = event.detail.newDate;
        }

        setActiveNavLink('nav-disponibilidad');
        appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando agenda...</span></div></div>`;

        try {
            const fechaFiltro = currentDate.toISOString().split('T')[0];
            const response = await fetch(`${API_URL}api_owner_horario_disponible.php?fecha=${fechaFiltro}`);
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'No se pudo cargar el cronograma.');
            }
            const data = await response.json();
            const slots = data.slots;
            const infoNegocio = data.info_negocio;

            let cronogramaHtml = '';
            if (slots.length === 0 && infoNegocio.dias_trabajo && !infoNegocio.dias_trabajo.includes(currentDate.getDay().toString())) {
                cronogramaHtml = `<div class="alert alert-warning">El negocio no trabaja este día (${currentDate.toLocaleDateString('es-ES', { weekday: 'long' })}).</div>`;
            } else if (slots.length === 0) {
                cronogramaHtml = `<div class="alert alert-info">No hay slots disponibles para este día. Revise la configuración de horario.</div>`;
            } else {
                cronogramaHtml = `<div class="list-group">`;
                slots.forEach(slot => {
                    let slotClass = 'list-group-item';
                    let slotContent = `<strong>${slot.start_time} - ${slot.end_time}</strong>`;
                    let actionButton = '';

                    if (slot.status === 'booked') {
                        slotClass += ' list-group-item-danger'; // Cita ocupada
                        slotContent += `<br>Cita: ${slot.cita.nombre_cliente} - ${slot.cita.nombre_servicio} (${slot.cita.estado_cita})`;
                        actionButton = `<button class="btn btn-sm btn-outline-light btn-edit-cita" data-id-cita="${slot.cita.id_cita}">Ver/Editar</button>`;
                    } else {
                        slotClass += ' list-group-item-success'; // Slot disponible
                        slotContent += `<br>Disponible`;
                        actionButton = `<button class="btn btn-sm btn-outline-light btn-agendar-cita" data-start-time="${fechaFiltro} ${slot.start_time}">Agendar</button>`;
                    }

                    cronogramaHtml += `
                        <div class="${slotClass} d-flex justify-content-between align-items-center">
                            <div>${slotContent}</div>
                            <div>${actionButton}</div>
                        </div>
                    `;
                });
                cronogramaHtml += `</div>`;
            }

            appContainer.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Mi Agenda <small class="text-muted fs-5">(${currentDate.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' })})</small></h3>
                    <div class="btn-group">
                        <button class="btn btn-secondary" id="prev-day-cronograma-btn">&laquo; Día Anterior</button>
                        <input type="date" class="form-control" id="date-picker-cronograma" value="${fechaFiltro}">
                        <button class="btn btn-secondary" id="next-day-cronograma-btn">Día Siguiente &raquo;</button>
                    </div>
                </div>
                ${cronogramaHtml}
            `;

            // Añadir listeners para la navegación por fecha
            document.getElementById('prev-day-cronograma-btn').addEventListener('click', () => {
                currentDate.setDate(currentDate.getDate() - 1);
                renderCronogramaView();
            });
            document.getElementById('next-day-cronograma-btn').addEventListener('click', () => {
                currentDate.setDate(currentDate.getDate() + 1);
                renderCronogramaView();
            });
            document.getElementById('date-picker-cronograma').addEventListener('change', (e) => {
                const [year, month, day] = e.target.value.split('-').map(Number);
                currentDate = new Date(year, month - 1, day);
                renderCronogramaView();
            });

            // Añadir listeners para los botones de acción en los slots
            document.querySelectorAll('.btn-edit-cita').forEach(btn => {
                btn.addEventListener('click', (e) => openEditCitaModal(e.target.dataset.idCita));
            });
            document.querySelectorAll('.btn-agendar-cita').forEach(btn => {
                btn.addEventListener('click', (e) => openAgendarCitaModal(e.target.dataset.startTime));
            });

        } catch (error) {
            appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    }

    async function openAgendarCitaModal(suggestedStartTime) {
        let clientes = [];
        let servicios = [];

        try {
            const [clientesResponse, serviciosResponse] = await Promise.all([
                fetch(`${API_URL}api_owner_clientes.php`),
                fetch(`${API_URL}api_owner_servicios.php`)
            ]);
            clientes = await clientesResponse.json();
            servicios = await serviciosResponse.json();
        } catch (error) {
            alert('Error al cargar datos para agendar cita: ' + error.message);
            return;
        }

        const clientesOptions = clientes.map(c => `<option value="${c.id_cliente}">${c.nombre_completo} (${c.numero_celular})</option>`).join('');
        const serviciosOptions = servicios.map(s => `<option value="${s.id_servicio}">${s.nombre_servicio} (${s.duracion_valor} ${s.duracion_unidad})</option>`).join('');

        const modalHtml = `
            <div class="modal fade" id="agendarCitaModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Agendar Nueva Cita</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="modal-error-container"></div>
                            <form id="agendar-cita-form">
                                <div class="mb-3">
                                    <label for="cliente_cita" class="form-label">Cliente</label>
                                    <select class="form-select" id="cliente_cita" required>
                                        <option value="">Seleccione un cliente...</option>
                                        ${clientesOptions}
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="servicio_cita" class="form-label">Servicio</label>
                                    <select class="form-select" id="servicio_cita" required>
                                        <option value="">Seleccione un servicio...</option>
                                        ${serviciosOptions}
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="fecha_hora_inicio_cita" class="form-label">Fecha y Hora de Inicio</label>
                                    <input type="datetime-local" class="form-control" id="fecha_hora_inicio_cita" value="${suggestedStartTime}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="descripcion_trabajo_cita" class="form-label">Notas (Opcional)</label>
                                    <textarea class="form-control" id="descripcion_trabajo_cita" rows="3"></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="save-new-cita-btn">Agendar Cita</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modalElement = document.getElementById('agendarCitaModal');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        document.getElementById('save-new-cita-btn').addEventListener('click', async () => {
            const payload = {
                id_cliente: document.getElementById('cliente_cita').value,
                id_servicio: document.getElementById('servicio_cita').value,
                fecha_hora_inicio: document.getElementById('fecha_hora_inicio_cita').value,
                descripcion_trabajo: document.getElementById('descripcion_trabajo_cita').value,
            };
            try {
                const saveResponse = await fetch(`${API_URL}api_owner_cita_crear.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const saveData = await saveResponse.json();
                if (!saveResponse.ok) throw new Error(saveData.error);
                modal.hide();
                // Recargar la vista activa
                const activeLink = navMenu.querySelector('.nav-link.active');
                if (activeLink && activeLink.id === 'nav-mi-agenda') {
                    renderMiAgendaView();
                } else if (activeLink && activeLink.id === 'nav-disponibilidad') {
                    renderDisponibilidadView();
                } else { renderCalendarioView(); }
            } catch (saveError) {
                document.getElementById('modal-error-container').innerHTML = `<div class="alert alert-danger">${saveError.message}</div>`;
            }
        });

        modalElement.addEventListener('hidden.bs.modal', () => modalElement.remove());
    }

    // --- VISTA DE MI NEGOCIO ---
    async function renderMiNegocioView(event) {
        if (event) event.preventDefault();
        setActiveNavLink('nav-mi-negocio');
        appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"></div></div>`;

        try {
            // Cargar datos del negocio y de los países en paralelo para optimizar
            const [negocioResponse, paisesResponse] = await Promise.all([
                fetch(`${API_URL}api_owner_negocio_get.php`),
                fetch(`${API_URL}api_paises.php`)
            ]);

            if (!negocioResponse.ok) {
                const errorData = await negocioResponse.json();
                throw new Error(errorData.error || `Error ${response.status}: No se pudo cargar la configuración.`);
            }
            
            const negocio = await negocioResponse.json();
            const paises = await paisesResponse.json();

            // CORRECCIÓN: La variable 'diasSemana' no estaba definida. La añado aquí.
            const diasSemana = { '1': 'Lunes', '2': 'Martes', '3': 'Miércoles', '4': 'Jueves', '5': 'Viernes', '6': 'Sábado', '7': 'Domingo' };
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

            appContainer.innerHTML = `
                <div class="row justify-content-center">
                    <div class="col-md-10 col-lg-8">
                        <div class="card">
                            <div class="card-header"><h3>Configuración de Mi Negocio</h3></div>
                            <div class="card-body">
                                <div id="error-container-negocio"></div>
                                <form id="negocio-form">
                                    <div class="mb-3">
                                        <label for="nombre_negocio" class="form-label">Nombre del Negocio</label>
                                        <input type="text" class="form-control" id="nombre_negocio" value="${negocio.nombre_negocio || ''}">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Teléfono (Identificador)</label>
                                            <input type="tel" class="form-control" value="${negocio.telefono || ''}" readonly disabled>
                                            <small class="form-text text-muted">El teléfono no se puede modificar.</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email de Contacto</label>
                                            <input type="email" class="form-control" id="email" value="${negocio.email || ''}">
                                        </div>
                                    </div>
                                    <hr>
                                    <h5 class="mt-4">Dirección del Negocio</h5>
                                    <div class="mb-3">
                                        <label for="direccion1" class="form-label">Dirección 1</label>
                                        <input type="text" class="form-control" id="direccion1" value="${negocio.direccion1 || ''}">
                                    </div>
                                    <div class="mb-3">
                                        <label for="direccion2" class="form-label">Dirección 2 (Opcional)</label>
                                        <input type="text" class="form-control" id="direccion2" value="${negocio.direccion2 || ''}">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-8 mb-3">
                                            <label for="ciudad" class="form-label">Ciudad</label>
                                            <input type="text" class="form-control" id="ciudad" value="${negocio.ciudad || ''}">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="zip_code" class="form-label">Código Postal</label>
                                            <input type="text" class="form-control" id="zip_code" value="${negocio.zip_code || ''}">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="id_pais" class="form-label">País</label>
                                            <select class="form-select" id="id_pais" required>${paisesOptions}</select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="id_estado" class="form-label">Estado / Provincia</label>
                                            <select class="form-select" id="id_estado" required disabled><option>Cargando...</option></select>
                                        </div>
                                    </div>
                                    <hr>
                                    <h5 class="mt-4">Horario de Trabajo</h5>
                                    <div class="mb-3">
                                        <label class="form-label">Días de Trabajo</label>
                                        <div>${diasTrabajoCheckboxes}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="hora_inicio" class="form-label">Hora de Inicio</label>
                                            <input type="time" class="form-control" id="hora_inicio" value="${negocio.hora_inicio || ''}" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="hora_cierre" class="form-label">Hora de Cierre</label>
                                            <input type="time" class="form-control" id="hora_cierre" value="${negocio.hora_cierre || ''}" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="intervalo_minutos" class="form-label">Intervalo (minutos)</label>
                                            <input type="number" class="form-control" id="intervalo_minutos" value="${negocio.intervalo_minutos || 30}" required>
                                        </div>
                                    </div>
                                    <div class="d-grid mt-3">
                                        <button type="submit" class="btn btn-primary">Guardar Configuración</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Lógica para cargar estados dinámicamente
            const paisSelect = document.getElementById('id_pais');
            const estadoSelect = document.getElementById('id_estado');
            
            async function cargarEstados(idPais, idEstadoSeleccionado) {
                const response = await fetch(`${API_URL}api_estados.php?id_pais=${idPais}`);
                const estados = await response.json();
                estadoSelect.innerHTML = '<option value="">Seleccione un estado...</option>';
                estados.forEach(estado => {
                    estadoSelect.innerHTML += `<option value="${estado.id_estado}" ${estado.id_estado == idEstadoSeleccionado ? 'selected' : ''}>${estado.nombre_estado}</option>`;
                });
                estadoSelect.disabled = false;
            }

            paisSelect.addEventListener('change', () => cargarEstados(paisSelect.value));
            await cargarEstados(paisSelect.value, negocio.id_estado); // Carga inicial con el estado guardado

            document.getElementById('negocio-form').addEventListener('submit', handleUpdateNegocio);

        } catch (error) {
            appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    }

    // --- VISTA DE MI PERFIL ---
    async function renderMiPerfilView(event) {
        if (event) event.preventDefault();
        setActiveNavLink('nav-mi-perfil');
        appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"></div></div>`;

        try {
            const response = await fetch(`${API_URL}api_owner_perfil_detalle.php`);
            if (!response.ok) throw new Error('No se pudo cargar tu perfil.');
            const usuario = await response.json();

            appContainer.innerHTML = `
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header"><h3>Mi Perfil de Usuario</h3></div>
                            <div class="card-body">
                                <div id="error-container-perfil"></div>
                                <form id="perfil-form">
                                    <div class="mb-3">
                                        <label for="nombre_usuario_perfil" class="form-label">Nombre de Usuario</label>
                                        <input type="text" class="form-control" id="nombre_usuario_perfil" value="${usuario.nombre_usuario}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="correo_electronico_perfil" class="form-label">Correo Electrónico</label>
                                        <input type="email" class="form-control" id="correo_electronico_perfil" value="${usuario.correo_electronico}" required>
                                    </div>
                                    <hr>
                                    <h5 class="mt-4">Cambiar Contraseña (Opcional)</h5>
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Contraseña Actual</label>
                                        <input type="password" class="form-control" id="current_password" placeholder="Ingresa tu contraseña actual para cambiarla">
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Nueva Contraseña</label>
                                        <input type="password" class="form-control" id="new_password">
                                    </div>
                                    <div class="d-grid mt-4">
                                        <button type="submit" class="btn btn-primary">Actualizar Perfil</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('perfil-form').addEventListener('submit', async (e) => {
                e.preventDefault();
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
            });

        } catch (error) {
            appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    }

    // Nueva función para manejar la eliminación de citas
    async function handleDeleteCita(idCita) {
        if (!confirm(`¿Estás seguro de que quieres eliminar permanentemente la cita #${idCita}? Esta acción no se puede deshacer.`)) {
            return;
        }

        try {
            const response = await fetch(`${API_URL}api_owner_cita_eliminar.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_cita: idCita })
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error);
            
            // Recargamos la vista de calendario para reflejar el cambio.
            const activeLink = navMenu.querySelector('.nav-link.active');
            if (activeLink && activeLink.id === 'nav-mi-agenda') {
                renderMiAgendaView();
            } else {
                renderCalendarioView();
            }

        } catch (error) {
            alert('Error al eliminar la cita: ' + error.message);
        }
    }

    // Función para marcar el enlace activo en la barra de navegación
    function setActiveNavLink(activeId) {
        navMenu.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
        const activeLink = document.getElementById(activeId);
        if (activeLink) {
            activeLink.classList.add('active');
        }
    }

    // Función genérica para actualizar un reloj
    function updateClock(elementId) {
        const timeElement = document.getElementById(elementId);
        if (timeElement) {
            timeElement.textContent = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        }
    }

    // Iniciar la aplicación mostrando la vista de login
    renderLoginView();
});