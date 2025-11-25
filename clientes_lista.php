<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
require_once 'Auth_check.php';
require_once 'config.php'; // Incluimos la conexión aquí para usarla más adelante

// Obtener la lista de países para los menús desplegables
$paises = [];
$paises_result = $conn->query("SELECT id_pais, nombre_pais, codigo_telefono FROM j110_paises ORDER BY nombre_pais ASC");
while ($row = $paises_result->fetch_assoc()) {
    $paises[] = $row;
}

// Obtener el nombre del negocio actual para mostrarlo en la página
$nombre_negocio_actual = 'Negocio Desconocido';
$stmt_negocio = $conn->prepare("SELECT nombre_negocio FROM j102_negocios WHERE id_negocio = ?");
$stmt_negocio->bind_param("i", $id_negocio_session);
$stmt_negocio->execute();
$result_negocio = $stmt_negocio->get_result()->fetch_assoc();
$nombre_negocio_actual = $result_negocio['nombre_negocio'] ?? $nombre_negocio_actual;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes</title>
    <!-- Usaremos Bootstrap para un diseño limpio y rápido -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; // Incluimos el menú de navegación ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <h3>Registrar Nuevo Cliente</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        // Mostrar mensajes de éxito o error
                        if (isset($_GET['status'])) {
                            if ($_GET['status'] == 'success') {
                                echo '<div class="alert alert-success">¡Cliente registrado con éxito!</div>';
                            } elseif ($_GET['status'] == 'success_edit') {
                                echo '<div class="alert alert-success">¡Cliente actualizado con éxito!</div>';
                            } elseif ($_GET['status'] == 'success_delete') {
                                echo '<div class="alert alert-success">¡Cliente eliminado con éxito!</div>';
                            } elseif ($_GET['status'] == 'error') {
                                $errorMessage = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Ocurrió un error.';
                                echo '<div class="alert alert-danger">' . $errorMessage . '</div>';
                            }
                        }
                        ?>

                        <form action="clientes_crear.php" method="POST">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" id="nombre" name="nombre_completo" required>
                            </div>
                            <div class="mb-3">
                                <label for="numero_celular" class="form-label">Número de Celular</label>
                                <div class="input-group">
                                    <select class="form-select" id="country_code" name="country_code" style="max-width: 120px;">
                                        <?php foreach ($paises as $pais): ?>
                                            <option value="<?php echo htmlspecialchars($pais['codigo_telefono']); ?>" <?php echo ($pais['id_pais'] == 1) ? 'selected' : ''; ?>><?php echo htmlspecialchars($pais['codigo_telefono']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="tel" class="form-control" id="numero_celular" name="numero_celular" placeholder="Ej: 4121234567">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="correo_electronico" required>
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
                                            <option value="<?php echo $pais['id_pais']; ?>" data-codigo-telefono="<?php echo htmlspecialchars($pais['codigo_telefono']); ?>" <?php echo ($pais['id_pais'] == 1) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($pais['nombre_pais']); ?>
                                            </option>
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
                                    <label for="zip_code" class="form-label">Zip Code</label>
                                    <input type="text" class="form-control" id="zip_code" name="zip_code">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="notas" class="form-label">Notas Adicionales</label>
                                <textarea class="form-control" id="notas" name="notas_adicionales" rows="3"></textarea>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <label class="form-label">Preferencias de Comunicación:</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="in_sms" value="1" id="in_sms_crear" checked>
                                    <label class="form-check-label" for="in_sms_crear">Recibir notificaciones por SMS</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="in_email" value="1" id="in_email_crear" checked>
                                    <label class="form-check-label" for="in_email_crear">Recibir notificaciones por Email</label>
                                </div>
                            </div>
                            <div class="d-grid gap-2 d-sm-flex">
                                <button type="submit" class="btn btn-primary flex-grow-1">Guardar Cliente</button>
                                <button type="reset" class="btn btn-secondary flex-grow-1">Limpiar Formulario</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <h3>Lista de Clientes</h3>
                <h5 class="text-muted mb-3">Para: <?php echo htmlspecialchars($nombre_negocio_actual); ?></h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Celular</th>
                                <th>Email</th>
                                <th>SMS</th>
                                <th>Email</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT id_cliente, nombre_completo, numero_celular, correo_electronico, activo, IN_SMS, IN_EMAIL FROM j106_clientes WHERE id_negocio = ? ORDER BY activo DESC, nombre_completo ASC";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $id_negocio_session);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            $i = 1;
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $i++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row["nombre_completo"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["numero_celular"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["correo_electronico"]) . "</td>";
                                    $sms_status = $row['IN_SMS'] ? '<span class="badge bg-success">On</span>' : '<span class="badge bg-secondary">Off</span>';
                                    $email_status = $row['IN_EMAIL'] ? '<span class="badge bg-success">On</span>' : '<span class="badge bg-secondary">Off</span>';
                                    echo "<td>" . $sms_status . "</td>";
                                    echo "<td>" . $email_status . "</td>";
                                    $estado_cliente = $row['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>';
                                    echo "<td>" . $estado_cliente . "</td>";
                                    echo '<td>
                                            <a href="clientes_editar.php?id=' . $row['id_cliente'] . '" class="btn btn-sm btn-warning">Editar</a>
                                            <form action="clientes_eliminar.php" method="POST" style="display:inline-block;" onsubmit="return confirm(\'¿Estás seguro? Se eliminarán también todas sus citas.\');">
                                                <input type="hidden" name="id_cliente" value="' . $row['id_cliente'] . '">
                                                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                            </form>
                                          </td>';
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center'>No hay clientes registrados todavía.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const paisSelect = document.getElementById('id_pais');
        const estadoSelect = document.getElementById('id_estado');
        const codigoTelefonoSelect = document.getElementById('country_code');

        function cargarEstados(idPais, idEstadoSeleccionado = null) {
            if (!idPais) {
                estadoSelect.innerHTML = '<option value="">Seleccione un país primero</option>';
                estadoSelect.disabled = true;
                return;
            }

            fetch(`api_estados.php?id_pais=${idPais}`)
                .then(response => response.json())
                .then(data => {
                    estadoSelect.innerHTML = '<option value="">Seleccione un estado...</option>';
                    data.forEach(estado => {
                        const option = document.createElement('option');
                        option.value = estado.id_estado;
                        option.textContent = estado.nombre_estado;
                        if (idEstadoSeleccionado && estado.id_estado == idEstadoSeleccionado) {
                            option.selected = true;
                        }
                        estadoSelect.appendChild(option);
                    });
                    estadoSelect.disabled = false;
                });
        }

        paisSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            codigoTelefonoSelect.value = selectedOption.getAttribute('data-codigo-telefono');
            cargarEstados(this.value);
        });

        // Carga inicial de estados si ya hay un país seleccionado (para formularios de edición)
        if (paisSelect.value) {
            cargarEstados(paisSelect.value);
        }
    });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>