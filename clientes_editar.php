<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
require_once 'auth_check.php';
require_once 'config.php';

// 1. Verificar que se ha proporcionado un ID válido
$id_cliente = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_cliente <= 0) {
    header("Location: dashboard.php?status=error&message=" . urlencode("ID de cliente no válido."));
    exit();
}

// 2. Obtener los datos actuales del cliente
$cliente = []; // Inicializar la variable para evitar errores
$sql = "SELECT 
            c.id_cliente, c.nombre_completo, c.numero_celular, c.correo_electronico,
            c.direccion1, c.direccion2, c.ciudad, c.zip_code, c.notas_adicionales, c.IN_SMS, c.IN_EMAIL, c.activo,
            c.id_pais, c.id_estado
        FROM j106_clientes c
        WHERE c.id_cliente = ? AND c.id_negocio = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ii", $id_cliente, $id_negocio_session);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $cliente = $result->fetch_assoc();
    } else {
        header("Location: clientes_lista.php?status=error&message=" . urlencode("Cliente no encontrado o no pertenece a tu negocio."));
        exit();
    }
    $stmt->close();
} else {
    // Si la preparación de la consulta falla
    die("Error al preparar la consulta para obtener los datos del cliente.");
}

// Obtener la lista de países para los menús desplegables
$paises = [];
$paises_result = $conn->query("SELECT id_pais, nombre_pais, codigo_telefono FROM j110_paises ORDER BY nombre_pais ASC");
while ($row = $paises_result->fetch_assoc()) {
    $paises[] = $row;
}

// Lógica para separar el código de país del número de teléfono
$phone_number_display = $cliente['numero_celular'];
$country_code_display = ''; 

if (!empty($cliente['numero_celular'])) {
    $parts = explode(' ', $cliente['numero_celular'], 2);
    if (count($parts) === 2) {
        $country_code_display = $parts[0];
        $phone_number_display = $parts[1];
    } else {
        // Si no hay espacio, asumimos que es solo el número
        $phone_number_display = $cliente['numero_celular'];
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('clients_edit_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo __('clients_editing'); ?>: <?php echo htmlspecialchars($cliente['nombre_completo'] ?? 'Cliente'); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php
                        if (isset($_GET['message_key'])) {
                            $message = __($_GET['message_key']);
                            echo "<div class='alert alert-danger'>" . htmlspecialchars($message) . "</div>";
                        } elseif (isset($_GET['status']) && $_GET['status'] == 'error') {
                            // Fallback para mensajes antiguos
                            $errorMessage = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : __('operation_error');
                            echo '<div class="alert alert-danger">' . $errorMessage . '</div>';
                        }
                        ?>
                        <form action="clientes_actualizar.php" method="POST">
                            <!-- Campo oculto para enviar el ID del cliente -->
                            <input type="hidden" name="id_cliente" value="<?php echo $cliente['id_cliente']; ?>">

                            <div class="mb-3">
                                <label for="nombre" class="form-label"><?php echo __('clients_form_name'); ?></label>
                                <input type="text" class="form-control" id="nombre" name="nombre_completo" value="<?php echo htmlspecialchars($cliente['nombre_completo']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="celular" class="form-label"><?php echo __('clients_form_phone'); ?></label>
                                <div class="input-group">
                                    <select class="form-select" id="country_code" name="country_code" style="max-width: 120px;">
                                        <?php foreach ($paises as $pais): ?>
                                            <option value="<?php echo htmlspecialchars($pais['codigo_telefono']); ?>" <?php echo ($country_code_display == $pais['codigo_telefono']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($pais['codigo_telefono']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="tel" class="form-control" id="numero_celular" name="numero_celular" value="<?php echo htmlspecialchars($phone_number_display); ?>" placeholder="Ej: 4121234567">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label"><?php echo __('clients_form_email'); ?></label>
                                <input type="email" class="form-control" id="email" name="correo_electronico" value="<?php echo htmlspecialchars($cliente['correo_electronico']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="direccion1" class="form-label"><?php echo __('clients_form_address1'); ?></label>
                                <input type="text" class="form-control" id="direccion1" name="direccion1" value="<?php echo htmlspecialchars($cliente['direccion1'] ?? ''); ?>" maxlength="128">
                            </div>
                            <div class="mb-3">
                                <label for="direccion2" class="form-label"><?php echo __('clients_form_address2'); ?></label>
                                <input type="text" class="form-control" id="direccion2" name="direccion2" value="<?php echo htmlspecialchars($cliente['direccion2'] ?? ''); ?>" maxlength="128">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="id_pais" class="form-label"><?php echo __('clients_form_country'); ?></label>
                                    <select class="form-select" id="id_pais" name="id_pais" required>
                                        <option value=""><?php echo __('businesses_form_select_country'); ?></option>
                                        <?php foreach ($paises as $pais): ?>
                                            <option value="<?php echo $pais['id_pais']; ?>" data-codigo-telefono="<?php echo htmlspecialchars($pais['codigo_telefono']); ?>" <?php echo ($cliente['id_pais'] == $pais['id_pais']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($pais['nombre_pais']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="id_estado" class="form-label"><?php echo __('clients_form_state'); ?></label>
                                    <select class="form-select" id="id_estado" name="id_estado" required disabled>
                                        <option value=""><?php echo __('businesses_form_loading'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="ciudad" class="form-label"><?php echo __('clients_form_city'); ?></label>
                                    <input type="text" class="form-control" id="ciudad" name="ciudad" value="<?php echo htmlspecialchars($cliente['ciudad'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="zip_code" class="form-label"><?php echo __('clients_form_zip'); ?></label>
                                    <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($cliente['zip_code'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="notas" class="form-label"><?php echo __('clients_form_notes'); ?></label>
                                <textarea class="form-control" id="notas" name="notas_adicionales" rows="3"><?php echo htmlspecialchars($cliente['notas_adicionales']); ?></textarea>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <label class="form-label"><?php echo __('clients_form_communication'); ?></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="in_sms" value="1" id="in_sms_editar" <?php echo ($cliente['IN_SMS'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="in_sms_editar"><?php echo __('clients_form_sms_notifications'); ?></label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="in_email" value="1" id="in_email_editar" <?php echo ($cliente['IN_EMAIL'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="in_email_editar"><?php echo __('clients_form_email_notifications'); ?></label>
                                </div>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" <?php echo ($cliente['activo'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="activo"><?php echo __('clients_form_active'); ?></label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success w-100"><?php echo __('clients_form_update'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const paisSelect = document.getElementById('id_pais');
        const estadoSelect = document.getElementById('id_estado');
        const codigoTelefonoSelect = document.getElementById('country_code');
        const idEstadoGuardado = <?php echo json_encode($cliente['id_estado'] ?? null); ?>;

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

        // Carga inicial de estados para el país ya seleccionado
        cargarEstados(paisSelect.value, idEstadoGuardado);
    });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>