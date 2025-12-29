# Plan de Pruebas Integral - ZApp Citas

**Fecha de Actualización:** 27 de Diciembre de 2025

---

## 1. Objetivo

Este documento establece un plan de pruebas exhaustivo para validar la funcionalidad completa del ecosistema ZApp Citas desde una instalación limpia. El objetivo es asegurar que todos los módulos, flujos de usuario y funcionalidades críticas operen como se espera antes de un despliegue en producción.

---

## 2. Fase 0: Preparación del Entorno de Pruebas

| # | Tarea | Pasos a Seguir | Resultado Esperado | Estado |
|:-:|:---|:---|:---|:---:|
| 0.1 | **Instalación de la Base de Datos** | 1. Crear una base de datos MySQL/MariaDB nueva y vacía.<br>2. Importar la estructura y datos iniciales desde el archivo `zapp_citas.sql` más reciente. | La base de datos se crea con todas las tablas y datos maestros (países, etc.) sin errores. | ☐ |
| 0.2 | **Configuración del Proyecto** | 1. Colocar los archivos del proyecto en el directorio del servidor web (ej. `htdocs`).<br>2. Editar `config.php` con las credenciales de la nueva base de datos y la configuración SMTP. | El proyecto está accesible desde el navegador. | ☐ |
| 0.3 | **Creación de Usuario Master** | 1. Insertar manualmente un usuario con `rol = 'Master'` en la tabla `j100_usuarios`.<br>2. Asignarle `id_negocio = 1` (el negocio principal por defecto). | El usuario "Master" existe y está listo para iniciar sesión en el panel de administración. | ☐ |

---

## 3. Fase 1: Pruebas del Panel de Administración (`zapp_citas`)

El objetivo de esta fase es validar la capacidad del administrador para configurar el ecosistema.

| # | Caso de Prueba | Pasos a Seguir | Resultado Esperado | Estado |
|:-:|:---|:---|:---|:---:|
| 1.1 | **Login y Acceso** | 1. Navegar a `sesion_iniciar.php`.<br>2. Intentar login con credenciales incorrectas.<br>3. Iniciar sesión con el usuario "Master". | 1. Se muestra error de credenciales.<br>2. Se accede al `dashboard.php`. | ☐ |
| 1.2 | **Creación de Negocio** | 1. Ir a "Negocios" -> "Crear Nuevo Negocio".<br>2. Llenar el formulario con datos de un nuevo negocio y su propietario.<br>3. Guardar. | 1. El negocio se crea con `activo = 4` (Pendiente).<br>2. El usuario propietario se crea en la BD.<br>3. Se reciben los correos de notificación (propietario y admin).<br>4. Se redirige a la página de configuración del nuevo negocio. | ☐ |
| 1.3 | **Aprobación de Negocio** | 1. En la página de configuración del negocio, cambiar el estado de "Pendiente por Aprobar" a "Activo".<br>2. Guardar los cambios. | 1. El estado del negocio en la BD cambia a `activo = 1`.<br>2. El propietario ahora debería poder iniciar sesión. | ☐ |
| 1.4 | **Configuración Completa** | 1. Editar el negocio recién creado.<br>2. Ajustar el horario de trabajo y subir una imagen de fondo.<br>3. Guardar. | 1. Todos los datos se guardan correctamente en la BD.<br>2. La imagen de fondo se muestra en la vista previa. | ☐ |
| 1.5 | **Gestión de CRUDs** | 1. Crear, editar y desactivar un Cliente.<br>2. Crear, editar y desactivar un Servicio.<br>3. Crear, editar y cambiar estado de una Cita. | Todas las operaciones CRUD funcionan sin errores y los cambios se reflejan en la base de datos. | ☐ |

---

## 4. Fase 2: Pruebas de la SPA del Propietario (`spa_owner`)

El objetivo es validar el flujo de auto-registro y la gestión diaria del negocio por parte del propietario.

| # | Caso de Prueba | Pasos a Seguir | Resultado Esperado | Estado |
|:-:|:---|:---|:---|:---:|
| 2.1 | **Auto-Registro de Propietario (OTP)** | 1. Ir a `spa_owner.php` y hacer clic en "Registrar Negocio".<br>2. Ingresar correo para recibir código.<br>3. Ingresar el código de verificación recibido.<br>4. Llenar el formulario de registro completo y enviar. | 1. Se recibe el código por email.<br>2. Se valida el código y se muestra el formulario.<br>3. El negocio se crea con `activo = 4` (Pendiente).<br>4. Se reciben los correos de notificación. | ☐ |
| 2.2 | **Login de Propietario (OTP)** | 1. Ir a `spa_owner.php` e ingresar el email/teléfono.<br>2. Intentar login con el negocio recién creado (estado Pendiente).<br>3. Usar el Panel de Admin para aprobar el negocio (cambiar `activo` a `1`).<br>4. Volver a intentar el login, ingresando el código recibido. | 1. El login falla con mensaje de "Pendiente de aprobación".<br>2. El login es exitoso.<br>3. La imagen de fondo personalizada se carga correctamente. | ☐ |
| 2.3 | **Gestión de Citas (SPA)** | 1. Crear un cliente y un servicio desde la SPA.<br>2. Ir a "Mi Agenda" y crear una cita de tipo "Servicio".<br>3. Crear una cita de tipo "Reunión" con invitados.<br>4. Verificar que se envíen los correos de notificación. | Todas las operaciones se completan sin errores y los datos son consistentes con el Panel de Admin. | ☐ |
| 2.4 | **Ciclo de Vida de Cita (SPA)** | 1. En "Mi Agenda", cambiar el estado de una cita (Confirmar, Completar, Cancelar).<br>2. Editar una cita (cambiar hora).<br>3. Eliminar físicamente una cita (acción "🔥 Eliminar"). | 1. Los cambios de estado se reflejan correctamente.<br>2. La edición se guarda.<br>3. La cita se elimina de la base de datos tras la doble confirmación. | ☐ |
| 2.5 | **Dashboard y Configuración** | 1. Ir a "Dashboard" y verificar que los gráficos muestren datos.<br>2. Ir a "Mi Negocio" y cambiar el horario.<br>3. Ir a "Mi Perfil" y cambiar la contraseña. | 1. Los gráficos se renderizan.<br>2. La configuración se guarda.<br>3. El cambio de contraseña funciona y se puede volver a iniciar sesión. | ☐ |
| 2.6 | **Verificación de UI Responsiva** | 1. Abrir `spa_owner.php` en una vista de móvil (o reducir el ancho del navegador).<br>2. Navegar entre secciones. | 1. El menú de navegación principal se colapsa.<br>2. Aparece un Menú Flotante (FAB) en la esquina inferior derecha.<br>3. El FAB permite acceder a las vistas principales. | ✅ |

---

## 5. Fase 3: Pruebas de la SPA del Cliente (`spa_client`)

El objetivo es validar la experiencia completa del cliente final, desde el registro hasta el agendamiento.

| # | Caso de Prueba | Pasos a Seguir | Resultado Esperado | Estado |
|:-:|:---|:---:|:---|:---:|
| 3.1 | **Auto-Registro de Cliente (OTP)** | 1. Ir a `spa_client.php`.<br>2. Ingresar un número de teléfono nuevo.<br>3. El sistema indica que se enviará un código.<br>4. Ingresar el código recibido y llenar el formulario de registro.<br>5. Enviar. | 1. Se recibe el código por email/SMS.<br>2. Se valida el código y se muestra el formulario.<br>3. El cliente se crea en la BD.<br>4. Se recibe el correo de bienvenida. | ☐ |
| 3.2 | **Login de Cliente (OTP)** | 1. Iniciar sesión con el número de teléfono del cliente.<br>2. Recibir e ingresar el código de verificación. | 1. El login es exitoso.<br>2. El dashboard del cliente se muestra correctamente. | ☐ |
| 3.3 | **Flujo de Agendamiento** | 1. Hacer clic en "Agendar Cita".<br>2. Seleccionar un servicio de la lista.<br>3. Seleccionar una fecha en el calendario.<br>4. Verificar que se muestren los horarios disponibles.<br>5. Seleccionar un horario.<br>6. Confirmar la reserva. | 1. El flujo de 3 pasos funciona sin errores.<br>2. La cita se crea en la base de datos.<br>3. El cliente y el propietario reciben el correo de notificación. | ☐ |
| 3.4 | **Gestión de Cita (Cliente)** | 1. En el dashboard del cliente, ver la cita recién creada.<br>2. Hacer clic en "Confirmar Asistencia".<br>3. Verificar que el estado cambie a "Confirmada".<br>4. Hacer clic en "Cancelar Cita". | 1. Las acciones se completan sin errores.<br>2. El estado de la cita se actualiza en la BD y se refleja en la `spa_owner`. | ☐ |
| 3.5 | **Historial y Perfil** | 1. Ir a "Mi Historial" y verificar que aparezcan las citas.<br>2. Ir a "Mi Perfil", modificar el nombre y guardar.<br>3. Verificar que el cambio se refleje en la `spa_owner`. | 1. El historial se muestra correctamente.<br>2. Los datos del perfil se actualizan en la base de datos. | ☐ |
| 3.6 | **Verificación de UI Responsiva** | 1. Abrir `spa_client.php` en una vista de móvil.<br>2. Navegar entre secciones. | 1. El menú de navegación principal se colapsa.<br>2. Aparece un Menú Flotante (FAB) en la esquina inferior derecha.<br>3. El FAB permite acceder a las vistas principales. | ✅ |

---

## 6. Conclusión

Una vez que todos los casos de prueba de las tres fases se completen con éxito (marcando el estado como ✅), el proyecto se considerará validado y listo para su despliegue en un entorno de producción.