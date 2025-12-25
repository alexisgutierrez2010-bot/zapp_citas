// js/owner_modules/reviews.js
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.

export async function renderReviewsView(context) {
    const { dom, API_URL } = context;

    dom.appContainer.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Reseñas de Clientes</h2>
            <div id="resumen-puntuacion" class="badge bg-warning text-dark fs-5">
                <span class="spinner-border spinner-border-sm"></span>
            </div>
        </div>
        <div id="lista-resenas-owner" class="row g-3">
            <div class="col-12 text-center"><div class="spinner-border text-primary"></div></div>
        </div>
    `;

    try {
        const response = await fetch(`${API_URL}api_owner_resenas.php`);
        if (!response.ok) throw new Error('Error al cargar las reseñas');
        const data = await response.json();
        
        // Actualizar resumen
        const promedio = data.promedio || 0;
        const total = data.total || 0;
        document.getElementById('resumen-puntuacion').innerHTML = `
            <i class="bi bi-star-fill"></i> ${promedio} <small class="fs-6 fw-normal">(${total} opiniones)</small>
        `;

        const container = document.getElementById('lista-resenas-owner');
        if (!data.resenas || data.resenas.length === 0) {
            container.innerHTML = '<div class="col-12"><div class="alert alert-info">No tienes reseñas todavía.</div></div>';
            return;
        }

        container.innerHTML = data.resenas.map(r => `
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold mb-0">${r.nombre_cliente}</h6>
                                <small class="text-muted">${r.nombre_servicio || 'General'}</small>
                            </div>
                            <span class="badge bg-warning text-dark">★ ${r.puntuacion}</span>
                        </div>
                        <p class="card-text fst-italic">"${r.comentario}"</p>
                    </div>
                    <div class="card-footer bg-white border-top-0 text-end">
                        <small class="text-muted">${new Date(r.fecha_hora).toLocaleDateString()} ${new Date(r.fecha_hora).toLocaleTimeString()}</small>
                    </div>
                </div>
            </div>
        `).join('');

    } catch (error) {
        dom.appContainer.innerHTML = `<div class="alert alert-danger">Error al cargar reseñas: ${error.message}</div>`;
    }
}
