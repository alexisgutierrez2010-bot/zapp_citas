<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
require_once 'auth_check.php';
require_once 'config.php';

// Solo el rol Administrador puede acceder a esta página.
if (strcasecmp(trim($rol_session ?? ''), 'Administrador') != 0) {
    header("Location: dashboard.php?status=error&message=" . urlencode("Acceso no autorizado."));
    exit;
}

// Obtener la lista de negocios
$todos_los_negocios = [];
$result_todos_negocios = $conn->query("SELECT id_negocio, nombre_negocio FROM j102_negocios WHERE activo = 1 ORDER BY nombre_negocio ASC");
if ($result_todos_negocios) {
    while ($row = $result_todos_negocios->fetch_assoc()) {
        $todos_los_negocios[] = $row;
    }
}

// Obtener la lista de países
$paises = [];
$paises_result = $conn->query("SELECT id_pais, nombre_pais, codigo_telefono FROM j110_paises ORDER BY nombre_pais ASC");
while ($row = $paises_result->fetch_assoc()) {
    $paises[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Nuevo Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: <?php echo $daily_bg_color; ?>;">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Registrar Nuevo Cliente</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['message'])): ?>
                            <div class="alert alert-<?php echo $_GET['status'] == 'success' ? 'success' : 'danger'; ?>">
                                <?php echo htmlspecialchars(urldecode($_GET['message'])); ?>
                            </div>
                        <?php endif; ?>
                        <form action="clientes_crear.php" method="POST">
                            <div class="mb-3">
                                <label for="id_negocio" class="form-label">Asignar al Negocio</label>
                                <select name="id_negocio" id="id_negocio" class="form-select" required>
                                    <option value="">-- Seleccione un Negocio --</option>
                                    <?php foreach ($todos_los_negocios as $negocio): ?>
                                        <option value="<?php echo $negocio['id_negocio']; ?>">
                                            <?php echo htmlspecialchars($negocio['nombre_negocio']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="nombre_completo" class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" required>
                            </div>
                            <div class="mb-3">
                                <label for="numero_celular" class="form-label">Teléfono Celular</label>
                                <div class="input-group">
                                    <select class="form-select" name="country_code" style="max-width: 120px;">
                                        <?php foreach ($paises as $pais): ?>
                                            <option value="<?php echo htmlspecialchars($pais['codigo_telefono']); ?>" <?php echo ($pais['id_pais'] == 1) ? 'selected' : ''; ?>><?php echo htmlspecialchars($pais['codigo_telefono']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="tel" class="form-control" id="numero_celular" name="numero_celular" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="correo_electronico" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="correo_electronico" name="correo_electronico" required>
                            </div>
                            <div class="mb-3">
                                <label for="direccion1" class="form-label">Dirección (Opcional)</label>
                                <input type="text" class="form-control" id="direccion1" name="direccion1">
                            </div>
                            <div class="mb-3">
                                <label for="direccion2" class="form-label">Dirección 2 (Opcional)</label>
                                <input type="text" class="form-control" id="direccion2" name="direccion2">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="id_pais" class="form-label">País (Opcional)</label>
                                    <select class="form-select" id="id_pais" name="id_pais">
                                        <option value="">Seleccione un país...</option>
                                        <?php foreach ($paises as $pais): ?>
                                            <option value="<?php echo $pais['id_pais']; ?>"><?php echo htmlspecialchars($pais['nombre_pais']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="id_estado" class="form-label">Estado / Provincia (Opcional)</label>
                                    <select class="form-select" id="id_estado" name="id_estado" disabled>
                                        <option value="">Seleccione un país primero</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="ciudad" class="form-label">Ciudad (Opcional)</label>
                                    <input type="text" class="form-control" id="ciudad" name="ciudad">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="zip_code" class="form-label">Código Postal (Opcional)</label>
                                    <input type="text" class="form-control" id="zip_code" name="zip_code">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="notas_adicionales" class="form-label">Notas Adicionales (Opcional)</label>
                                <textarea class="form-control" id="notas_adicionales" name="notas_adicionales" rows="2"></textarea>
                            </div>
                            <hr>
                            <h5 class="mt-3">Preferencias de Comunicación</h5>
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="in_email" value="1" id="in_email" checked><label class="form-check-label" for="in_email">Recibir correos electrónicos</label></div>
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="in_sms" value="1" id="in_sms" checked><label class="form-check-label" for="in_sms">Recibir SMS</label></div>
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="in_whatsapp" value="1" id="in_whatsapp" checked><label class="form-check-label" for="in_whatsapp">Recibir WhatsApp</label></div>
                            <div class="d-flex justify-content-between mt-4">
                                <a href="clientes_lista.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Cliente</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const paisSelect = document.getElementById('id_pais');
        const estadoSelect = document.getElementById('id_estado');

        paisSelect.addEventListener('change', function() {
            const idPais = this.value;
            estadoSelect.innerHTML = '<option value="">Cargando...</option>';
            estadoSelect.disabled = true;

            if (idPais) {
                fetch(`api_estados.php?id_pais=${idPais}`)
                    .then(response => response.json())
                    .then(data => {
                        estadoSelect.innerHTML = '<option value="">Seleccione un estado...</option>';
                        data.forEach(estado => {
                            estadoSelect.innerHTML += `<option value="${estado.id_estado}">${estado.nombre_estado}</option>`;
                        });
                        estadoSelect.disabled = false;
                    });
            }
        });
    });
    </script>
</body>
</html>