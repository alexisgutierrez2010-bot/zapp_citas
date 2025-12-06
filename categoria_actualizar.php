<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).
require_once 'auth_check.php';
require_once 'config.php';
require_once 'audit_log.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_categoria = (int)$_POST['id_categoria'];
    $nombre_categoria = trim($_POST['nombre_categoria']);
    $descripcion = trim($_POST['descripcion']);
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($id_categoria <= 0 || empty($nombre_categoria)) {
        header("Location: categorias_lista.php?status=error&message=" . urlencode("Datos inválidos."));
        exit();
    }

    // Verificar duplicados (excluyendo la categoría actual)
    $stmt_check = $conn->prepare("SELECT id_categoria FROM j103_categorias WHERE nombre_categoria = ? AND id_categoria != ?");
    $stmt_check->bind_param("si", $nombre_categoria, $id_categoria);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        header("Location: categorias_lista.php?status=error&message=" . urlencode("Ya existe otra categoría con ese nombre."));
        exit();
    }
    $stmt_check->close();

    $sql = "UPDATE j103_categorias SET nombre_categoria = ?, descripcion = ?, activo = ? WHERE id_categoria = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $nombre_categoria, $descripcion, $activo, $id_categoria);

    if ($stmt->execute()) {
        registrar_auditoria($conn, $_SESSION['id_usuario'], null, 'UPDATE_CATEGORY', "Se actualizó la categoría '{$nombre_categoria}' (ID: {$id_categoria}).");
        header("Location: categorias_lista.php?status=success");
    } else {
        header("Location: categorias_lista.php?status=error&message=" . urlencode("Error al actualizar la categoría: " . $stmt->error));
    }
    $stmt->close();
    exit();
}

?>