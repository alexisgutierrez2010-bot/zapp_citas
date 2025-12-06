# Tareas Pendientes y Recomendaciones - ZApp Citas

**Fecha de Actualización:** 01 de Diciembre de 2025

Este documento resume las tareas de optimización y corrección para la aplicación.

---

## 1. [COMPLETADO] Estandarizar Nombres de Archivos (Case-Sensitivity)

*   **Estado:** Se corrigieron todas las inclusiones de `Auth_check.php` a `auth_check.php` para asegurar la compatibilidad con servidores Linux.

---

## 2. [PARCIALMENTE COMPLETADO] Implementar Cierre Manual de Citas Vencidas

*   **Problema Original:** La actualización de citas a 'Vencida' se ejecutaba en cada carga de página, siendo ineficiente.

*   **Solución Adoptada:** Crear una funcionalidad de cierre manual para que el propietario la ejecute cuando lo necesite.

*   **Estado:**
    -   **[COMPLETADO]** Se creó la página `citas_cerrar_vencidas.php` para el panel de administración `zapp_citas`.
    -   **[COMPLETADO]** Se creó el endpoint `api_owner_citas_cerrar_vencidas.php` para la `spa_owner`.
    -   **[COMPLETADO]** Se corrigió y finalizó la integración del botón en la interfaz de `spa_owner.js`.

---

## 3. [PENDIENTE - ALTA PRIORIDAD] Mejorar la lógica de disponibilidad de horarios

*   **Problema:** El script `api_owner_horario_disponible.php` genera slots de tiempo a intervalos fijos y luego verifica si están ocupados. Es ineficiente si una cita larga cubre múltiples intervalos.
*   **Solución:** Refactorizar el bucle para que, al encontrar una cita, "salte" directamente al final de esa cita para continuar generando los siguientes slots disponibles.

---

## 4. [COMPLETADO] Implementar borrado lógico en `clientes_eliminar.php`

*   **Problema Original:** Se usaba `DELETE FROM`, lo cual era destructivo.
*   **Solución:** Se cambió la consulta a `UPDATE j106_clientes SET activo = 0 WHERE id_cliente = ?`. Esto es más seguro y preserva el historial.
*   **Estado:** Implementado en `clientes_eliminar.php` y la UI de `clientes_lista.php` fue actualizada para reflejar el cambio (botón "Desactivar").

---

## 5. [PENDIENTE - BAJA PRIORIDAD] Estandarizar Borrado Lógico (Refactorización)

*   **Problema:** La lógica de "eliminar" es inconsistente en la aplicación. Algunas entidades se desactivan (borrado lógico), pero otras, como los **Usuarios**, se eliminan permanentemente (`DELETE`), lo cual es un riesgo de seguridad de datos. Además, la interfaz de usuario (botones y textos) no es consistente.
*   **Solución Propuesta:**
    -   **`usuarios_eliminar.php`:** Modificar para que use `UPDATE j100_usuarios SET activo = 0` en lugar de `DELETE`.
    -   **UI del Panel de Admin:** Cambiar todos los botones "Eliminar" por "Desactivar" en `servicios_lista.php` y `usuarios_lista.php` para que coincida con la acción real.
    -   **UI de SPA Owner:** Cambiar los botones "Eliminar" por "Desactivar" en las vistas de Clientes y Servicios.
    -   **Filtrado de Datos:** Asegurarse de que los elementos inactivos (clientes, servicios, negocios) no aparezcan en los menús desplegables para agendar nuevas citas.

---

*Este documento sirve como bitácora de desarrollo y planificación.*