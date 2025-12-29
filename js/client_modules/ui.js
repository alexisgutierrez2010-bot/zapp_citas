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

    // --- MEJORA UI: Inyectar estilos para Menú Flotante (Móvil) ---
    // Reemplazamos la barra fija por un botón flotante estilo "iPhone"
    if (!document.getElementById('mobile-menu-styles')) {
        const style = document.createElement('style');
        style.id = 'mobile-menu-styles';
        style.innerHTML = `
            .fab-menu-btn {
                position: fixed; bottom: 25px; right: 25px;
                width: 60px; height: 60px; border-radius: 50%;
                background-color: #0d6efd; color: white;
                display: flex; justify-content: center; align-items: center;
                box-shadow: 0 4px 15px rgba(13, 110, 253, 0.4); z-index: 1060;
                cursor: pointer; transition: transform 0.2s;
            }
            .fab-menu-btn:active { transform: scale(0.95); }
            .fab-menu-btn i { font-size: 1.8rem; }
            
            .fab-menu-items {
                position: fixed; bottom: 100px; right: 25px;
                display: flex; flex-direction: column; gap: 15px;
                z-index: 1059; opacity: 0; visibility: hidden;
                transform: translateY(20px); transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                align-items: flex-end;
            }
            .fab-menu-items.show { opacity: 1; visibility: visible; transform: translateY(0); }
            
            .fab-item { display: flex; align-items: center; text-decoration: none; color: #333; }
            .fab-item-label {
                background-color: white; padding: 6px 15px; border-radius: 20px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15); margin-right: 12px;
                font-weight: 600; font-size: 0.95rem; color: #495057;
            }
            .fab-item-icon {
                width: 50px; height: 50px; border-radius: 50%;
                background-color: white; color: #0d6efd;
                display: flex; justify-content: center; align-items: center;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            }
            .fab-item-icon i { font-size: 1.4rem; }
            .fab-item.active .fab-item-icon { background-color: #0d6efd; color: white; }
            
            .fab-overlay {
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.6); z-index: 1058;
                display: none; backdrop-filter: blur(3px);
            }
            .fab-overlay.show { display: block; }

            @media (max-width: 991.98px) {
                .navbar .navbar-toggler { display: none !important; } /* Ocultar hamburguesa */
                .navbar .navbar-collapse { display: none !important; } /* Ocultar menú colapsable */
                .navbar .container { justify-content: center; } /* Centrar logo en móvil */
            }
            @media (min-width: 992px) { 
                .fab-menu-btn, .fab-menu-items, .fab-overlay { display: none !important; } 
            }
        `;
        document.head.appendChild(style);
    }

    // Limpiar versión anterior si existe
    const oldBottomNav = document.getElementById('bottom-nav');
    if (oldBottomNav) oldBottomNav.remove();
    const oldStyle = document.getElementById('bottom-nav-styles');
    if (oldStyle) oldStyle.remove();

    // Crear contenedor de menú flotante si no existe
    let fabContainer = document.getElementById('mobile-fab-container');
    if (!fabContainer) {
        fabContainer = document.createElement('div');
        fabContainer.id = 'mobile-fab-container';
        fabContainer.innerHTML = `
            <div class="fab-overlay" id="fab-overlay"></div>
            <div class="fab-menu-items" id="fab-menu-items"></div>
            <div class="fab-menu-btn" id="fab-menu-btn">
                <i class="bi bi-list"></i>
            </div>
        `;
        document.body.appendChild(fabContainer);

        const btn = document.getElementById('fab-menu-btn');
        const items = document.getElementById('fab-menu-items');
        const overlay = document.getElementById('fab-overlay');
        const icon = btn.querySelector('i');

        const toggleMenu = () => {
            const isShown = items.classList.contains('show');
            if (isShown) {
                items.classList.remove('show');
                overlay.classList.remove('show');
                icon.className = 'bi bi-list';
            } else {
                items.classList.add('show');
                overlay.classList.add('show');
                icon.className = 'bi bi-x-lg';
            }
        };

        btn.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);
    }

    navMenu.innerHTML = ''; // Limpiar menú

    if (clienteActual) {
        // Cliente ha iniciado sesión: mostrar menú completo y cargar datos del negocio.
        try {
            const response = await fetch(`${context.API_URL}api_negocio_publico.php?id_negocio=${clienteActual.id_negocio}`);
            if (!response.ok) throw new Error('Failed to load business data.');
            const negocio = await response.json();

            context.state.negocioInfo = negocio;
            document.title = `Portal de Cliente - ${negocio.nombre_negocio}`;
            navbarBrand.textContent = negocio.nombre_negocio;

            navMenu.innerHTML = `
                <li class="nav-item">
                    <a class="nav-link" href="#" data-view="dashboard"><i class="bi bi-house-door"></i> Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-view="booking"><i class="bi bi-calendar-plus"></i> Agendar</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-view="history"><i class="bi bi-clock-history"></i> Mi Historial</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-view="reviews"><i class="bi bi-star"></i> Reseñas</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        👤 ${clienteActual.nombre_completo}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="#" data-view="profile">Mi Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" data-action="logout">Cerrar Sesión</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="ayuda_spa_client.php" target="_blank">❓ Ayuda</a>
                </li>
            `;

            // Menú Flotante (Móvil) - Opciones desplegables
            const itemsContainer = document.getElementById('fab-menu-items');
            itemsContainer.innerHTML = `
                <a href="#" class="fab-item" data-action="logout"><span class="fab-item-label">Cerrar Sesión</span><div class="fab-item-icon"><i class="bi bi-box-arrow-right"></i></div></a>
                <a href="#" class="fab-item" data-view="profile"><span class="fab-item-label">Mi Perfil</span><div class="fab-item-icon"><i class="bi bi-person"></i></div></a>
                <a href="#" class="fab-item" data-view="reviews"><span class="fab-item-label">Reseñas</span><div class="fab-item-icon"><i class="bi bi-star"></i></div></a>
                <a href="#" class="fab-item" data-view="history"><span class="fab-item-label">Historial</span><div class="fab-item-icon"><i class="bi bi-clock-history"></i></div></a>
                <a href="#" class="fab-item" data-view="booking"><span class="fab-item-label">Agendar Cita</span><div class="fab-item-icon"><i class="bi bi-calendar-plus"></i></div></a>
                <a href="#" class="fab-item" data-view="dashboard"><span class="fab-item-label">Inicio</span><div class="fab-item-icon"><i class="bi bi-house-door"></i></div></a>
            `;
        } catch (error) {
            console.error("Error updating navbar with business data:", error);
            navbarBrand.textContent = 'Portal de Cliente';
        }
    } else {
        // Cliente no ha iniciado sesión: mostrar título genérico.
        const genericTitle = 'Portal de Cliente';
        const genericSubtitle = 'Gestión de Citas';
        document.title = `${genericTitle} - ${genericSubtitle}`;
        navbarBrand.innerHTML = `
            ${genericTitle}
            <span class="ms-2 fw-normal text-white-50" style="font-size: 0.8em;">${genericSubtitle}</span>
        `;
        // Añadir enlace de ayuda y volver al inicio para usuarios no logueados
        navMenu.innerHTML = `<li class="nav-item"><a class="nav-link" href="ayuda_spa_client.php" target="_blank">❓ Ayuda</a></li>`;
        
        // Menú Flotante (Móvil) - No Logueado
        const itemsContainer = document.getElementById('fab-menu-items');
        itemsContainer.innerHTML = `
            <a href="ayuda_spa_client.php" target="_blank" class="fab-item"><span class="fab-item-label">Ayuda</span><div class="fab-item-icon"><i class="bi bi-question-circle"></i></div></a>
            <a href="#" class="fab-item" data-view="login"><span class="fab-item-label">Entrar</span><div class="fab-item-icon"><i class="bi bi-box-arrow-in-right"></i></div></a>
        `;
    }

    // Añadir listeners a los nuevos items flotantes para cerrar el menú al hacer click
    document.querySelectorAll('.fab-item').forEach(item => {
        item.addEventListener('click', () => {
            document.getElementById('fab-menu-items').classList.remove('show');
            document.getElementById('fab-overlay').classList.remove('show');
            document.getElementById('fab-menu-btn').querySelector('i').className = 'bi bi-list';
        });
    });
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

    // Actualizar estado activo en menú flotante
    const fabItems = document.getElementById('fab-menu-items');
    if (fabItems) {
        fabItems.querySelectorAll('.fab-item').forEach(link => link.classList.remove('active'));
        const activeBottomLink = fabItems.querySelector(`[data-view="${currentView}"]`);
        if (activeBottomLink) activeBottomLink.classList.add('active');
    }
}
