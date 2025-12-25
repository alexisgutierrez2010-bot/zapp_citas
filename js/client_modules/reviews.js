// js/client_modules/reviews.js
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.

export async function renderReviewsView(context) {
    const { dom, state, API_URL } = context;
    // Asegurarse de que tenemos la información del negocio
    const idNegocio = state.clienteActual.id_negocio;

    dom.appContainer.innerHTML = `
        <div class="container py-3">
            <h3 class="mb-3">Reseñas y Opiniones</h3>
            
            <!-- Formulario de Nueva Reseña -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">Deja tu opinión</div>
                <div class="card-body">
                    <form id="form-resena">
                        <div class="mb-3">
                            <label class="form-label">Calificación</label>
                            <div class="rating-stars fs-3 text-warning" style="cursor: pointer;">
                                <i class="bi bi-star" data-value="1"></i>
                                <i class="bi bi-star" data-value="2"></i>
                                <i class="bi bi-star" data-value="3"></i>
                                <i class="bi bi-star" data-value="4"></i>
                                <i class="bi bi-star" data-value="5"></i>
                            </div>
                            <input type="hidden" id="puntuacion" value="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="comentario" class="form-label">Tu experiencia</label>
                            <textarea class="form-control" id="comentario" rows="3" placeholder="Cuéntanos qué te pareció el servicio..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Publicar Reseña</button>
                    </form>
                </div>
            </div>

            <!-- Lista de Reseñas -->
            <h5 class="mb-3">Opiniones de otros clientes</h5>
            <div id="lista-resenas">
                <div class="text-center"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    `;

    // Lógica de Estrellas (Interacción visual)
    const stars = document.querySelectorAll('.rating-stars i');
    stars.forEach(star => {
        star.addEventListener('click', (e) => {
            const val = e.target.dataset.value;
            document.getElementById('puntuacion').value = val;
            stars.forEach(s => {
                // Llenar estrellas hasta la seleccionada
                if (s.dataset.value <= val) {
                    s.classList.remove('bi-star');
                    s.classList.add('bi-star-fill');
                } else {
                    s.classList.remove('bi-star-fill');
                    s.classList.add('bi-star');
                }
            });
        });
    });

    // Cargar Reseñas existentes
    loadReviews(context, idNegocio);

    // Manejar Envío del formulario
    document.getElementById('form-resena').addEventListener('submit', async (e) => {
        e.preventDefault();
        const puntuacion = document.getElementById('puntuacion').value;
        const comentario = document.getElementById('comentario').value;

        if (puntuacion == 0) { alert('Por favor selecciona una calificación tocando las estrellas.'); return; }

        try {
            const response = await fetch(`${API_URL}api_client_resenas.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_negocio: idNegocio,
                    puntuacion: puntuacion,
                    comentario: comentario
                })
            });
            const data = await response.json();
            if (data.success) {
                alert(data.message);
                document.getElementById('form-resena').reset();
                // Reset visual de estrellas
                stars.forEach(s => { s.classList.remove('bi-star-fill'); s.classList.add('bi-star'); });
                document.getElementById('puntuacion').value = 0;
                loadReviews(context, idNegocio); // Recargar lista
            } else {
                alert(data.error || 'Error al publicar');
            }
        } catch (error) {
            console.error(error);
            alert('Error de conexión al publicar la reseña.');
        }
    });
}

async function loadReviews(context, idNegocio) {
    try {
        const response = await fetch(`${context.API_URL}api_client_resenas.php?id_negocio=${idNegocio}`);
        const resenas = await response.json();
        
        const container = document.getElementById('lista-resenas');
        if (resenas.length === 0) {
            container.innerHTML = '<div class="alert alert-info">Aún no hay reseñas. ¡Sé el primero en opinar!</div>';
            return;
        }

        container.innerHTML = resenas.map(r => `
            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h6 class="card-title fw-bold">${r.nombre_cliente}</h6>
                        <small class="text-muted">${new Date(r.fecha_hora).toLocaleDateString()}</small>
                    </div>
                    <div class="text-warning mb-2">
                        ${'★'.repeat(Math.round(r.puntuacion))}${'☆'.repeat(5 - Math.round(r.puntuacion))}
                        <span class="text-dark ms-1 small">(${r.puntuacion})</span>
                    </div>
                    <p class="card-text">${r.comentario}</p>
                    ${r.nombre_servicio ? `<span class="badge bg-light text-dark border">Servicio: ${r.nombre_servicio}</span>` : ''}
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error(error);
        document.getElementById('lista-resenas').innerHTML = '<div class="alert alert-danger">Error al cargar reseñas.</div>';
    }
}
