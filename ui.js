// c:/xampp/htdocs/zapp_citas/js/client_modules/ui.js

/**
 * Actualiza la barra de navegación.
 * Muestra el menú completo si el cliente ha iniciado sesión, o lo oculta si no.
 * También actualiza el título de la aplicación con el nombre del negocio.
 * @param {object} context - El contexto global de la aplicación.
 */
export async function updateNavbar(context) {
    const { navMenu, navbarBrand } = context.dom;
    const { clienteActual } = context.state;

    navMenu.innerHTML = ''; // Limpiar menú

    if (clienteActual) {
        // Cliente ha iniciado sesión: mostrar menú completo y cargar datos del negocio.
        try {
            const response = await fetch(`${context.API_URL}api_negocio_publico.php?id_negocio=${clienteActual.id_negocio}`);
            if (!response.ok) throw new Error('Failed to load business data.');
            const negocio = await response.json();

            context.state.negocioInfo = negocio;
            document.title = `${context.T.client_portal_title} - ${negocio.nombre_negocio}`;
            navbarBrand.textContent = negocio.nombre_negocio;

            navMenu.innerHTML = `
                <li class="nav-item">
                    <a class="nav-link" href="#" data-view="dashboard">${context.T.client_nav_dashboard}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-view="booking">${context.T.client_nav_book}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-view="history">${context.T.client_nav_history}</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        👤 ${clienteActual.nombre_completo}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="#" data-view="profile">${context.T.client_nav_profile}</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" data-action="logout">${context.T.client_nav_logout}</a></li>
                    </ul>
                </li>
            `;
        } catch (error) {
            console.error("Error updating navbar with business data:", error);
            navbarBrand.textContent = context.T.client_portal_title;
        }
    } else {
        // Cliente no ha iniciado sesión: mostrar título genérico.
        const genericTitle = context.T.client_portal_title;
        const genericSubtitle = context.T.client_portal_subtitle;
        document.title = `${genericTitle} - ${genericSubtitle}`;
        navbarBrand.innerHTML = `
            ${genericTitle}
            <span class="ms-2 fw-normal text-white-50" style="font-size: 0.8em;">${genericSubtitle}</span>
        `;
    }
}

/**
 * Marca como 'activo' el enlace de navegación correspondiente a la vista actual.
 * @param {object} context - El contexto global de la aplicación.
 */
export function setActiveNavLink(context) {
    const { navMenu } = context.dom;
    const { currentView } = context.state;

    navMenu.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
    
    const activeLink = navMenu.querySelector(`[data-view="${currentView}"]`);
    if (activeLink) {
        activeLink.classList.add('active');
    }
}