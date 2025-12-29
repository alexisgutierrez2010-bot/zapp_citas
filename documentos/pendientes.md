# Tareas Pendientes y Recomendaciones - ZApp Citas

**Fecha de Actualización:** 27 de Diciembre de 2025

Este documento resume las tareas de optimización y corrección para la aplicación.

---

## 1. [PENDIENTE - ALTA PRIORIDAD] Implementación de Login con OTP (Sin Contraseña)

*   **Objetivo:** Modernizar el sistema de autenticación para clientes y propietarios eliminando el uso de contraseñas estáticas.
*   **Tareas:**
    -   Modificar tabla `j106_clientes` y `j100_usuarios` para agregar campos temporales (`otp_codigo`, `otp_expiracion`).
    -   Crear API `api_login_solicitar.php` para generar y enviar códigos (Email/SMS/WhatsApp).
    -   Crear API `api_login_verificar.php` para validar el código e iniciar sesión.
    -   Actualizar interfaces de usuario (`auth.js`) para soportar el flujo de dos pasos.

---

## 2. [PENDIENTE - ALTA PRIORIDAD] Mejorar la lógica de disponibilidad de horarios

*   **Problema:** El script `api_owner_horario_disponible.php` genera slots de tiempo a intervalos fijos y luego verifica si están ocupados. Es ineficiente si una cita larga cubre múltiples intervalos.
*   **Solución:** Refactorizar el bucle para que, al encontrar una cita, "salte" directamente al final de esa cita para continuar generando los siguientes slots disponibles.

---

## 3. [PENDIENTE - BAJA PRIORIDAD] Estandarizar Borrado Lógico (Refactorización)

*   **Problema:** La lógica de "eliminar" es inconsistente en la aplicación. Algunas entidades se desactivan (borrado lógico), pero otras, como los **Usuarios**, se eliminan permanentemente (`DELETE`), lo cual es un riesgo de seguridad de datos. Además, la interfaz de usuario (botones y textos) no es consistente.
*   **Solución Propuesta:**
    -   **`usuarios_eliminar.php`:** Modificar para que use `UPDATE j100_usuarios SET activo = 0` en lugar de `DELETE`.
    -   **UI del Panel de Admin:** Cambiar todos los botones "Eliminar" por "Desactivar" en `servicios_lista.php` y `usuarios_lista.php` para que coincida con la acción real.
    -   **UI de SPA Owner:** Cambiar los botones "Eliminar" por "Desactivar" en las vistas de Clientes y Servicios.
    -   **Filtrado de Datos:** Asegurarse de que los elementos inactivos (clientes, servicios, negocios) no aparezcan en los menús desplegables para agendar nuevas citas.

---

## Tareas Recientemente Completadas

*   **[COMPLETADO] Mejora de UI Móvil (Menú Flotante/FAB):** Se implementó un menú flotante responsivo para mejorar la navegación en dispositivos móviles en ambas aplicaciones (`spa_client` y `spa_owner`).

---

*Este documento sirve como bitácora de desarrollo y planificación.*