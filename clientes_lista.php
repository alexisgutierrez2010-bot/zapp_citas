<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.
require_once 'auth_check.php';
require_once 'config.php';

// --- LÓGICA DE ROLES Y FILTRADO ---
// Determinar si el usuario es Administrador de forma robusta
$es_administrador = (isset($rol_session) && strcasecmp(trim($rol_session), 'Administrador') == 0);

$id_negocio_filtro = 0;
$todos_los_negocios = [];
if ($es_administrador) {
    $id_negocio_filtro = isset($_GET['id_negocio_filtro']) ? (int)$_GET['id_negocio_filtro'] : 0;
    $result_todos_negocios = $conn->query("SELECT id_negocio, nombre_negocio FROM j102_negocios WHERE activo = 1 ORDER BY nombre_negocio ASC");
    if ($result_todos_negocios) {
        while ($row = $result_todos_negocios->fetch_assoc()) {
            $todos_los_negocios[] = $row;
        }
    }
} else {
    // Si no es Administrador (es Propietario), solo puede ver su propio negocio
    $id_negocio_filtro = $id_negocio_session;
}

// Obtener la lista de países para los menús desplegables
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
    <title>Gestión de Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: <?php echo $daily_bg_color; ?>;">
    <?php include 'navbar.php'; ?>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        Lista de Clientes
                        <span class="badge bg-secondary fs-6"><?php echo htmlspecialchars($rol_session ?? 'Desconocido'); ?></span>
                    </h3>
                    <?php if ($es_administrador): ?>
                        <a href="clientes_nuevo.php" class="btn btn-primary">Crear Nuevo Cliente</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (isset($_GET['message'])): ?>
                    <div class="alert alert-<?php echo $_GET['status'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars(urldecode($_GET['message'])); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($es_administrador): ?>
                <form method="GET" action="clientes_lista.php" class="row g-3 align-items-center mb-3 bg-light p-3 rounded">
                    <div class="col-md-8">
                        <label for="id_negocio_filtro" class="form-label">Filtrar por Negocio</label>
                        <select name="id_negocio_filtro" id="id_negocio_filtro" class="form-select">
                            <option value="0">Todos los Negocios</option>
                            <?php foreach ($todos_los_negocios as $negocio): ?>
                                <option value="<?php echo $negocio['id_negocio']; ?>" <?php echo ($id_negocio_filtro == $negocio['id_negocio']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($negocio['nombre_negocio']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-grid">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-info">Filtrar</button>
                    </div>
                </form>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Celular</th>
                                <th>Email</th>
                                <?php if ($es_administrador): ?>
                                <th>Negocio</th>
                                <?php endif; ?>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT c.*, n.nombre_negocio FROM j106_clientes c JOIN j102_negocios n ON c.id_negocio = n.id_negocio";
                            $params = [];
                            $types = "";
                            if ($id_negocio_filtro > 0) {
                                $sql .= " WHERE c.id_negocio = ?";
                                $params[] = $id_negocio_filtro;
                                $types .= "i";
                            }
                            $sql .= " ORDER BY c.nombre_completo ASC";
                            
                            $stmt = $conn->prepare($sql);
                            if (!empty($types)) {
                                $stmt->bind_param($types, ...$params);
                            }
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                $i = 1;
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $i++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row["nombre_completo"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["numero_celular"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["correo_electronico"]) . "</td>";
                                    if ($es_administrador) {
                                        echo "<td>" . htmlspecialchars($row["nombre_negocio"]) . "</td>";
                                    }
                                    echo '<td><span class="badge ' . ($row['activo'] ? 'bg-success' : 'bg-danger') . '">' . ($row['activo'] ? 'Activo' : 'Inactivo') . '</span></td>';
                                    echo '<td>
                                            <a href="clientes_editar.php?id=' . $row['id_cliente'] . '" class="btn btn-sm btn-warning">Editar</a>
                                            <form action="clientes_eliminar.php" method="POST" class="d-inline" onsubmit="return confirm(\'¿Seguro que quieres desactivar este cliente?\');">
                                                <input type="hidden" name="id_cliente" value="' . $row['id_cliente'] . '">
                                                <button type="submit" class="btn btn-sm btn-danger">Desactivar</button>
                                            </form>
                                          </td>';
                                    echo "</tr>";
                                }
                            } else {
                                $colspan = $es_administrador ? 7 : 6;
                                echo "<tr><td colspan='{$colspan}' class='text-center'>No hay clientes registrados que coincidan con el filtro.</td></tr>";
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