<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
require_once 'auth_check.php';
// 1. Incluir la configuración de la base de datos
require_once 'audit_log.php';
require_once 'config.php';

// 2. Verificar que los datos se envían por el método POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 3. Recoger y limpiar los datos del formulario
    $nombre = trim($_POST['nombre_completo']);
    $country_code = $_POST['country_code'];
    $phone_number = preg_replace('/[^0-9]/', '', $_POST['numero_celular']); // Limpiar número
    $email = trim($_POST['correo_electronico']);
    $direccion1 = trim($_POST['direccion1']);
    $direccion2 = trim($_POST['direccion2']);
    $ciudad = trim($_POST['ciudad']);
    $id_pais = isset($_POST['id_pais']) ? (int)$_POST['id_pais'] : null;
    $id_estado = isset($_POST['id_estado']) ? (int)$_POST['id_estado'] : null;
    $zip_code = trim($_POST['zip_code']);
    $notas = trim($_POST['notas_adicionales']);
    $in_sms = isset($_POST['in_sms']) ? 1 : 0;
    $in_email = isset($_POST['in_email']) ? 1 : 0;
    $celular = ''; // Inicializar

    // --- NUEVA VALIDACIÓN ---
    // Validar el formato del correo electrónico
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: clientes_lista.php?status=error&message_key=error_invalid_email");
        exit();
    }    
    // Unir código de país y número si se proporcionó un número
    if (!empty($phone_number)) {
        $celular = $country_code . ' ' . $phone_number;
    }
    // --- FIN DE LA NUEVA VALIDACIÓN ---

    // VERIFICACIÓN DE DUPLICADOS: Revisar si el correo electrónico ya existe
    $sql_check = "SELECT id_cliente FROM j106_clientes WHERE correo_electronico = ? AND id_negocio = ?";
    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("si", $email, $id_negocio_session);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            // Si encontramos un resultado, el correo ya existe.
            header("Location: clientes_lista.php?status=error&message_key=error_duplicate_entry");
            exit();
        }
        $stmt_check->close();
    }

    // 4. Preparar la consulta SQL para evitar inyecciones SQL (muy importante)
    $sql = "INSERT INTO j106_clientes (nombre_completo, numero_celular, correo_electronico, direccion1, direccion2, ciudad, id_pais, id_estado, zip_code, notas_adicionales, id_negocio, IN_SMS, IN_EMAIL) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Preparar la sentencia
    if ($stmt = $conn->prepare($sql)) {
        // 5. Vincular los parámetros
        // "ssssssiissiii"
        $stmt->bind_param("ssssssiissiii", $nombre, $celular, $email, $direccion1, $direccion2, $ciudad, $id_pais, $id_estado, $zip_code, $notas, $id_negocio_session, $in_sms, $in_email);

        // 6. Ejecutar la sentencia
        if ($stmt->execute()) {
            $id_nuevo_cliente = $stmt->insert_id;
            $descripcion_audit = "Se ha creado el cliente '{$nombre}' (ID: {$id_nuevo_cliente}).";
            registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'CREATE_CLIENT', $descripcion_audit);

            // Si todo va bien, redirigir
            header("Location: clientes_lista.php?status=success_create"); // Redirigir primero
            $stmt->close(); // Luego cerrar la sentencia
            exit(); // Terminar el script
        } else {
            // Si hay un error, redirigir con un mensaje de error
            header("Location: clientes_lista.php?status=error&message_key=operation_error");
        }

        // 7. Cerrar la sentencia
        $stmt->close();
    } else {
        header("Location: clientes_lista.php?status=error&message=" . urlencode($conn->error));
    }

    exit();
}
?>