# Plan de Pruebas de Citas - ZApp Citas

**Fecha de Actualización:** 01 de Diciembre de 2025

---

## 1. Objetivo

Verificar de forma integral el ciclo de vida completo de una cita, desde su creación hasta su finalización, a través de las tres interfaces de la aplicación: el Panel de Administración (`zapp_citas`), la SPA del Propietario (`spa_owner`) y la SPA del Cliente (`spa_client`). El objetivo es asegurar la consistencia de los datos y el correcto funcionamiento de la UI en todas las plataformas.

---

## 2. Prerrequisitos

1.  **Base de Datos Limpia:** Eliminar todas las citas existentes de la tabla `j108_citas` para empezar desde cero.
2.  **Entidades Activas:** Asegurarse de tener al menos:
    *   1 Negocio activo.
    *   1 Usuario "Propietario" activo, asociado a ese negocio.
    *   1 Cliente activo, asociado a ese negocio.
    *   1 Servicio activo, asociado a ese negocio.

---

## 3. Casos de Prueba (Estado Actual)

### Parte A: SPA del Propietario (`spa_owner`) - ¡Prioridad y Foco Principal!

| # | Funcionalidad | Estado | Notas |
|:-:|:---|:---:|:---|
| B1 | **Iniciar Sesión** | ✅ **Estable** | El login funciona y redirige a la agenda. |
| B2 | **Crear Cita (Servicio)** | ✅ **Estable** | El formulario crea la cita sin errores en la interfaz. |
| B3 | **Notificación al Crear Cita** | ✅ **Estable** | Envía correo al cliente, con copia (BCC) al negocio y el archivo `.ics` con la **zona horaria corregida**. |
| B4 | **Ver Citas (Agenda y Calendario)** | ✅ **Estable** | Las citas se muestran correctamente en la vista de agenda diaria y en el calendario semanal/mensual. |
| B5 | **Cambiar Estado de Cita** | ✅ **Estable** | El cambio de estado (Confirmar, Cancelar, etc.) desde la agenda funciona y **correctamente NO envía correo automático**. |
| B6 | **Enviar Email Manualmente** | ✅ **Estable** | La acción "📧 Enviar Email" funciona para todos los escenarios (Nueva, Cancelada, etc.) y sigue la lógica de BCC y adjunto `.ics`. |
| B7 | **Verificar Disponibilidad** | ✅ **Estable** | La vista de disponibilidad del día muestra correctamente los slots ocupados y libres. |
| B8 | **Eliminar Cita** | ✅ **Estable** | La eliminación de citas desde la agenda funciona. |
| B9 | **Crear Cita (Reunión)** | 🟡 **Pendiente** | **Siguiente paso:** Probar el flujo completo, incluyendo el guardado de invitados y el envío de notificaciones a múltiples correos. |

### Parte B: Panel de Administración (`zapp_citas`) - Funcionalidad de Respaldo

| # | Funcionalidad | Estado | Notas |
|:-:|:---|:---:|:---|
| A1 | **CRUD Básico de Citas** | 🟡 **Pendiente** | La funcionalidad existe pero no ha sido el foco. Las pruebas deben asegurar que no haya conflictos con la SPA. |
| A2 | **Envío de Correo** | ❌ **Deprecado** | La lógica de envío de correo desde el panel de admin está desactualizada. Se debe usar la SPA para notificaciones. |

### Parte C: SPA del Cliente (`spa_client`) - Pendiente de Revisión

| # | Funcionalidad | Estado | Notas |
|:-:|:---|:---:|:---|
| C1 | **Ciclo de Vida Completo** | 🟡 **Pendiente** | Toda la funcionalidad de la SPA del cliente (Login, Ver Cita, Confirmar, Cancelar, Agendar) necesita ser probada de forma integral. |

### Parte D: Funcionalidad Cruzada

| # | Funcionalidad | Estado | Notas |
|:-:|:---|:---:|:---|
| D1 | **Crear en SPA Owner, Ver en Admin** | ✅ **Estable** | Una cita creada en la SPA del propietario se refleja correctamente en el panel de admin (al refrescar). |
| D2 | **Cliente Confirma, Propietario Ve** | 🟡 **Pendiente** | Se debe verificar que los cambios de estado hechos por el cliente se reflejen en la SPA del propietario. |

---

## 4. Criterios de Éxito

*   Todas las pruebas marcadas como ✅ **Estable** se completan sin errores de JavaScript en la consola ni errores de PHP.
*   Los datos de las citas (estado, fecha, hora) son consistentes en las tres aplicaciones después de cada acción.
*   La interfaz de usuario se actualiza correctamente para reflejar los cambios.
*   Las notificaciones por correo electrónico se envían en los momentos esperados y con el contenido correcto.