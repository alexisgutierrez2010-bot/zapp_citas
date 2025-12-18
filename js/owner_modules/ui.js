// js/modules/ui.js

/**
 * Actualiza el fondo del body basado en si el negocio tiene una imagen personalizada.
 * @param {object} ownerData - Los datos del propietario/negocio.
 */
function updateBodyBackground(ownerData) {
    const imageUrl = ownerData.has_background ? `get_image.php?id=${ownerData.id_negocio}&t=${new Date().getTime()}` : '';
    const gradient = 'linear-gradient(to right, rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.3))';
    document.body.style.backgroundImage = imageUrl ? `${gradient}, url('${imageUrl}')` : '';
}

/**
 * Actualiza los ítems dinámicos de la barra de navegación.
 * @param {object} context - El objeto de contexto de la aplicación.
 */
export function updateNavbar(context) {
    const { dom, state } = context;
    const navMenuContainer = document.getElementById('nav-menu-items');

    updateBodyBackground(state.ownerActual || {});

    if (state.ownerActual) {
        let trialInfoHtml = '';
        if (state.ownerActual.fecha_desactivacion) {
            const hoy = new Date();
            const fin = new Date(state.ownerActual.fecha_desactivacion);
            const diffTime = fin - hoy;
            const diffDays = Math.max(0, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));
            
            let badgeClass = 'bg-success';
            let trialText = `Quedan ${diffDays} días`;
            if (diffDays <= 7) badgeClass = 'bg-warning text-dark';
            if (diffDays <= 0) { badgeClass = 'bg-danger'; trialText = 'Prueba finalizada'; }
            trialInfoHtml = `<span class="navbar-text me-3"><span class="badge ${badgeClass}">${trialText}</span></span>`;
        }

        dom.navbarBrand.textContent = state.ownerActual.nombre_negocio;

        const navItemsHtml = `
            ${trialInfoHtml}
            <a class="nav-link" href="#" data-view="agenda">Mi Agenda</a>
            <a class="nav-link" href="#" data-view="calendario">Calendario</a>
            <a class="nav-link" href="#" data-view="clientes">Mis Clientes</a>
            <a class="nav-link" href="#" data-view="servicios">Mis Servicios</a>
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAdmin" role="button" data-bs-toggle="dropdown" aria-expanded="false">Administración</a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownAdmin">
                    <li><a class="dropdown-item" href="#" data-view="negocio">Mi Negocio</a></li>
                    <li><a class="dropdown-item" href="#" data-view="dashboard">Dashboard</a></li>
                    <li><a class="dropdown-item" href="#" data-view="perfil">Mi Perfil</a></li>
                </ul>
            </div>
            <div class="nav-item ms-lg-2">
                <a class="nav-link" href="#" data-action="logout">Cerrar Sesión</a>
            </div>
        `;

        if (navMenuContainer) {
            navMenuContainer.innerHTML = navItemsHtml;
        }

        startClock(context); // Se pasa el contexto a startClock
    } else {
        dom.navbarBrand.textContent = 'App Propietario';
        if (navMenuContainer) navMenuContainer.innerHTML = '';
        document.body.style.backgroundImage = ''; // Limpiar fondo al cerrar sesión
    }
}

export function setActiveNavLink(navMenu, viewName) {
    navMenu.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
    const activeLink = navMenu.querySelector(`[data-view="${viewName}"]`);
    if (activeLink) activeLink.classList.add('active');
}

function startClock(context) {
    const clockElement = document.getElementById('owner-clock');
    if (!clockElement) return;
    
    const updateTime = () => {
        const now = new Date();
        clockElement.textContent = now.toLocaleTimeString('es-ES');
    };
    updateTime();
    context.state.clockInterval = setInterval(updateTime, 1000);
}