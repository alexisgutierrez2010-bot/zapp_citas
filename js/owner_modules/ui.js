// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-25-2025).
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

    // --- MEJORA UI: Inyectar estilos para Menú Flotante (Móvil) ---
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

    // Limpiar versión anterior
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

        // Menú superior para escritorio
        const navItemsHtml = ` 
            ${trialInfoHtml}
            <a class="nav-link" href="#" data-view="agenda">Agenda</a>
            <a class="nav-link" href="#" data-view="calendario">Calendario</a>
            <a class="nav-link" href="#" data-view="clientes">Clientes</a>
            <a class="nav-link" href="#" data-view="servicios">Servicios</a>
            <a class="nav-link" href="#" data-view="reviews">Reseñas</a>
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAdmin" role="button" data-bs-toggle="dropdown" aria-expanded="false">Administración</a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownAdmin">
                    <li><a class="dropdown-item" href="#" data-view="dashboard">Dashboard</a></li>
                    <li><a class="dropdown-item" href="#" data-view="negocio">Mi Negocio</a></li>
                    <li><a class="dropdown-item" href="#" data-view="perfil">Mi Perfil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" data-action="close-overdue-appointments">Cerrar Citas Vencidas</a></li>
                </ul>
            </div>
            <div class="nav-item ms-lg-2">
                <a class="nav-link" href="#" data-action="logout">Cerrar Sesión</a>
            </div>
        `;

        if (navMenuContainer) {
            navMenuContainer.innerHTML = navItemsHtml;
        }

        // Menú Flotante (Móvil) - Opciones Propietario
        // Homologado con la lista solicitada para reducir y optimizar.
        const itemsContainer = document.getElementById('fab-menu-items');
        itemsContainer.innerHTML = `
            <a href="#" class="fab-item" data-action="logout"><span class="fab-item-label">Cerrar Sesión</span><div class="fab-item-icon"><i class="bi bi-box-arrow-right"></i></div></a>
            <a href="#" class="fab-item" data-view="dashboard"><span class="fab-item-label">Administración</span><div class="fab-item-icon"><i class="bi bi-speedometer2"></i></div></a>
            <a href="#" class="fab-item" data-view="reviews"><span class="fab-item-label">Reseñas</span><div class="fab-item-icon"><i class="bi bi-star"></i></div></a>
            <a href="#" class="fab-item" data-view="servicios"><span class="fab-item-label">Servicios</span><div class="fab-item-icon"><i class="bi bi-scissors"></i></div></a>
            <a href="#" class="fab-item" data-view="clientes"><span class="fab-item-label">Clientes</span><div class="fab-item-icon"><i class="bi bi-people"></i></div></a>
            <a href="#" class="fab-item" data-view="calendario"><span class="fab-item-label">Calendario</span><div class="fab-item-icon"><i class="bi bi-calendar3"></i></div></a>
            <a href="#" class="fab-item" data-view="agenda"><span class="fab-item-label">Agenda</span><div class="fab-item-icon"><i class="bi bi-journal-text"></i></div></a>
        `;

        startClock(context); // Se pasa el contexto a startClock
    } else {
        dom.navbarBrand.textContent = 'App Propietario';
        if (navMenuContainer) navMenuContainer.innerHTML = '';
        document.body.style.backgroundImage = ''; // Limpiar fondo al cerrar sesión
        
        // Menú Flotante (Móvil) - No Logueado
        const itemsContainer = document.getElementById('fab-menu-items');
        itemsContainer.innerHTML = `
            <a href="#" class="fab-item" data-view="login"><span class="fab-item-label">Entrar</span><div class="fab-item-icon"><i class="bi bi-box-arrow-in-right"></i></div></a>
        `;
    }

    // Añadir listeners para cerrar menú al click
    document.querySelectorAll('.fab-item').forEach(item => {
        item.addEventListener('click', () => {
            document.getElementById('fab-menu-items').classList.remove('show');
            document.getElementById('fab-overlay').classList.remove('show');
            document.getElementById('fab-menu-btn').querySelector('i').className = 'bi bi-list';
        });
    });
}

export function setActiveNavLink(navMenu, viewName) {
    navMenu.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
    const activeLink = navMenu.querySelector(`[data-view="${viewName}"]`);
    if (activeLink) activeLink.classList.add('active');

    // Actualizar estado activo en menú flotante
    const fabItems = document.getElementById('fab-menu-items');
    if (fabItems) {
        fabItems.querySelectorAll('.fab-item').forEach(link => link.classList.remove('active'));
        const activeBottomLink = fabItems.querySelector(`[data-view="${viewName}"]`);
        if (activeBottomLink) activeBottomLink.classList.add('active');
    }
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