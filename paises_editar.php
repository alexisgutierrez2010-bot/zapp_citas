<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Aplicada la internacionalización (i18n).
require_once 'Auth_check.php';
require_once 'config.php';

$id_pais = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_pais <= 0) {
    header("Location: paises_lista.php?status=error&message=" . urlencode("ID de país no válido."));
    exit();
}

$stmt = $conn->prepare("SELECT * FROM j110_paises WHERE id_pais = ?");
$stmt->bind_param("i", $id_pais);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    header("Location: paises_lista.php?status=error&message=" . urlencode("País no encontrado."));
    exit();
}
$pais = $result->fetch_assoc();
$stmt->close();

$timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('locations_edit_country_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h3><?php echo __('locations_editing_country'); ?>: <?php echo htmlspecialchars($pais['nombre_pais']); ?></h3></div>
                    <div class="card-body">
                        <form action="paises_actualizar.php" method="POST">
                            <input type="hidden" name="id_pais" value="<?php echo $pais['id_pais']; ?>">
                            <div class="mb-3">
                                <label for="nombre_pais" class="form-label"><?php echo __('locations_form_country_name'); ?></label>
                                <input type="text" class="form-control" id="nombre_pais" name="nombre_pais" value="<?php echo htmlspecialchars($pais['nombre_pais']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="codigo_pais" class="form-label"><?php echo __('locations_form_country_code'); ?></label>
                                <input type="text" class="form-control" id="codigo_pais" name="codigo_pais" value="<?php echo htmlspecialchars($pais['codigo_pais']); ?>" maxlength="2" required>
                            </div>
                            <div class="mb-3">
                                <label for="codigo_telefono" class="form-label"><?php echo __('locations_form_phone_code'); ?></label>
                                <input type="text" class="form-control" id="codigo_telefono" name="codigo_telefono" value="<?php echo htmlspecialchars($pais['codigo_telefono']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="timezone" class="form-label"><?php echo __('locations_form_timezone'); ?></label>
                                <select class="form-select" id="timezone" name="timezone" required>
                                    <?php foreach ($timezones as $tz): ?>
                                        <option value="<?php echo $tz; ?>" <?php echo ($pais['timezone'] == $tz) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tz); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success"><?php echo __('locations_update_country'); ?></button>
                                <a href="paises_lista.php?lang=<?php echo $lang; ?>" class="btn btn-secondary"><?php echo __('cancel'); ?></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- El footer ya se incluye, no se necesita duplicar -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>