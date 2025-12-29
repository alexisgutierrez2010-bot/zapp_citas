<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Aplicada la internacionalización (i18n).
require_once 'auth_check.php';
require_once 'config.php';

// --- LÓGICA DE ROLES Y FILTRADO ---
// Determinar si el usuario es Administrador de forma robusta
$es_administrador = (isset($rol_session) && strcasecmp(trim($rol_session), 'Administrador') == 0);

if (!$es_administrador) {
    // Si no es administrador, no tiene acceso a esta página.
    header("Location: dashboard.php?status=error&message=" . urlencode("Acceso no autorizado."));
    exit;
}

$id_negocio_filtro = isset($_GET['id_negocio_filtro']) ? (int)$_GET['id_negocio_filtro'] : 0;
$todos_los_negocios = [];
$result_todos_negocios = $conn->query("SELECT id_negocio, nombre_negocio FROM j102_negocios WHERE activo = 1 ORDER BY nombre_negocio ASC");
if ($result_todos_negocios) {
    while ($row = $result_todos_negocios->fetch_assoc()) {
        $todos_los_negocios[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: <?php echo $daily_bg_color; ?>;">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        Lista de Usuarios
                        <span class="badge bg-secondary fs-6"><?php echo htmlspecialchars($rol_session ?? 'Desconocido'); ?></span>
                    </h3>
                    <a href="usuarios_nuevo.php" class="btn btn-primary">Crear Nuevo Usuario</a>
                </div>
            </div>
            <div class="card-body">
                <?php if (isset($_GET['message'])): ?>
                    <div class="alert alert-<?php echo $_GET['status'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars(urldecode($_GET['message'])); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="GET" action="usuarios_lista.php" class="row g-3 align-items-center mb-3 bg-light p-3 rounded">
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

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Negocio</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT u.*, n.nombre_negocio 
                                    FROM j100_usuarios u
                                    JOIN j102_negocios n ON u.id_negocio = n.id_negocio";
                            
                            $params = [];
                            $types = "";
                            if ($id_negocio_filtro > 0) {
                                $sql .= " WHERE u.id_negocio = ?";
                                $params[] = $id_negocio_filtro;
                                $types .= "i";
                            }
                            $sql .= " ORDER BY u.nombre_usuario ASC";

                            $stmt = $conn->prepare($sql);
                            if (!empty($types)) {
                                $stmt->bind_param($types, ...$params);
                            }
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result && $result->num_rows > 0) {
                                $i = 1;
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $i++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nombre_usuario']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['correo_electronico']) . "</td>";
                                    echo "<td><span class='badge bg-secondary'>" . htmlspecialchars($row["rol"]) . "</span></td>";
                                    echo "<td>" . htmlspecialchars($row["nombre_negocio"]) . "</td>";
                                    $estado_usuario = $row['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>';
                                    echo "<td>" . $estado_usuario . "</td>";
                                    echo '<td>';
                                    
                                    // Lógica para mostrar los botones de acción
                                    // Un usuario no puede actuar sobre sí mismo
                                    if ($row['id_usuario'] != $_SESSION['id_usuario']) {
                                        echo '<a href="usuarios_editar.php?id=' . $row['id_usuario'] . '" class="btn btn-sm btn-warning">Editar</a> 
                                              <a href="usuarios_reset_clave.php?id=' . $row['id_usuario'] . '" class="btn btn-sm btn-info">Resetear Clave</a>
                                              <form action="usuarios_eliminar.php" method="POST" style="display:inline-block;" onsubmit="return confirm(\'¿Seguro que quieres desactivar este usuario?\');">
                                                  <input type="hidden" name="id_usuario" value="' . $row['id_usuario'] . '">
                                                  <button type="submit" class="btn btn-sm btn-danger">Desactivar</button>
                                              </form>';
                                    } else {
                                        echo '<span class="text-muted fst-italic">(Usuario actual)</span>';
                                    }
                                    echo '</td>';
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center'>No hay usuarios registrados.</td></tr>";
                            }
                            $stmt->close();
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