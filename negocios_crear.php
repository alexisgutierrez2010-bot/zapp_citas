<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025). Corregido para compatibilidad con Linux.
require_once 'auth_check.php';
require_once 'audit_log.php';
require_once 'config.php';

// Solo un Master puede crear nuevos negocios
/*
if ($rol_session != 'Master') {
    header("Location: dashboard.php?status=error&message=" . urlencode("No tienes permiso para esta acción."));
    exit;
}
*/

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();

    try {
        // 1. Recoger datos del negocio
        $nombre_negocio = trim($_POST['nombre_negocio']);
        $email_negocio = trim($_POST['email']);

        // 2. Recoger datos del usuario Propietario
        $admin_user = trim($_POST['admin_user']);
        $admin_pass = $_POST['admin_pass'];
        $admin_email = trim($_POST['admin_email']);

        // 3. Validar que los campos no estén vacíos
        if (empty($nombre_negocio) || empty($email_negocio) || empty($admin_user) || empty($admin_pass) || empty($admin_email)) {
            throw new Exception("Todos los campos son obligatorios.");
        }

        // --- NUEVA VALIDACIÓN ---
        if (!filter_var($email_negocio, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El formato del email del negocio no es válido.");
        }
        if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El formato del email del usuario propietario no es válido.");
        }
        // No hay teléfono en el formulario de creación, se valida en la actualización.

        // 3.5. Verificar que el negocio no exista
        $sql_check_negocio = "SELECT id_negocio FROM j102_negocios WHERE nombre_negocio = ? OR email = ?";
        $stmt_check_negocio = $conn->prepare($sql_check_negocio);
        $stmt_check_negocio->bind_param("ss", $nombre_negocio, $email_negocio);
        $stmt_check_negocio->execute();
        $stmt_check_negocio->store_result();
        if ($stmt_check_negocio->num_rows > 0) {
            throw new Exception("El nombre del negocio o el email de contacto ya existen.");
        }
        $stmt_check_negocio->close();


        // 4. Insertar el nuevo negocio
        $sql_negocio = "INSERT INTO j102_negocios (nombre_negocio, email) VALUES (?, ?)";
        $stmt_negocio = $conn->prepare($sql_negocio);
        $stmt_negocio->bind_param("ss", $nombre_negocio, $email_negocio);
        if (!$stmt_negocio->execute()) {
            throw new Exception("Error al insertar el negocio: " . $stmt_negocio->error);
        }
        $id_nuevo_negocio = $stmt_negocio->insert_id;
        $stmt_negocio->close();

        if ($id_nuevo_negocio <= 0) {
            throw new Exception("No se pudo crear el negocio.");
        }

        // 5. Crear el usuario Propietario para el nuevo negocio
        $password_hash = password_hash($admin_pass, PASSWORD_DEFAULT);
        $rol_propietario = 'Propietario';

        // Verificar que el usuario no exista
        $sql_check_user = "SELECT id_usuario FROM j100_usuarios WHERE nombre_usuario = ? OR correo_electronico = ?";
        $stmt_check_user = $conn->prepare($sql_check_user);
        $stmt_check_user->bind_param("ss", $admin_user, $admin_email);
        $stmt_check_user->execute();
        $stmt_check_user->store_result();
        if ($stmt_check_user->num_rows > 0) {
            throw new Exception("El nombre de usuario o correo para el Propietario ya existe.");
        }
        $stmt_check_user->close();

        $sql_usuario = "INSERT INTO j100_usuarios (nombre_usuario, correo_electronico, password_hash, rol, id_negocio) VALUES (?, ?, ?, ?, ?)";
        $stmt_user = $conn->prepare($sql_usuario);
        $stmt_user->bind_param("ssssi", $admin_user, $admin_email, $password_hash, $rol_propietario, $id_nuevo_negocio);
        $stmt_user->execute();

        // 7. Actualizar las fechas de prueba del nuevo negocio
        $sql_update_trial = "UPDATE j102_negocios SET fecha_habilitacion = NOW(), fecha_desactivacion = DATE_ADD(NOW(), INTERVAL dias_prueba DAY) WHERE id_negocio = ?";
        $stmt_trial = $conn->prepare($sql_update_trial);
        $stmt_trial->bind_param("i", $id_nuevo_negocio);
        $stmt_trial->execute();
        $stmt_user->close();
        $stmt_trial->close();

        // 6. Si todo fue bien, confirmar la transacción
        $conn->commit();
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_nuevo_negocio, 'CREATE_BUSINESS', "Se creó el negocio '{$nombre_negocio}' (ID: {$id_nuevo_negocio}) y su usuario Propietario '{$admin_user}'.");
        header("Location: negocios_configuracion.php?status=success&message=" . urlencode("Negocio y Propietario creados con éxito."));

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: crear_negocio.php?status=error&message=" . urlencode($e->getMessage()));
    }

    exit();
}
?>