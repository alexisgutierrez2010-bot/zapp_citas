<?php
session_start(); // CORRECCIÓN: Iniciar la sesión para acceder a las variables de sesión.
require_once 'auth_check.php';
require_once 'config.php';

$id_cita = isset($_GET['id_cita']) ? (int)$_GET['id_cita'] : 0;
if ($id_cita <= 0) {
    header("Location: citas_lista.php?status=error&message=" . urlencode("ID de cita no válido."));
    exit();
}

// Obtener datos de la cita y los invitados
$stmt_cita = $conn->prepare("SELECT c.*, cl.nombre_completo FROM j108_citas c JOIN j106_clientes cl ON c.id_cliente = cl.id_cliente WHERE c.id_cita = ? AND c.id_negocio = ?");
$stmt_cita->bind_param("ii", $id_cita, $id_negocio_session);
$stmt_cita->execute();
$cita = $stmt_cita->get_result()->fetch_assoc();
$stmt_cita->close();

if (!$cita || $cita['tipo_cita'] !== 'Reunion') {
    header("Location: citas_lista.php?status=error&message=" . urlencode("Cita no encontrada o no es una reunión."));
    exit();
}

$invitados = [];
$stmt_invitados = $conn->prepare("SELECT * FROM j109_invitados_cita WHERE id_cita = ?");
$stmt_invitados->bind_param("i", $id_cita);
$stmt_invitados->execute();
$result_invitados = $stmt_invitados->get_result();
while ($row = $result_invitados->fetch_assoc()) {
    $invitados[] = $row;
}
$stmt_invitados->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Invitados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- Modal para gestionar invitados -->
    <div class="modal fade" id="invitadosModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gestionar Invitados para la Reunión</h5>
                </div>
                <div class="modal-body">
                    <p><strong>Tema:</strong> <?php echo htmlspecialchars($cita['descripcion_trabajo']); ?></p>
                    <p><strong>Cliente Principal:</strong> <?php echo htmlspecialchars($cita['nombre_completo']); ?></p>
                    <hr>
                    <div id="error-container-modal"></div>
                    <form id="form-invitados">
                        <div id="lista-invitados-modal">
                            <!-- Los invitados se cargarán aquí -->
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btn-anadir-invitado-modal">+ Añadir Invitado</button>
                    </form>
                </div>
                <div class="modal-footer">
                    <a href="citas_lista.php" class="btn btn-secondary">Cerrar y Volver a la Lista</a>
                    <button type="button" class="btn btn-primary" id="btn-guardar-invitados">Guardar Invitados</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modalElement = document.getElementById('invitadosModal');
        const modal = new bootstrap.Modal(modalElement);
        const listaInvitadosContainer = document.getElementById('lista-invitados-modal');
        const btnAnadir = document.getElementById('btn-anadir-invitado-modal');
        const btnGuardar = document.getElementById('btn-guardar-invitados');
        const errorContainer = document.getElementById('error-container-modal');
        const idCita = <?php echo $id_cita; ?>;
        const invitadosIniciales = <?php echo json_encode($invitados); ?>;

        function renderizarInvitado(invitado = {}) {
            const div = document.createElement('div');
            div.className = 'row g-2 mb-2 align-items-center';
            div.innerHTML = `
                <div class="col-sm-4"><input type="text" class="form-control form-control-sm" placeholder="Nombre Invitado" value="${invitado.nombre_invitado || ''}" required></div>
                <div class="col-sm-4"><input type="email" class="form-control form-control-sm" placeholder="Email Invitado" value="${invitado.correo_electronico_invitado || ''}" required></div>
                <div class="col-sm-3"><input type="tel" class="form-control form-control-sm" placeholder="Teléfono (Opcional)" value="${invitado.numero_celular_invitado || ''}"></div>
                <div class="col-sm-1"><button type="button" class="btn btn-sm btn-danger btn-remover-invitado">X</button></div>
            `;
            listaInvitadosContainer.appendChild(div);
            div.querySelector('.btn-remover-invitado').addEventListener('click', () => div.remove());
        }

        btnAnadir.addEventListener('click', () => renderizarInvitado());

        btnGuardar.addEventListener('click', async function() {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
            errorContainer.innerHTML = '';

            const invitadosPayload = [];
            const filasInvitados = listaInvitadosContainer.querySelectorAll('.row');
            filasInvitados.forEach(fila => {
                const nombre = fila.querySelector('input[type="text"]').value.trim();
                const email = fila.querySelector('input[type="email"]').value.trim();
                const telefono = fila.querySelector('input[type="tel"]').value.trim();
                if (nombre && email) {
                    invitadosPayload.push({ nombre, email, telefono });
                }
            });

            try {
                const response = await fetch('api_cita_guardar_invitados.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_cita: idCita, invitados: invitadosPayload })
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.error);

                errorContainer.innerHTML = '<div class="alert alert-success">Invitados guardados con éxito.</div>';
                setTimeout(() => window.location.href = 'citas_lista.php', 1500);

            } catch (error) {
                errorContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
            } finally {
                this.disabled = false;
                this.innerHTML = 'Guardar Invitados';
            }
        });

        // Cargar invitados iniciales y mostrar el modal
        invitadosIniciales.forEach(renderizarInvitado);
        modal.show();
    });
    </script>
</body>
</html>