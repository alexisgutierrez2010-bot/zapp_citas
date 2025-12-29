<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
require_once 'auth_check.php';
require_once 'config.php';

// --- VISTA DEL FORMULARIO ---
// Solo el rol Administrador puede acceder a esta página.
if (strcasecmp(trim($rol_session ?? ''), 'Administrador') != 0) {
    header("Location: dashboard.php?status=error&message=" . urlencode("Acceso no autorizado."));
    exit;
}

// Obtener datos para los selectores del formulario
$paises = [];
$paises_result = $conn->query("SELECT id_pais, nombre_pais, codigo_telefono FROM j110_paises ORDER BY nombre_pais ASC");
while ($row = $paises_result->fetch_assoc()) {
    $paises[] = $row;
}

$categorias = [];
$categorias_result = $conn->query("SELECT id_categoria, nombre_categoria FROM j103_categorias WHERE activo = 1 ORDER BY nombre_categoria ASC");
while ($row = $categorias_result->fetch_assoc()) {
    $categorias[] = $row;
}

$dias_semana = [
    '1' => 'Lunes', '2' => 'Martes', '3' => 'Miércoles', '4' => 'Jueves',
    '5' => 'Viernes', '6' => 'Sábado', '7' => 'Domingo'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Negocio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: <?php echo $daily_bg_color; ?>;">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Crear Nuevo Negocio</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        if (isset($_GET['message_key'])) {
                            $message = htmlspecialchars(urldecode($_GET['message_key'])); // Corregido para decodificar URL
                            echo "<div class='alert alert-danger'>" . htmlspecialchars($message) . "</div>";
                        }
                        ?>
                        <form action="negocios_crear.php" method="POST">
                            <h5 class="mt-3">Datos Principales</h5>
                            <div class="mb-3">
                                <label for="nombre_negocio" class="form-label">Nombre del Negocio</label>
                                <input type="text" class="form-control" id="nombre_negocio" name="nombre_negocio" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="telefono_local" class="form-label">Teléfono del Negocio</label>
                                    <div class="input-group">
                                        <select class="form-select" id="country_code" name="country_code" style="max-width: 150px;" required>
                                            <option>Cargando...</option>
                                        </select>
                                        <input type="tel" class="form-control" id="telefono_local" name="telefono_local" placeholder="Número local" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email de Contacto del Negocio</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <hr>
                            <h5 class="mt-3">Detalles del Negocio</h5>
                            <div class="mb-3">
                                <label for="id_categoria_negocio" class="form-label">Categoría del Negocio</label>
                                <select class="form-select" id="id_categoria_negocio" name="id_categoria_negocio">
                                    <option value="">-- Sin Categoría --</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id_categoria']; ?>"><?php echo htmlspecialchars($categoria['nombre_categoria']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="direccion1" class="form-label">Dirección 1</label>
                                <input type="text" class="form-control" id="direccion1" name="direccion1" maxlength="128">
                            </div>
                             <div class="mb-3">
                                <label for="direccion2" class="form-label">Dirección 2 (Opcional)</label>
                                <input type="text" class="form-control" id="direccion2" name="direccion2" maxlength="128">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="id_pais" class="form-label">País</label>
                                    <select class="form-select" id="id_pais" name="id_pais" required>
                                        <option value="">Seleccione un país...</option>
                                        <?php foreach ($paises as $pais): ?>
                                            <option value="<?php echo $pais['id_pais']; ?>"><?php echo htmlspecialchars($pais['nombre_pais']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="id_estado" class="form-label">Estado / Provincia</label>
                                    <select class="form-select" id="id_estado" name="id_estado" required disabled>
                                        <option value="">Seleccione un país primero</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="ciudad" class="form-label">Ciudad</label>
                                    <input type="text" class="form-control" id="ciudad" name="ciudad">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="zip_code" class="form-label">Código Postal</label>
                                    <input type="text" class="form-control" id="zip_code" name="zip_code">
                                </div>
                            </div>
                            <hr>
                            <h5 class="mt-4">Horario de Trabajo</h5>
                            <div class="mb-3">
                                <label class="form-label">Días de Trabajo</label>
                                <div>
                                    <?php foreach ($dias_semana as $num => $dia): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="dias_trabajo[]" value="<?php echo $num; ?>" id="dia_<?php echo $num; ?>" <?php echo in_array($num, ['1','2','3','4','5']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="dia_<?php echo $num; ?>"><?php echo $dia; ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="hora_inicio" class="form-label">Hora de Inicio</label>
                                    <input type="time" class="form-control" id="hora_inicio" name="hora_inicio" value="08:00" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="hora_cierre" class="form-label">Hora de Cierre</label>
                                    <input type="time" class="form-control" id="hora_cierre" name="hora_cierre" value="18:00" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="intervalo_minutos" class="form-label">Intervalo (minutos)</label>
                                    <input type="number" class="form-control" id="intervalo_minutos" name="intervalo_minutos" value="30" required>
                                </div>
                            </div>
                            <hr>
                            <h5 class="mt-3">Crear Usuario Propietario</h5>
                            <p class="text-muted">Se creará un usuario 'Propietario' para este nuevo negocio.</p>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="admin_user" class="form-label">Nombre de Usuario Propietario</label>
                                    <input type="text" class="form-control" id="admin_user" name="admin_user" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="admin_pass" class="form-label">Contraseña para Propietario</label>
                                    <input type="password" class="form-control" id="admin_pass" name="admin_pass" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="admin_email" class="form-label">Email del Usuario Propietario</label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Crear Negocio y Usuario Propietario</button>
                                <a href="negocios_configuracion.php" class="btn btn-secondary">Volver a la Lista</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const paisSelect = document.getElementById('id_pais');
            const estadoSelect = document.getElementById('id_estado');
            const countryCodeSelect = document.getElementById('country_code');

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

            try {
                const response = await fetch('api_paises.php');
                const paises = await response.json();
                countryCodeSelect.innerHTML = paises.map(pais => `<option value="${pais.codigo_telefono}" ${pais.id_pais == 1 ? 'selected' : ''}>${pais.nombre_pais} (${pais.codigo_telefono})</option>`).join('');
            } catch (error) { console.error("Error cargando códigos de país:", error); }
        });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>