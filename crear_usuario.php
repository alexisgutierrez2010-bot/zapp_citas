<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
// --- SCRIPT DE ADMINISTRACIÓN PARA CREAR/REINICIAR USUARIOS ---
// ADVERTENCIA: Este script es para uso administrativo. No debe ser accesible al público.
require_once 'config.php'; // Este archivo no debería llamar a config.php dos veces.
// require_once '../config.php'; // Eliminamos la referencia incorrecta

// --- DATOS DEL USUARIO A CREAR O ACTUALIZAR ---
$nombre_usuario = 'master'; // Usuario a restablecer
$password_plano = 'master123'; // Nueva contraseña
$rol = 'Master'; // Rol del usuario
$id_config_negocio = 1; // ID del negocio principal
$email_usuario = 'master@negocio.com'; // Email del usuario

// 1. Encriptar la contraseña de forma segura
$password_hash = password_hash($password_plano, PASSWORD_DEFAULT);

// 2. Preparar la consulta SQL
// Esta consulta buscará al usuario. Si existe, actualizará su contraseña. Si no, lo creará.
$sql = "INSERT INTO j100_usuarios (nombre_usuario, password_hash, correo_electronico, rol, id_negocio) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        password_hash = VALUES(password_hash), rol = VALUES(rol), id_negocio = VALUES(id_negocio)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssi", $nombre_usuario, $password_hash, $email_usuario, $rol, $id_config_negocio);

// 3. Ejecutar y mostrar resultado
if ($stmt->execute()) {
    echo "<h1>¡Éxito!</h1>";
    echo "<p>El usuario '<strong>" . htmlspecialchars($nombre_usuario) . "</strong>' ha sido creado/actualizado con éxito.</p>";
    echo "<p>La contraseña ahora es: <strong>" . htmlspecialchars($password_plano) . "</strong></p>";
    echo "<p><a href='sesion_iniciar.php'>Ir a la página de Login</a></p>";
} else {
    echo "<h1>Error</h1>";
    echo "<p>No se pudo crear o actualizar el usuario: " . $stmt->error . "</p>";
}

$stmt->close();
$conn->close();