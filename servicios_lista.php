<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
require_once 'auth_check.php';
require_once 'config.php';

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
    <title>Gestión de Servicios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: <?php echo $daily_bg_color; ?>;">
    <?php include 'navbar.php'; ?>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h3>Registrar Nuevo Servicio</h3></div>
                    <div class="card-body">
                        <?php if (isset($_GET['status'])): ?>
                            <div class="alert alert-<?php echo $_GET['status'] == 'success' ? 'success' : 'danger'; ?>">
                                <?php echo htmlspecialchars($_GET['message']); ?>
                            </div>
                        <?php endif; ?>
                        <form action="servicios_procesar_crear.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="nombre_servicio" class="form-label">Nombre del Servicio</label>
                                <input type="text" class="form-control" id="nombre_servicio" name="nombre_servicio" required>
                            </div>
                            <div class="mb-3">
                                <label for="foto_servicio" class="form-label">Foto del Servicio (Opcional)</label>
                                <input class="form-control" type="file" id="foto_servicio" name="foto_servicio" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Duración</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="duracion_valor" value="30" required>
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
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Guardar Servicio</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <h3>Lista de Servicios</h3>
                <h5 class="text-muted mb-3">Para: <?php echo htmlspecialchars($nombre_negocio_actual); ?></h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Foto</th>
                                <th>Servicio</th>
                                <th>Duración</th>
                                <th>Precio</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM j104_servicios WHERE id_negocio = ? ORDER BY activo DESC, nombre_servicio ASC";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $id_negocio_session);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $image_url = !empty($row['foto_servicio']) ? 'api_get_service_image.php?id=' . $row['id_servicio'] : 'assets/images/no-image.png';
                                    echo "<tr>";
                                    echo '<td><img src="' . $image_url . '" class="rounded-circle" width="40" height="40" alt="Foto" style="object-fit: cover;"></td>';
                                    echo "<td>" . htmlspecialchars($row["nombre_servicio"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["duracion_valor"]) . " " . htmlspecialchars($row["duracion_unidad"]) . "</td>";
                                    echo "<td>" . ($row["precio"] ? '$' . number_format($row["precio"], 2) : 'N/A') . "</td>";
                                    echo '<td><span class="badge ' . ($row['activo'] ? 'bg-success' : 'bg-danger') . '">' . ($row['activo'] ? 'Activo' : 'Inactivo') . '</span></td>';
                                    echo '<td><a href="servicios_editar.php?id=' . $row['id_servicio'] . '" class="btn btn-sm btn-warning">Editar</a></td>';
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>No hay servicios registrados.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>