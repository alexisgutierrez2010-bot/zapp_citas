document.addEventListener('DOMContentLoaded', function() {
    const API_URL = ''; // Todo está en la misma carpeta raíz
    const navbarBrand = document.getElementById('navbar-brand-title');
    const appContainer = document.getElementById('app-container');
    const navMenu = document.getElementById('nav-menu');

    let clienteActual = null; // Variable para mantener el estado del cliente logueado

    // Función para actualizar la barra de navegación
    function updateNavbar() {
        navMenu.innerHTML = ''; // Limpiar el menú
        if (clienteActual) {
            // Si hay un cliente logueado, mostrar su menú
            navMenu.innerHTML = `
                <li class="nav-item">
                    <a class="nav-link" href="#" id="nav-ultima-cita">Última Cita</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" id="nav-perfil">Mi Perfil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" id="nav-historial">Historia de Citas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" id="nav-agendar">Horarios Disponibles</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" id="nav-salir">Salir</a>
                </li>
            `;
            // Añadir listeners a los nuevos elementos del menú
            document.getElementById('nav-ultima-cita').addEventListener('click', (e) => {
                e.preventDefault();
                renderVistaPrincipal(clienteActual);
            });
            document.getElementById('nav-perfil').addEventListener('click', renderPerfilView);
            document.getElementById('nav-historial').addEventListener('click', renderHistorialView);
            document.getElementById('nav-agendar').addEventListener('click', renderAgendarCitaView);
            document.getElementById('nav-salir').addEventListener('click', handleLogout);
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

    async function cargarDatosNegocio() {
        try {
            // Al inicio, mostramos un título genérico.
            const genericTitle = "Gestión de Citas por Clientes";
            document.title = genericTitle;
            navbarBrand.textContent = genericTitle;
            updateNavbar();
        } catch (error) {
            navbarBrand.textContent = 'Error';
            appContainer.innerHTML = `<div class="alert alert-danger">Error al cargar la configuración inicial: ${error.message}</div>`;
            console.error("Error en cargarDatosNegocio:", error);
        }
    }

    async function cargarDatosNegocioEspecifico(idNegocio) {
        const response = await fetch(`${API_URL}api_negocio_publico.php?id_negocio=${idNegocio}`);
        const negocio = await response.json();
        document.title = `Portal de Citas - ${negocio.nombre_negocio}`;
        navbarBrand.textContent = negocio.nombre_negocio;
    }

    // Función para renderizar la vista de Login
    async function renderLoginView() {
        try {
            // 1. Obtener datos necesarios en paralelo (CAPTCHA y lista de países)
            const [captchaResponse, paisesResponse] = await Promise.all([
                fetch(`${API_URL}api_cliente_login.php`),
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
            const captchaHash = captchaData.captcha_hash;

            // Crear las opciones para el selector de países
            const paisesOptions = paises.map(pais => 
                `<option value="${pais.codigo_telefono}" ${pais.id_pais == 1 ? 'selected' : ''}>${pais.nombre_pais} (${pais.codigo_telefono})</option>`
            ).join('');

            // 2. Construir el HTML del formulario de login
            appContainer.innerHTML = `
                <div class="row justify-content-center">
                    <div class="col-md-5 col-lg-4">
                        <div class="card">
                            <div class="card-header text-center bg-primary text-white"><h3>Bienvenido</h3></div>
                            <div class="card-body">
                                <div id="error-container"></div>
                                <form id="login-form">
                                    <div class="mb-3">
                                        <label for="numero_celular" class="form-label">Tu Número de Teléfono</label>
                                        <div class="input-group">
                                            <select class="form-select" id="country_code" name="country_code" style="max-width: 150px;">
                                                ${paisesOptions}
                                            </select>
                                            <input type="tel" class="form-control" id="numero_celular" name="numero_celular" placeholder="Ej: 4121234567" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 text-center">
                                        <label for="captcha" class="form-label">Código de Seguridad</label>
                                        <div class="p-2 bg-dark text-white rounded font-monospace fs-4" style="letter-spacing: 5px;">
                                            ${captchaHash}
                                        </div>
                                        <div class="mx-auto" style="max-width: 200px;">
                                            <input type="text" name="captcha" id="captcha" class="form-control mt-2 text-center" autocomplete="off" required>
                                        </div>
                                    <small class="form-text text-muted">El código expira en 2 minutos.</small>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">Ingresar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // 3. Añadir el listener para el envío del formulario
            document.getElementById('login-form').addEventListener('submit', handleLogin);
        } catch (error) {
            appContainer.innerHTML = `<div class="alert alert-danger">Error al cargar el formulario de inicio de sesión: ${error.message}</div>`;
            console.error("Error en renderLoginView:", error);
        }
    }

    // Función para manejar el intento de login
    async function handleLogin(event) {
        event.preventDefault();
        const countryCode = document.getElementById('country_code').value;
        const phoneNumber = document.getElementById('numero_celular').value;
        const captcha = document.getElementById('captcha').value;
        const errorContainer = document.getElementById('error-container');

        // CORRECCIÓN: Limpiamos espacios del código de país y del número, y luego los unimos con UN solo espacio.
        const fullPhoneNumber = `${countryCode.trim()} ${phoneNumber.replace(/\s/g, '')}`;

        try {
            const response = await fetch(`${API_URL}api_cliente_login.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ numero_celular: fullPhoneNumber, captcha: captcha })
            });

            const data = await response.json();

            if (!response.ok) {
                // Si el error es 404 (Not Found), es un cliente nuevo.
                if (response.status === 404) {
                    // En lugar de ir directo al registro, preguntamos al usuario.
                    renderConfirmacionRegistroView(fullPhoneNumber);
                    return; // Detenemos la ejecución de esta función
                } else {
                    // Para otros errores (ej. CAPTCHA incorrecto), lanzamos el error.
                    throw new Error(data.error || 'Ocurrió un error desconocido.');
                }
            }

            // ¡Login exitoso! Mostramos la vista principal del cliente.
            clienteActual = data; // Guardamos el estado del cliente
            updateNavbar(); // Actualizamos el menú para mostrar las opciones del cliente
            await cargarDatosNegocioEspecifico(clienteActual.id_negocio); // Cargamos el nombre del negocio correcto
            renderVistaPrincipal(clienteActual);

        } catch (error) {
            errorContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
            // Recargar la vista de login para obtener un nuevo CAPTCHA
            setTimeout(renderLoginView, 2000);
        }
    }

    function handleLogout(event) {
        event.preventDefault();
        clienteActual = null; // Limpiamos el estado del cliente
        updateNavbar(); // Limpiamos el menú
        renderLoginView(); // Volvemos a la vista de login
    }

    // Función para renderizar la vista principal del cliente (después del login)
    async function renderVistaPrincipal(cliente) {
        setActiveNavLink('nav-ultima-cita');
        appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando tus citas...</span></div></div>`;

        try {
            const response = await fetch(`${API_URL}api_cliente_citas.php?id_cliente=${cliente.id_cliente}`);
            if (!response.ok) {
                throw new Error('No se pudieron cargar los datos de tus citas.');
            }
            const data = await response.json();
            const cita = data.cita_reciente;

            let citaHtml = '';
            if (cita) {
                // Formatear la fecha y hora para que sea legible
                const fecha = new Date(cita.fecha_hora_inicio);
                const opcionesFecha = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                const opcionesHora = { hour: 'numeric', minute: 'numeric', hour12: true };

                citaHtml = `
                    <div class="card">
                        <div class="card-header">
                            Tu Cita Más Reciente
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">${cita.nombre_servicio}</h5>
                            <p class="card-text">
                                <strong>Fecha:</strong> ${fecha.toLocaleDateString('es-ES', opcionesFecha)}<br>
                                <strong>Hora:</strong> ${fecha.toLocaleTimeString('es-ES', opcionesHora)}<br>
                                <strong>Estado:</strong> <span class="badge bg-info text-dark">${cita.estado_cita}</span><br>
                                ${cita.descripcion_trabajo ? `<strong>Descripción:</strong> ${cita.descripcion_trabajo}` : ''}
                            </p>
                            <p class="card-text"><small class="text-muted">ID de Cita: ${cita.id_cita}</small></p>
                            ${cita.estado_cita === 'Pendiente' ? `<a href="#" class="btn btn-success" data-id-cita="${cita.id_cita}" id="btn-confirmar-cita">Confirmar Cita</a>` : ''}
                            ${cita.estado_cita === 'Pendiente' || cita.estado_cita === 'Confirmada' ? `<a href="#" class="btn btn-danger ms-2" data-id-cita="${cita.id_cita}" id="btn-cancelar-cita">Cancelar Cita</a>` : ''}
                        </div>
                    </div>
                `;
            } else {
                citaHtml = `
                    <div class="alert alert-info">Aún no tienes citas registradas.</div>
                    <button class="btn btn-primary" id="btn-agendar-desde-dash">Agendar Primera Cita</button>
                `;
            }

            appContainer.innerHTML = `
                <div class="row">
                    <div class="col-12">
                        <h3>¡Hola, ${cliente.nombre_completo}!</h3>
                        <hr>
                    </div>
                    <div class="col-md-8">
                        ${citaHtml}
                    </div>
                </div>
            `;

            // Añadir listeners para los botones de acción, si existen
            const btnCancelar = document.getElementById('btn-cancelar-cita');
            if (btnCancelar) {
                btnCancelar.addEventListener('click', handleCancelarCita);
            }
            const btnConfirmar = document.getElementById('btn-confirmar-cita');
            if (btnConfirmar) {
                btnConfirmar.addEventListener('click', handleConfirmarCita);
            }
            // Listener para el botón de agendar desde el dashboard vacío
            const btnAgendarDash = document.getElementById('btn-agendar-desde-dash');
            if (btnAgendarDash) {
                btnAgendarDash.addEventListener('click', renderAgendarCitaView);
            }

        } catch (error) {
            appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    }

    async function handleCancelarCita(event) {
        event.preventDefault();
        const idCita = event.target.dataset.idCita;

        if (!confirm('¿Estás seguro de que quieres cancelar esta cita?')) {
            return;
        }

        try {
            const response = await fetch(`${API_URL}api_cliente_accion_cita.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_cita: idCita, id_cliente: clienteActual.id_cliente, accion: 'cancelar' })
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error);
            
            // Si todo sale bien, recargamos la vista principal para que se actualice el estado
            renderVistaPrincipal(clienteActual);
        } catch (error) {
            alert('Error al cancelar la cita: ' + error.message);
        }
    }

    async function handleConfirmarCita(event) {
        event.preventDefault();
        const idCita = event.target.dataset.idCita;

        if (!confirm('¿Estás seguro de que quieres confirmar tu asistencia a esta cita?')) {
            return;
        }

        try {
            const response = await fetch(`${API_URL}api_cliente_accion_cita.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_cita: idCita, id_cliente: clienteActual.id_cliente, accion: 'confirmar' })
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error);
            
            // Si todo sale bien, recargamos la vista principal para que se actualice el estado
            renderVistaPrincipal(clienteActual);
        } catch (error) {
            alert('Error al confirmar la cita: ' + error.message);
        }
    }

    // --- Vistas Placeholder para las nuevas opciones del menú ---

    async function renderPerfilView(event) {
        event.preventDefault();
        setActiveNavLink('nav-perfil');
        appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"></div></div>`;

        try {
            // Cargar datos del perfil y de los países en paralelo
            const [perfilResponse, paisesResponse] = await Promise.all([
                fetch(`${API_URL}api_cliente_perfil.php?id_cliente=${clienteActual.id_cliente}`),
                fetch(`${API_URL}api_paises.php`)
            ]);

            if (!perfilResponse.ok) throw new Error('No se pudo cargar tu perfil.');
            
            const perfil = await perfilResponse.json();
            const paises = await paisesResponse.json();

            const paisesOptions = paises.map(pais => 
                `<option value="${pais.id_pais}" ${perfil.id_pais == pais.id_pais ? 'selected' : ''}>${pais.nombre_pais}</option>`
            ).join('');

            appContainer.innerHTML = `
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header"><h3>Mi Perfil</h3></div>
                            <div class="card-body">
                                <div id="error-container-perfil"></div>
                                <form id="perfil-form">
                                    <div class="mb-3">
                                        <label for="nombre_completo_perfil" class="form-label">Nombre Completo</label>
                                        <input type="text" class="form-control" id="nombre_completo_perfil" value="${perfil.nombre_completo}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="numero_celular_perfil" class="form-label">Número de Teléfono (Login)</label>
                                        <input type="tel" class="form-control" id="numero_celular_perfil" value="${perfil.numero_celular || ''}" readonly disabled>
                                        <small class="form-text text-muted">Este número no se puede modificar.</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="correo_electronico_perfil" class="form-label">Correo Electrónico</label>
                                        <input type="email" class="form-control" id="correo_electronico_perfil" value="${perfil.correo_electronico}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="direccion1_perfil" class="form-label">Dirección 1</label>
                                        <input type="text" class="form-control" id="direccion1_perfil" value="${perfil.direccion1 || ''}">
                                    </div>
                                    <div class="mb-3">
                                        <label for="direccion2_perfil" class="form-label">Dirección 2 (Opcional)</label>
                                        <input type="text" class="form-control" id="direccion2_perfil" value="${perfil.direccion2 || ''}">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-8 mb-3">
                                            <label for="ciudad_perfil" class="form-label">Ciudad</label>
                                            <input type="text" class="form-control" id="ciudad_perfil" value="${perfil.ciudad || ''}">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="zip_code_perfil" class="form-label">Zip Code</label>
                                            <input type="text" class="form-control" id="zip_code_perfil" value="${perfil.zip_code || ''}">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="id_pais_perfil" class="form-label">País</label>
                                            <select class="form-select" id="id_pais_perfil" required>${paisesOptions}</select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="id_estado_perfil" class="form-label">Estado / Provincia</label>
                                            <select class="form-select" id="id_estado_perfil" required disabled><option>Cargando...</option></select>
                                        </div>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">Actualizar Perfil</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Lógica para cargar estados dinámicamente
            const paisSelect = document.getElementById('id_pais_perfil');
            const estadoSelect = document.getElementById('id_estado_perfil');
            
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
            await cargarEstados(paisSelect.value, perfil.id_estado); // Carga inicial con el estado guardado

            document.getElementById('perfil-form').addEventListener('submit', handleUpdatePerfil);

        } catch (error) {
            appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    }

    async function handleUpdatePerfil(event) {
        event.preventDefault();
        const errorContainer = document.getElementById('error-container-perfil');
        const form = document.getElementById('perfil-form');
        const submitButton = form.querySelector('button[type="submit"]');
        errorContainer.innerHTML = ''; // Limpiar errores previos

        const payload = {
            id_cliente: clienteActual.id_cliente,
            nombre_completo: document.getElementById('nombre_completo_perfil').value,
            correo_electronico: document.getElementById('correo_electronico_perfil').value,
            id_pais: document.getElementById('id_pais_perfil').value,
            id_estado: document.getElementById('id_estado_perfil').value,
            direccion1: document.getElementById('direccion1_perfil').value,
            direccion2: document.getElementById('direccion2_perfil').value,
            ciudad: document.getElementById('ciudad_perfil').value,
            zip_code: document.getElementById('zip_code_perfil').value,
        };

        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Actualizando...`;

        try {
            const response = await fetch(`${API_URL}api_cliente_perfil.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'No se pudo actualizar el perfil.');
            }

            // Actualizar el nombre en el objeto del cliente local
            clienteActual.nombre_completo = payload.nombre_completo;

            errorContainer.innerHTML = `<div class="alert alert-success">${data.message}</div>`;

        } catch (error) {
            errorContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Actualizar Perfil';
        }
    }

    async function renderHistorialView(event) {
        event.preventDefault();
        setActiveNavLink('nav-historial');
        appContainer.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"></div></div>`;

        try {
            const response = await fetch(`${API_URL}api_cliente_historial.php?id_cliente=${clienteActual.id_cliente}`);
            if (!response.ok) {
                throw new Error('No se pudo cargar tu historial de citas.');
            }
            const historial = await response.json();

            let historialHtml = '';
            if (historial.length > 0) {
                const status_colors = {
                    'Pendiente': 'bg-info text-dark', 'Completada': 'bg-success',
                    'Cancelada': 'bg-danger', 'Pospuesta': 'bg-warning text-dark',
                    'No Asistió': 'bg-secondary', 'Confirmada': 'bg-primary',
                };

                const citasRows = historial.map((cita, index) => {
                    const fecha = new Date(cita.fecha_hora_inicio);
                    const estado = cita.estado_cita;
                    const color_clase = status_colors[estado] ?? 'bg-light text-dark';
                    return `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${fecha.toLocaleDateString('es-ES')}</td>
                            <td>${fecha.toLocaleTimeString('es-ES', { hour: 'numeric', minute: 'numeric' })}</td>
                            <td>${cita.nombre_servicio}</td>
                            <td>${cita.descripcion_trabajo || '<small class="text-muted">N/A</small>'}</td>
                            <td><span class="badge ${color_clase}">${estado}</span></td>
                            <td><small class="text-muted">${cita.id_cita}</small></td>
                        </tr>
                    `;
                }).join('');

                historialHtml = `
                    <table class="table table-striped table-hover">
                        <thead class="table-dark"><tr><th>#</th><th>Fecha</th><th>Hora</th><th>Servicio</th><th>Descripción</th><th>Estado</th><th>ID</th></tr></thead>
                        <tbody>${citasRows}</tbody>
                    </table>`;
            } else {
                historialHtml = `<div class="alert alert-info">No tienes citas en tu historial.</div>`;
            }
            appContainer.innerHTML = `<h3>Historial de Citas</h3>${historialHtml}`;
        } catch (error) {
            appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    }

    // Función para mostrar la confirmación antes de registrar
    function renderConfirmacionRegistroView(numeroCelular) {
        appContainer.innerHTML = `
            <div class="row justify-content-center">
                <div class="col-md-7">
                    <div class="card text-center">
                        <div class="card-header"><h3>Cliente No Encontrado</h3></div>
                        <div class="card-body">
                            <p class="lead">El número de teléfono <strong>${numeroCelular}</strong> no está registrado en nuestro sistema.</p>
                            <p>¿Deseas registrarte como un nuevo cliente?</p>
                            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-4">
                                <button id="confirm-register-btn" class="btn btn-primary btn-lg px-4 gap-3">Sí, Registrarme</button>
                                <button id="cancel-register-btn" class="btn btn-outline-secondary btn-lg px-4">No, Volver</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('confirm-register-btn').addEventListener('click', () => {
            renderRegistroView(numeroCelular);
        });

        document.getElementById('cancel-register-btn').addEventListener('click', () => {
            renderLoginView();
        });
    }

    // Función para renderizar la vista de Registro
    async function renderRegistroView(numeroCelular) {
        // Necesitamos la lista de países y estados
        const [paisesResponse, negociosResponse] = await Promise.all([
            fetch(`${API_URL}api_paises.php`),
            fetch(`${API_URL}api_negocios_lista_publica.php`)
        ]);

        const paises = await paisesResponse.json();
        const negocios = await negociosResponse.json();

        const paisesOptions = paises.map(pais => 
            `<option value="${pais.id_pais}" ${pais.id_pais == 1 ? 'selected' : ''}>${pais.nombre_pais}</option>`
        ).join('');

        const negociosOptions = negocios.map(negocio => 
            `<option value="${negocio.id_negocio}">${negocio.nombre_negocio}</option>`
        ).join('');

        appContainer.innerHTML = `
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header text-center"><h3>Completa tu Registro</h3></div>
                        <div class="card-body">
                            <p class="text-muted">Hemos detectado que eres nuevo. Por favor, completa tus datos para continuar.</p>
                            <div id="error-container"></div>
                            <form id="registro-form">
                                <!-- Campo oculto con el número completo para enviarlo a la API -->
                                <input type="hidden" id="numero_celular_registro" value="${numeroCelular}">
                                
                                <div class="mb-3">
                                    <label class="form-label">Número de Teléfono</label>
                                    <!-- Representación visual para el usuario (deshabilitada) -->
                                    <div class="input-group">
                                        <span class="input-group-text" style="max-width: 150px;">${numeroCelular.split(' ')[0]}</span>
                                        <input type="tel" class="form-control" value="${numeroCelular.split(' ')[1] || ''}" readonly>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="nombre_completo" class="form-label">Nombre Completo</label>
                                    <input type="text" class="form-control" id="nombre_completo" required>
                                </div>
                                <div class="mb-3">
                                    <label for="correo_electronico" class="form-label">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="correo_electronico" required>
                                </div>
                                <div class="mb-3">
                                    <label for="direccion1" class="form-label">Dirección 1</label>
                                    <input type="text" class="form-control" id="direccion1">
                                </div>
                                <div class="mb-3">
                                    <label for="direccion2" class="form-label">Dirección 2 (Opcional)</label>
                                    <input type="text" class="form-control" id="direccion2">
                                </div>
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label for="ciudad" class="form-label">Ciudad</label>
                                        <input type="text" class="form-control" id="ciudad">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="zip_code" class="form-label">Zip Code</label>
                                        <input type="text" class="form-control" id="zip_code">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="id_pais" class="form-label">País</label>
                                        <select class="form-select" id="id_pais" required>${paisesOptions}</select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="id_estado" class="form-label">Estado / Provincia</label>
                                        <select class="form-select" id="id_estado" required disabled><option>Seleccione un país</option></select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="id_negocio" class="form-label">¿A qué negocio deseas registrarte?</label>
                                    <select class="form-select" id="id_negocio" required>${negociosOptions}</select>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success">Registrarme</button>
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
        
        async function cargarEstados(idPais) {
            const response = await fetch(`${API_URL}api_estados.php?id_pais=${idPais}`);
            const estados = await response.json();
            estadoSelect.innerHTML = '<option value="">Seleccione un estado...</option>';
            estados.forEach(estado => {
                estadoSelect.innerHTML += `<option value="${estado.id_estado}">${estado.nombre_estado}</option>`;
            });
            estadoSelect.disabled = false;
        }

        paisSelect.addEventListener('change', () => cargarEstados(paisSelect.value));
        cargarEstados(paisSelect.value); // Carga inicial

        document.getElementById('registro-form').addEventListener('submit', handleRegistro);
    }

    // Función para manejar el envío del formulario de registro
    async function handleRegistro(event) {
        event.preventDefault();
        const errorContainer = document.getElementById('error-container');
        errorContainer.innerHTML = ''; // Limpiar errores previos

        const payload = {
            numero_celular: document.getElementById('numero_celular_registro').value,
            nombre_completo: document.getElementById('nombre_completo').value,
            correo_electronico: document.getElementById('correo_electronico').value,
            id_pais: document.getElementById('id_pais').value,
            id_estado: document.getElementById('id_estado').value,
            id_negocio: document.getElementById('id_negocio').value,
            direccion1: document.getElementById('direccion1').value,
            direccion2: document.getElementById('direccion2').value,
            ciudad: document.getElementById('ciudad').value,
            zip_code: document.getElementById('zip_code').value,
        };

        // Validación simple en el frontend
        if (!payload.nombre_completo || !payload.correo_electronico || !payload.id_pais || !payload.id_estado || !payload.id_negocio) { // La dirección no es obligatoria aquí
            errorContainer.innerHTML = `<div class="alert alert-danger">Por favor, complete todos los campos.</div>`;
            return;
        }

        try {
            const response = await fetch(`${API_URL}api_cliente_registro.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'No se pudo completar el registro.');
            }

            appContainer.innerHTML = `<div class="alert alert-success"><h4>¡Registro Exitoso!</h4><p>${data.message}</p><p>Ahora serás redirigido a la pantalla de inicio de sesión.</p></div>`;
            setTimeout(renderLoginView, 4000); // Redirigir al login después de 4 segundos

        } catch (error) {
            errorContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    }

    // --- VISTA PARA AGENDAR NUEVA CITA (FUNCIONALIDAD AÑADIDA) ---

    async function renderAgendarCitaView(event) {
        if (event) event.preventDefault();
        setActiveNavLink('nav-agendar');
        appContainer.innerHTML = `<div class="text-center"><div class="spinner-border"></div></div>`;

        try {
            // Paso 1: Cargar y mostrar los servicios disponibles para el negocio del cliente.
            const response = await fetch(`${API_URL}api_servicios_publicos.php?id_negocio=${clienteActual.id_negocio}`);
            if (!response.ok) throw new Error('No se pudieron cargar los servicios del negocio.');
            const servicios = await response.json();

            if (servicios.length === 0) {
                appContainer.innerHTML = `<div class="alert alert-warning">Este negocio no tiene servicios disponibles para agendar en este momento.</div>`;
                return;
            }

            const serviciosHtml = servicios.map(s => `
                <a href="#" class="list-group-item list-group-item-action btn-seleccionar-servicio" data-id-servicio="${s.id_servicio}" data-nombre-servicio="${s.nombre_servicio}">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">${s.nombre_servicio}</h5>
                        <small>Duración: ${s.duracion_valor} ${s.duracion_unidad}</small>
                    </div>
                    <p class="mb-1">Precio: ${s.precio ? `$${parseFloat(s.precio).toFixed(2)}` : 'Consultar'}</p>
                </a>
            `).join('');

            appContainer.innerHTML = `
                <h3>Agendar Nueva Cita</h3>
                <div class="card">
                    <div class="card-header">Paso 1 de 2: Selecciona un servicio</div>
                    <div class="list-group list-group-flush">${serviciosHtml}</div>
                </div>
            `;

            document.querySelectorAll('.btn-seleccionar-servicio').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const idServicio = e.currentTarget.dataset.idServicio;
                    const nombreServicio = e.currentTarget.dataset.nombreServicio;
                    renderSeleccionHorarioView(idServicio, nombreServicio);
                });
            });

        } catch (error) {
            appContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    }

    function renderSeleccionHorarioView(idServicio, nombreServicio) {
        // Paso 2: Mostrar calendario y contenedor para los horarios.
        const hoy = new Date().toISOString().split('T')[0];
        appContainer.innerHTML = `
            <h3>Agendar: ${nombreServicio}</h3>
            <div class="card">
                <div class="card-header">Paso 2 de 2: Elige la fecha y hora</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label for="fecha-cita" class="form-label">Selecciona una fecha:</label>
                            <input type="date" id="fecha-cita" class="form-control" min="${hoy}">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Horarios Disponibles:</label>
                            <div id="slots-container" class="p-3 bg-light rounded" style="min-height: 100px;">
                                <p class="text-muted text-center">Selecciona una fecha para ver los horarios.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-secondary" id="btn-volver-servicios">Volver a Servicios</button>
                </div>
            </div>
        `;

        document.getElementById('btn-volver-servicios').addEventListener('click', renderAgendarCitaView);

        const fechaInput = document.getElementById('fecha-cita');
        const slotsContainer = document.getElementById('slots-container');

        fechaInput.addEventListener('change', async () => {
            const fechaSeleccionada = fechaInput.value;
            if (!fechaSeleccionada) return;

            slotsContainer.innerHTML = `<div class="text-center"><div class="spinner-border spinner-border-sm"></div> Buscando horarios...</div>`;

            try {
                const response = await fetch(`${API_URL}api_cliente_horario_disponible.php?id_negocio=${clienteActual.id_negocio}&id_servicio=${idServicio}&fecha=${fechaSeleccionada}`);
                if (!response.ok) throw new Error('No se pudo cargar la disponibilidad.');
                const slots = await response.json();

                if (slots.length === 0) {
                    slotsContainer.innerHTML = `<p class="text-muted text-center">No hay horarios disponibles para este día. Por favor, elige otra fecha.</p>`;
                } else {
                    const slotsHtml = slots.map(slot => 
                        `<button class="btn btn-outline-primary m-1 btn-seleccionar-slot" data-fecha-hora="${fechaSeleccionada} ${slot}">${slot}</button>`
                    ).join('');
                    slotsContainer.innerHTML = `<div class="d-flex flex-wrap">${slotsHtml}</div>`;

                    document.querySelectorAll('.btn-seleccionar-slot').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            const fechaHora = e.target.dataset.fechaHora;
                            handleConfirmarAgendamiento(idServicio, fechaHora, nombreServicio);
                        });
                    });
                }
            } catch (error) {
                slotsContainer.innerHTML = `<p class="text-danger text-center">${error.message}</p>`;
            }
        });
    }

    async function handleConfirmarAgendamiento(idServicio, fechaHora, nombreServicio) {
        const fechaObj = new Date(fechaHora);
        const fechaFormateada = fechaObj.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' });
        const horaFormateada = fechaObj.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });

        if (!confirm(`¿Confirmas que deseas agendar una cita para "${nombreServicio}" el día ${fechaFormateada} a las ${horaFormateada}?`)) {
            return;
        }

        try {
            const payload = { id_cliente: clienteActual.id_cliente, id_negocio: clienteActual.id_negocio, id_servicio: idServicio, fecha_hora_inicio: fechaHora };
            const response = await fetch(`${API_URL}api_cliente_agendar_cita.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error);

            alert(data.message);
            renderVistaPrincipal(clienteActual); // Volver al dashboard para ver la nueva cita.
        } catch (error) {
            alert(`Error al agendar la cita: ${error.message}`);
        }
    }

    // Iniciar la aplicación: Cargar datos del negocio y luego mostrar el login
    cargarDatosNegocio()
        .then(() => renderLoginView())
        .catch(error => {
            console.error("Error fatal al iniciar la aplicación:", error);
            appContainer.innerHTML = `<div class="alert alert-danger">Error fatal al iniciar la aplicación: ${error.message}</div>`;
        });
});