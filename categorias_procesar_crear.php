<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_categoria = trim($_POST['nombre_categoria']);
    $descripcion = trim($_POST['descripcion']);
    $activo = isset($_POST['activo']) ? 1 : 0;

    if (empty($nombre_categoria)) {
        header("Location: categorias_lista.php?status=error&message=" . urlencode("El nombre de la categoría es obligatorio."));
        exit();
    }

    // Verificar duplicados
    $stmt_check = $conn->prepare("SELECT id_categoria FROM j103_categorias WHERE nombre_categoria = ?");
    $stmt_check->bind_param("s", $nombre_categoria);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        header("Location: categorias_lista.php?status=error&message=" . urlencode("Ya existe una categoría con ese nombre."));

        exit();
    }
    $stmt_check->close();

    $sql = "INSERT INTO j103_categorias (nombre_categoria, descripcion, activo, fecha_registro) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $nombre_categoria, $descripcion, $activo);

    if ($stmt->execute()) {
        registrar_auditoria($conn, $_SESSION['id_usuario'], null, 'CREATE_CATEGORY', "Se creó la categoría '{$nombre_categoria}'.");
        header("Location: categorias_lista.php?status=success");
    } else {
        header("Location: categorias_lista.php?status=error&message=" . urlencode("Error al crear la categoría: " . $stmt->error));
    }
    $stmt->close();
    exit();
}