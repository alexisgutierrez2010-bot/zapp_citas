<?php
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_estado = (int)$_POST['id_estado'];
    $id_pais = (int)$_POST['id_pais'];
    $nombre_estado = trim($_POST['nombre_estado']);

    if ($id_estado <= 0 || $id_pais <= 0 || empty($nombre_estado)) {
        header("Location: paises_lista.php?status=error&message=" . urlencode("Datos inválidos."));
        exit();
    }

    $sql = "UPDATE j111_estados SET nombre_estado = ? WHERE id_estado = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $nombre_estado, $id_estado);

    if ($stmt->execute()) {
        registrar_auditoria($conn, $_SESSION['id_usuario'], $id_negocio_session, 'UPDATE_STATE', "Se actualizó el estado '{$nombre_estado}' (ID: {$id_estado}).");
        header("Location: estados_lista.php?id_pais={$id_pais}&status=success_edit");
    } else {
        header("Location: estados_editar.php?id={$id_estado}&status=error&message=" . urlencode("Error: " . $stmt->error));
    }
    $stmt->close();
    exit();
}
?>