<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).

/**
 * Registra un evento en la tabla de auditoría.
 *
 * @param mysqli $conn La conexión a la base de datos.
 * @param int|null $id_usuario El ID del usuario que realiza la acción (puede ser null).
 * @param int|null $id_negocio El ID del negocio asociado al evento (puede ser null).
 * @param string $tipo_evento El tipo de evento (ej. 'LOGIN_SUCCESS', 'CLIENT_REGISTER').
 * @param string $descripcion Una descripción detallada del evento.
 */
function registrar_auditoria($conn, $id_usuario, $id_negocio, $tipo_evento, $descripcion) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

    $sql = "INSERT INTO j101_auditoria (id_usuario, id_negocio, tipo_evento, descripcion, ip_address) VALUES (?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iisss", $id_usuario, $id_negocio, $tipo_evento, $descripcion, $ip_address);
        $stmt->execute();
        $stmt->close();
    }
}
?>