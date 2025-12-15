<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-01-2025).

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

    // CORRECCIÓN DEFINITIVA: Unificar la lógica de sesión.
    // Esta función es llamada desde el panel de admin (que usa $_SESSION['id_negocio'])
    // y desde la SPA del owner (que usa $_SESSION['owner_id_negocio']).
    // Si el parámetro $id_negocio viene como nulo, la función debe ser capaz de
    // encontrar el ID de negocio correcto desde la sesión que esté activa.
    if ($id_negocio === null) {
        $id_negocio = $_SESSION['id_negocio'] ?? $_SESSION['owner_id_negocio'] ?? null;
    }

    $sql = "INSERT IGNORE INTO j099_auditorias (id_usuario, id_negocio, accion, descripcion, ip_address) VALUES (?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        // --- SOLUCIÓN DEFINITIVA: Asignar todos los parámetros a variables locales ---
        // La función `bind_param` requiere que sus argumentos sean variables pasadas por referencia,
        // de lo contrario genera una advertencia que corrompe la salida JSON.
        // Al asignar todos los valores a variables locales antes de la llamada, se soluciona el problema.
        $id_usuario_ref = $id_usuario;
        $id_negocio_ref = $id_negocio;
        $tipo_evento_ref = $tipo_evento;
        $descripcion_ref = $descripcion;
        $ip_address_ref = $ip_address;
        $stmt->bind_param("iisss", $id_usuario_ref, $id_negocio_ref, $tipo_evento_ref, $descripcion_ref, $ip_address_ref);
        $stmt->execute();
        $stmt->close();
    }
}
?>