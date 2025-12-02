<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-27-2025).
require_once 'auth_check.php';
require_once 'config.php';

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
    <title>Gestión de Citas - Servicios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; // Incluimos el menú de navegación ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Columna para el formulario de registro -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3>Registrar Nuevo Servicio</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        if (isset($_GET['status'])) {
                            if ($_GET['status'] == 'success') {
                                echo '<div class="alert alert-success">Servicio registrado con éxito.</div>';
                            } elseif ($_GET['status'] == 'success_edit') {
                                echo '<div class="alert alert-success">Servicio actualizado con éxito.</div>';
                            } elseif ($_GET['status'] == 'success_delete') {
                                echo '<div class="alert alert-success">Servicio eliminado con éxito.</div>';
                            } elseif ($_GET['status'] == 'error') {
                                $errorMessage = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Ocurrió un error.';
                                echo '<div class="alert alert-danger">' . $errorMessage . '</div>';
                            }
                        }
                        ?>
                        <form action="servicios_crear.php" method="POST">
                            <div class="mb-3">
                                <label for="nombre_servicio" class="form-label">Nombre del Servicio</label>
                                <input type="text" class="form-control" id="nombre_servicio" name="nombre_servicio" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Duración</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="duracion_valor" name="duracion_valor" value="30" required>
                                    <select class="form-select" name="duracion_unidad">
                                        <option value="Minutos" selected>Minutos</option>
                                        <option value="Horas">Horas</option>
                                        <option value="Dias">Días</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="precio" class="form-label">Precio (opcional)</label>
                                <input type="number" step="0.01" class="form-control" id="precio" name="precio">
                            </div>
                            <div class="d-grid gap-2 d-sm-flex">
                                <button type="submit" class="btn btn-primary flex-grow-1">Guardar Servicio</button>
                                <button type="reset" class="btn btn-secondary flex-grow-1">Limpiar Formulario</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Columna para la lista de servicios -->
            <div class="col-md-8">
                <h3>Lista de Servicios</h3>
                <h5 class="text-muted mb-3">Para: <?php echo htmlspecialchars($nombre_negocio_actual); ?></h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Servicio</th>
                                <th>Duración</th>
                                <th>Precio</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT id_servicio, nombre_servicio, duracion_valor, duracion_unidad, precio, activo FROM j104_servicios WHERE id_negocio = ? ORDER BY activo DESC, nombre_servicio ASC";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $id_negocio_session);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            $i = 1;
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $i++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row["nombre_servicio"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["duracion_valor"]) . " " . htmlspecialchars($row["duracion_unidad"]) . "</td>";
                                    $precio_formateado = $row["precio"] ? '$' . number_format($row["precio"], 2) : 'N/A';
                                    $estado_servicio = $row['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>';
                                    echo "<td>" . $precio_formateado . "</td>";
                                    echo "<td>" . $estado_servicio . "</td>";
                                    // Botones de Acciones
                                    echo '<td>
                                            <a href="servicios_editar.php?id=' . $row['id_servicio'] . '" class="btn btn-sm btn-warning">Editar</a>
                                            <form action="servicios_eliminar.php" method="POST" style="display:inline-block;" onsubmit="return confirm(\'¿Estás seguro de que quieres eliminar este servicio?\');">
                                                <input type="hidden" name="id_servicio" value="' . $row['id_servicio'] . '">
                                                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                            </form>
                                          </td>';
                                    echo "</tr>";
                                }
                            } else { // Cambiado de 5 a 6 columnas
                                echo "<tr><td colspan='6' class='text-center'>No hay servicios registrados.</td></tr>";
                            }
                            $stmt->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'footer.php'; ?>
</body>
</html>