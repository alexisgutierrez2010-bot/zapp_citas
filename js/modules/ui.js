// js/modules/ui.js

/**
 * Actualiza la barra de navegación según el estado de login.
 * @param {object} context - El objeto de contexto de la aplicación.
 */
export function updateNavbar(context) {
    const { dom, state, T } = context;
    dom.navMenu.innerHTML = ''; // Limpiar menú

    if (state.ownerActual) {
        // Usuario logueado
        const mainTitle = state.ownerActual.nombre_negocio;
        dom.navbarBrand.innerHTML = mainTitle; // Usar innerHTML para ser consistente

        // Lógica para mostrar días restantes de prueba
        let trialInfoHtml = '';
        if (state.ownerActual.fecha_desactivacion) {
            const hoy = new Date();
            const fin = new Date(state.ownerActual.fecha_desactivacion);
            const diffTime = fin - hoy;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            let badgeClass = 'bg-success';
            let trialText = (T.spa_owner_trial_days_left || 'Trial: {days} days left').replace('{days}', diffDays);
            if (diffDays <= 7) badgeClass = 'bg-warning text-dark';
            if (diffDays <= 0) { badgeClass = 'bg-danger'; trialText = T.spa_owner_trial_ended || 'Trial Ended'; }
            trialInfoHtml = `<span class="navbar-text me-3"><span class="badge ${badgeClass}">${trialText}</span></span>`;
        }

        dom.navMenu.innerHTML = `
            <li class="nav-item"><a class="nav-link" href="#" data-view="agenda">${T.spa_owner_nav_my_agenda || 'Mi Agenda'}</a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-view="calendario">${T.spa_owner_nav_calendar || 'Calendar'}</a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-view="disponibilidad">${T.spa_owner_nav_availability || 'Availability'}</a></li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    ${T.spa_owner_nav_management || 'Management'}
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" data-view="clientes">${T.spa_owner_nav_clients || 'My Clients'}</a></li>
                    <li><a class="dropdown-item" href="#" data-view="servicios">${T.spa_owner_nav_services || 'My Services'}</a></li>
                </ul>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-gear-fill"></i> ${T.spa_owner_nav_admin || 'Admin'}
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" data-view="negocio">${T.spa_owner_nav_business || 'My Business'}</a></li>
                    <li><a class="dropdown-item" href="#" data-view="perfil">${T.spa_owner_nav_profile || 'My Profile'}</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" data-action="cierre-citas">${T.spa_owner_nav_close_appointments || 'Close Appointments'}</a></li>
                </ul>
            </li>
            <li class="nav-item"><a class="nav-link" href="#" data-action="logout"><i class="bi bi-box-arrow-right"></i> ${T.logout || 'Logout'}</a></li>
            <li class="nav-item dropdown ms-lg-2">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-globe"></i> ${state.currentLang.toUpperCase()}</a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="?lang=es">Español</a></li>
                    <li><a class="dropdown-item" href="?lang=en">English</a></li>
                </ul>
            </li>
            <li class="nav-item">${trialInfoHtml}</li>
            <li class="nav-item"><span id="owner-clock" class="navbar-text"></span></li>
        `;
        startClock(context); // Se pasa el contexto a startClock
    } else {
        // Usuario no logueado
        dom.navMenu.innerHTML = ` 
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-globe"></i> ${state.currentLang.toUpperCase()}</a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="?lang=es">Español</a></li>
                    <li><a class="dropdown-item" href="?lang=en">English</a></li>
                </ul>
            </li>
        `;
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
        clockElement.textContent = now.toLocaleTimeString(context.state.currentLang === 'es' ? 'es-ES' : 'en-US');
    };
    updateTime();
    context.state.clockInterval = setInterval(updateTime, 1000);
}