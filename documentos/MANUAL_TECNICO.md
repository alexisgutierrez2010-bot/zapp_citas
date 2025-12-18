# Manual Técnico y Guía de Despliegue - ZApp Citas

**Versión:** 1.8
**Última Actualización:** 14 de Diciembre de 2025

---

## 1. Introducción

Este documento sirve como la referencia técnica central para el ecosistema de aplicaciones "ZApp Citas". Su objetivo es proporcionar una visión clara y detallada de la arquitectura del software, la estructura de la base de datos, la funcionalidad de cada componente y las interacciones entre ellos.

El sistema ZApp Citas está compuesto por tres aplicaciones principales que trabajan de forma coordinada:

1.  **Panel de Administración (zapp_citas):** Es el núcleo del sistema, desarrollado en PHP tradicional. Permite la gestión completa de negocios, usuarios, clientes, servicios, citas y configuraciones globales.
2.  **SPA del Propietario (spa_owner):** Una Single-Page Application (HTML + JavaScript) diseñada para que los dueños de negocios gestionen su agenda, clientes y servicios de forma rápida y moderna.
3.  **SPA del Cliente (spa_client):** Una Single-Page Application (HTML + JavaScript) que permite a los clientes finales registrarse, ver su historial, agendar citas y gestionar su perfil.

El sistema utiliza una arquitectura mixta:
- El Panel de Administración sigue un modelo de carga de páginas PHP tradicional (ej. `clientes_lista.php` -> `clientes_editar.php`).
- Las SPAs (`spa_owner.php` y `spa_client.php`) son interfaces estáticas que consumen datos de un conjunto de scripts PHP que actúan como una API RESTful (ej. `api_owner_horario_disponible.php`).

---

## 2. Arquitectura Detallada de Aplicaciones

### 2.1. Panel de Administración (zapp_citas)

*   **Propósito y Tecnología:**
    Es el centro de control total del sistema. Está construido con PHP tradicional, donde cada acción del usuario resulta en una nueva solicitud al servidor, que renderiza y devuelve una página HTML completa.

*   **Flujo de Seguridad y Sesión:**
    1.  El acceso se inicia en `sesion_iniciar.php`.
    2.  Las credenciales se envían a `procesar_login.php`, que verifica el usuario y la contraseña contra la tabla `j100_usuarios`.
    3.  Si el login es exitoso, se establecen variables de sesión (`$_SESSION['loggedin'] = true`, `$_SESSION['id_usuario']`, etc.).
    4.  Cada página protegida (ej. `dashboard.php`) incluye `require_once 'auth_check.php';` al principio.
    5.  `auth_check.php` es el "guardián": inicia la sesión con `session_start()` y verifica si `$_SESSION['loggedin']` es `true`. Si no, redirige a `sesion_iniciar.php`. También gestiona el timeout por inactividad.

*   **Variables Clave de Sesión:**
    - `$_SESSION['loggedin']`: Booleano que indica si el usuario está autenticado.
    - `$_SESSION['id_usuario']`: ID del usuario logueado.
    - `$_SESSION['id_negocio']`: ID del negocio que el usuario está gestionando.
    - `$_SESSION['rol']`: Rol del usuario ('Master', 'Propietario').
    - `$_SESSION['last_activity']`: Timestamp de la última interacción para controlar el timeout.

### 2.2. SPA del Propietario (spa_owner)

*   **Propósito y Tecnología:**
    Interfaz moderna y rápida para la gestión diaria del negocio. Es una Single-Page Application (SPA), lo que significa que la página principal (`spa_owner.php`) se carga una sola vez. Toda la interacción posterior se realiza mediante JavaScript (`app_owner.js`), que se comunica con el servidor a través de APIs PHP sin recargar la página.

*   **Flujo de Seguridad y Sesión:**
    1.  La SPA carga `renderLoginView()` que llama a la API `api_owner_login.php` para obtener un CAPTCHA.
    2.  El usuario envía sus credenciales a `api_owner_login.php` (vía POST). La API valida al usuario y, si tiene éxito, crea una sesión de PHP en el servidor con variables específicas (`$_SESSION['owner_loggedin']`).
    3.  Cada acción posterior que requiere datos hace una llamada `fetch` a una API protegida (ej. `api_owner_calendario_eventos.php`).
    4.  Cada API protegida incluye `require_once 'api_owner_session_check.php';` al principio. Este guardián verifica que `$_SESSION['owner_loggedin']` sea `true`. Si no, la API devuelve un error 401 (No Autorizado).
*   **Variables Clave de Sesión:**
    - `$_SESSION['owner_loggedin']`: Booleano.
    - `$_SESSION['owner_id_usuario']`: ID del usuario propietario.
    - `$_SESSION['owner_id_negocio']`: ID del negocio que gestiona.
    - `$_SESSION['owner_last_activity']`: Timestamp para el timeout.

### 2.3. SPA del Cliente (spa_client)

*   **Propósito y Tecnología:**
    Portal de autoservicio para el cliente final. Al igual que la SPA del propietario, es una SPA (`spa_client.php` + `app_client.js`) que se comunica con el servidor mediante APIs (`api_cliente_*.php`).

*   **Flujo de Seguridad y Sesión:**
    El flujo es similar al de la SPA del propietario, pero con sus propias APIs y variables de sesión.
    1.  El login se realiza contra `api_cliente_login.php` usando solo el número de teléfono y un CAPTCHA.
    2.  Si el cliente no existe, la API devuelve un error 404, y el JavaScript le ofrece registrarse.
    3.  Si el login es exitoso, se crea una sesión con `$_SESSION['client_loggedin'] = true`.
    4.  Las APIs protegidas usan el guardián `api_cliente_session_check.php`.

*   **Variables Clave de Sesión:**
    - `$_SESSION['client_loggedin']`: Booleano.
    - `$_SESSION['client_id_cliente']`: ID del cliente logueado.
    - `$_SESSION['client_id_negocio']`: ID del negocio al que pertenece el cliente.
    - `$_SESSION['client_last_activity']`: Timestamp para el timeout.

---

## 2.5. Flujo de Comunicaciones por Email

Para garantizar una experiencia de usuario profesional y flexible, el sistema implementa un flujo de notificaciones por correo electrónico. La funcionalidad se ha centralizado en la **SPA del Propietario (`spa_owner`)** para dar control total al dueño del negocio, mientras que los envíos automáticos desde el panel de administración (`zapp_citas`) han sido suspendidos para evitar redundancias.

A continuación se detalla el comportamiento homologado del sistema:

| Evento de la Cita | Aplicación | Comportamiento del Email | Notas |
| :--- | :--- | :--- | :--- |
| **Crear Cita** | `spa_owner` | ✅ **Automático (Opcional):** Al crear la cita, el propietario puede marcar una casilla para notificar al cliente. Si se marca, envía correo de "Nueva Cita" con detalles y archivo `.ics`. | El envío desde `zapp_citas` está suspendido. |
| **Modificar Cita** | `spa_owner` | 🟡 **Manual:** Después de modificar, el propietario debe usar la acción "📧 Enviar Email" para notificar los cambios. | El envío desde `zapp_citas` está suspendido. |
| **Confirmar Cita** | `spa_owner` | 🟡 **Manual:** El propietario debe usar la acción "📧 Enviar Email" y seleccionar la plantilla "Cita Confirmada". | |
| **Cancelar Cita** | `spa_owner` | 🟡 **Manual:** El propietario debe usar la acción "📧 Enviar Email" y seleccionar la plantilla "Cita Cancelada". | |
| **Completar Cita** | `spa_owner` | 🟡 **Manual:** El propietario debe usar la acción "📧 Enviar Email" y seleccionar la plantilla "Cita Completada". | |
| **Eliminar Cita** | `spa_owner` | ❌ **Sin Email:** La acción de eliminar es inmediata y no envía notificación. Se recomienda cancelar primero. | El envío desde `zapp_citas` está suspendido. |

---

## 2.6. Sistema de Auditoría
El sistema cuenta con un robusto módulo de auditoría que registra todas las operaciones críticas realizadas en las tres aplicaciones. El objetivo es mantener una trazabilidad completa de las acciones que modifican datos, mejorando la seguridad y el control. Todos los eventos se almacenan en la tabla `j099_auditorias`.

La siguiente tabla de diagnóstico detalla la cobertura de auditoría en todo el sistema:

| Evento | Aplicación | Archivo(s) Involucrados | ¿Auditado? |
| :--- | :--- | :--- | :---: |
| **Login / Logout / Timeout** | `zapp_citas` | `procesar_login.php`, `logout.php`, `auth_check.php` | ✅ **Sí** |
| | `spa_owner` | `api_owner_login.php`, `api_owner_logout.php` | ✅ **Sí** |
| | `spa_client` | `api_cliente_login.php` | ✅ **Sí** |
| **Recuperar Contraseña** | `zapp_citas` | `procesar_olvide_clave.php` | ✅ **Sí** |
| | `spa_owner` | `api_owner_recuperar_clave.php` | ✅ **Sí** |
| | | | |
| **Crear Negocio** | `zapp_citas` | `negocios_crear.php` | ✅ **Sí** |
| **Actualizar Negocio** | `zapp_citas` | `negocios_actualizar.php` | ✅ **Sí** |
| | `spa_owner` | `api_owner_negocio_update.php` | ✅ **Sí** |
| **Desactivar/Eliminar Negocio** | `zapp_citas` | `negocios_eliminar.php` | ✅ **Sí** |
| | | | |
| **Crear Usuario** | `zapp_citas` | `usuarios_crear.php` | ✅ **Sí** |
| **Actualizar Usuario** | `zapp_citas` | `usuarios_actualizar.php` | ✅ **Sí** |
| **Eliminar Usuario** | `zapp_citas` | `usuarios_eliminar.php` | ✅ **Sí** |
| **Actualizar Perfil (Propio)** | `zapp_citas` | `perfil_actualizar.php` | ✅ **Sí** |
| | `spa_owner` | `api_owner_perfil_actualizar.php` | ✅ **Sí** |
| | | | |
| **Crear Cliente** | `zapp_citas` | `clientes_crear.php` | ✅ **Sí** |
| | `spa_owner` | `api_owner_cliente_crear.php` | ✅ **Sí** |
| | `spa_client` | `api_cliente_registro.php` | ✅ **Sí** |
| **Actualizar Cliente** | `zapp_citas` | `clientes_actualizar.php` | ✅ **Sí** |
| | `spa_owner` | `api_owner_cliente_actualizar.php` | ✅ **Sí** |
| | `spa_client` | `api_cliente_perfil.php` | ✅ **Sí** |
| **Desactivar Cliente** | `zapp_citas` | `clientes_eliminar.php` | ✅ **Sí** |
| | `spa_owner` | `api_owner_cliente_eliminar.php` | ✅ **Sí** |
| | | | |
| **Crear Servicio** | `zapp_citas` | `servicios_crear.php` | ✅ **Sí** |
| | `spa_owner` | `api_owner_servicio_crear.php` | ✅ **Sí** |
| **Actualizar Servicio** | `zapp_citas` | `servicios_actualizar.php` | ✅ **Sí** |
| | `spa_owner` | `api_owner_servicio_actualizar.php` | ✅ **Sí** |
| **Desactivar Servicio** | `zapp_citas` | `servicios_eliminar.php` | ✅ **Sí** |
| | `spa_owner` | `api_owner_servicio_eliminar.php` | ✅ **Sí** |
| | | | |
| **Crear Cita** | `zapp_citas` | `citas_crear.php` | ✅ **Sí** |
| | `spa_owner` | `api_owner_cita_crear.php` | ✅ **Sí** |
| **Actualizar Cita** | `zapp_citas` | `citas_actualizar.php` | ✅ **Sí** |
| **Actualizar Estado Cita** | `zapp_citas` | `citas_actualizar_estado.php` | ✅ **Sí** |
| **Eliminar Cita** | `zapp_citas` | `citas_eliminar.php` | ✅ **Sí** |
| **Eliminar Cita (Físico)** | `spa_owner` | `api_owner_cita_eliminar_fisico.php` | ✅ **Sí** |
| **Cerrar Citas Vencidas** | `spa_owner` | `api_owner_citas_cerrar_vencidas.php` | ✅ **Sí** |
| | | | |
| **CRUD de Categorías** | `zapp_citas` | `categorias_*.php` | ✅ **Sí** |
| | | | |
| **Manejo de Documentos** | `zapp_citas` | `documento_subir.php`, `documento_editar.php`, `enviar_resumen.php` | ✅ **Sí** |

**Conclusión:** Todas las operaciones que implican creación, modificación o eliminación de datos (CRUD) están siendo auditadas correctamente en todas las interfaces.

---

## 3. Listado de Programas y Funcionalidad

Esta sección detalla cada programa y su propósito dentro del sistema.

#### 3.1. Archivos Centrales y de Configuración
*   `config.php`: Define las credenciales de conexión a la base de datos y las constantes de configuración de SMTP.
*   `audit_log.php`: Contiene la función `registrar_auditoria()` para registrar eventos clave.
*   `index.php`: Portal de bienvenida principal que redirige a las tres aplicaciones.
*   `footer.php` / `navbar.php`: Componentes de UI para el panel de administración.
*   `get_image.php`: Script seguro para servir la imagen de fondo de un negocio.
*   `check_composer.php`: Script de diagnóstico para verificar la instalación de dependencias de Composer.
*   `documento_ver.php`: Visor que convierte archivos Markdown (.md) a HTML para una lectura fácil.

#### 3.2. Autenticación y Sesión
*   `auth_check.php`: **Guardián de sesión** para el panel de administración.
*   `api_owner_session_check.php`: Guardián de seguridad para la API de la SPA del Propietario.
*   `api_cliente_session_check.php`: Guardián de seguridad para la API de la SPA del Cliente.
*   `sesion_iniciar.php` / `procesar_login.php`: Gestionan el login del panel de administración.
*   `logout.php`: Cierra la sesión del panel de administración.
*   `olvide_clave.php` / `procesar_olvide_clave.php`: Gestionan la recuperación de contraseñas.

#### 3.3. Panel de Administración (CRUDs)
*   **Negocios:** `negocios_configuracion.php`, `negocios_actualizar.php`, `crear_negocio.php`, `negocios_crear.php`, `negocios_eliminar.php`.
*   **Usuarios:** `usuarios_lista.php`, `usuarios_crear.php`, `usuarios_editar.php`, `usuarios_actualizar.php`, `usuarios_eliminar.php`.
*   **Clientes:** `clientes_lista.php`, `clientes_crear.php`, `clientes_editar.php`, `clientes_actualizar.php`, `clientes_eliminar.php`.
*   **Servicios:** `servicios_lista.php`, `servicios_crear.php`, `servicios_editar.php`, `servicios_actualizar.php`, `servicios_eliminar.php`.
*   **Categorías:** `categorias_lista.php`, `categoria_crear.php`, `categorias_procesar_crear.php`, `categoria_editar.php`, `categoria_actualizar.php`, `categoria_eliminar.php`.
*   **Citas:** `citas_lista.php`, `citas_crear.php`, `citas_editar.php`, `citas_actualizar.php`, `citas_eliminar.php`, `citas_actualizar_estado.php`, `calendario_ver.php`.
*   **Documentos:** `seleccionar_resumen.php`, `documento_editar.php`, `documento_subir.php`, `enviar_resumen.php`.
*   **Localizaciones:** `paises_lista.php` (y su CRUD), `estados_lista.php` (y su CRUD).

#### 3.4. API para SPA del Propietario (spa_owner)

*   **Autenticación y Sesión:**
    *   `api_owner_login.php`: Valida las credenciales del propietario y crea su sesión.
    *   `api_owner_logout.php`: Cierra la sesión del propietario.
    *   `api_owner_recuperar_clave.php`: Gestiona la solicitud de recuperación de contraseña.
*   **Citas y Calendario:**
    *   `api_owner_dashboard_data.php`: **(NUEVO)** Devuelve los datos agregados para los 4 gráficos del dashboard.
    *   `api_owner_citas.php`: Devuelve la lista de citas para una fecha específica (vista de agenda).
    *   `api_owner_calendario_eventos.php`: Devuelve las citas en formato de evento para FullCalendar.
    *   `api_owner_horario_disponible.php`: Devuelve los slots de tiempo (libres y ocupados) para un día.
    *   `api_owner_cita_detalle.php`: Obtiene los detalles completos de una cita.
    *   `api_owner_cita_crear.php`: Crea una nueva cita (de servicio o reunión).
    *   `api_owner_cita_actualizar.php`: Actualiza los datos o el estado de una cita.
    *   `api_owner_cita_eliminar_fisico.php`: **(NUEVO)** Elimina permanentemente una cita y sus invitados.
    *   `api_owner_cita_eliminar.php`: Cancela una cita (borrado lógico).
    *   `api_owner_citas_cerrar_vencidas.php`: Marca como 'Vencidas' las citas pasadas.
*   **Gestión (CRUDs):**
    *   `api_owner_clientes.php`, `_detalle.php`, `_crear.php`, `_actualizar.php`, `_eliminar.php`: CRUD completo para Clientes.
    *   `api_owner_servicios.php`, `_crear.php`, `_actualizar.php`, `_eliminar.php`: CRUD completo para Servicios.
*   **Configuración:**
    *   `api_owner_negocio_get.php`, `_update.php`: Obtiene y actualiza la configuración del negocio.
    *   `api_owner_perfil_detalle.php`, `_actualizar.php`: Obtiene y actualiza el perfil del usuario propietario.
*   **Datos Auxiliares:**
    *   `api_get_translations.php`: Devuelve el diccionario de traducciones (ES/EN) para la interfaz.
    *   `api_paises.php`, `api_estados.php`: Devuelven las listas de localizaciones para los formularios.


#### 3.5. API para SPA del Cliente (spa_client)
*   `api_cliente_login.php`: Valida el número de celular del cliente y crea su sesión.
*   `api_cliente_logout.php`: Cierra la sesión del cliente.
*   `api_cliente_registro.php`: Registra un nuevo cliente en la base de datos.
*   `api_cliente_perfil.php`: Obtiene y actualiza los datos del perfil del cliente.
*   `api_cliente_citas.php`: Devuelve la cita más reciente del cliente.
*   `api_cliente_historial.php`: Devuelve el historial completo de citas del cliente.
*   `api_negocios_lista_publica.php`: Devuelve la lista de negocios activos.
*   `api_servicios_publicos.php`: Devuelve los servicios de un negocio específico.

#### 3.6. Estructura de Archivos JavaScript (SPAs)

*   **SPA del Propietario (`/js/owner_modules/`)**
    *   `app_owner.js` (en raíz): Orquestador principal de la SPA.
    *   `auth.js`: Módulo de autenticación.
    *   `agenda.js`: Módulo para la vista de agenda diaria.
    *   `calendario.js`: Módulo para la vista de calendario mensual.
    *   `citas.js`: Módulo para los formularios de creación/edición de citas.
    *   `clientes.js`: Módulo para el CRUD de clientes.
    *   `dashboard.js`: Módulo para los gráficos de resumen de gestión.
    *   `disponibilidad.js`: Módulo para la vista de slots de tiempo.
    *   `negocio.js`: Módulo para la configuración del negocio.
    *   `perfil.js`: Módulo para el perfil del propietario.
    *   `servicios.js`: Módulo para el CRUD de servicios.
    *   `ui.js`: Módulo para componentes de la interfaz de usuario (navbar, reloj).

*   **SPA del Cliente (`/js/client_modules/`)**
    *   `app_client.js` (en raíz): Orquestador principal de la SPA.
    *   `auth.js`: Módulo de autenticación y registro de clientes.
    *   `booking.js`: Módulo para el proceso de agendamiento de citas.
    *   `dashboard.js`: Módulo para la pantalla principal del cliente.
    *   `history.js`: Módulo para ver el historial de citas.
    *   `profile.js`: Módulo para que el cliente edite su perfil.
    *   `ui.js`: Módulo para componentes de la interfaz de usuario del cliente.



---

## 4. Diagrama de Flujo de Aplicaciones

Este diagrama ilustra el flujo principal de interacción entre los componentes.

```

                                     +-----------------------------------------+
                                     |          index.php (Portal)             |
                                     +-----------------------------------------+
                                                        |
                 +--------------------------------------+--------------------------------------+
                 |                                      |                                      |
                 V                                      V                                      V
+-------------------------------------+ +-------------------------------------+ +-------------------------------------+
|   Panel Administración (zapp_citas)   |      SPA Propietario (spa_owner)      |         SPA Cliente (spa_client)        |
|           (PHP Tradicional)           |            (JavaScript)             |              (JavaScript)             |
+=====================================+=====================================+=====================================+
|                                                                                                                     |
|                                             NAVEGADOR DEL USUARIO (FRONTEND)                                        |
|                                                                                                                     |
+-------------------------------------+-------------------------------------+-------------------------------------+
| 1. El usuario navega a páginas como | 1. Se carga `spa_owner.php` (HTML).   | 1. Se carga `spa_client.php` (HTML).  |
|    `sesion_iniciar.php` o           |    `app_owner.js` toma el control.    |    `app_client.js` toma el control.   |
|    `clientes_lista.php`.            |                                     |                                     |
|                                     | 2. JS realiza una llamada `fetch()` | 2. JS realiza una llamada `fetch()` |
| 2. El navegador envía una solicitud |    a la API para autenticarse o     |    a la API para autenticarse o     |
|    HTTP al servidor.                |    pedir datos.                     |    pedir datos.                     |
|                                     |    (Ej: `api_owner_citas.php`)      |    (Ej: `api_cliente_citas.php`)    |
+-------------------------------------+-------------------------------------+-------------------------------------+
                 |                                      |                                      |
                 |                                      |                                      |
                 V                                      V                                      V
+-------------------------------------+-------------------------------------+-------------------------------------+
|                                                                                                                     |
|                                                SERVIDOR (BACKEND)                                                   |
|                                                                                                                     |
+-------------------------------------+-------------------------------------+-------------------------------------+
| 3. El servidor recibe la solicitud. | 3. El servidor recibe la llamada a  | 3. El servidor recibe la llamada a  |
|    El script PHP (ej. `clientes_   |    la API (ej. `api_owner_citas.php`).|    la API (ej. `api_cliente_citas.php`).|
|    lista.php`) se ejecuta.          |                                     |                                     |
|                                     | 4. El script de la API (PHP) se     | 4. El script de la API (PHP) se     |
| 4. El script PHP se conecta         |    ejecuta y se conecta a la BD.    |    ejecuta y se conecta a la BD.    |
|    directamente a la BD.            |                                     |                                     |
+-------------------------------------+-------------------------------------+-------------------------------------+
                 |                                      |                                      |
                 +-----------------------+--------------+-----------------------+--------------+
                                         |                                      |
                                         V                                      V
                               +------------------+                   +------------------+
                               |  Base de Datos   |                   |  Base de Datos   |
                               |     (MySQL)      |                   |     (MySQL)      |
                               +------------------+                   +------------------+
                                         ^                                      ^
                                         |                                      |
                                         +--------------------------------------+

```

---

## 5. Estructura Detallada de la Base de Datos

### j100_usuarios
*   `id_usuario` (PK, AI)
*   `id_negocio` (FK a j102_negocios)
*   `nombre_usuario` (UNIQUE)
*   `correo_electronico` (UNIQUE)
*   `password_hash`
*   `rol` ('Master', 'Propietario')
*   `activo` (TINYINT, 1=Activo, 0=Inactivo)
*   `fecha_creacion`

### j099_auditorias
*   `id_audit` (PK, AI)
*   `id_usuario` (FK a j100_usuarios, NULLABLE)
*   `id_negocio` (FK a j102_negocios, NULLABLE)
*   `accion`
*   `descripcion`
*   `ip_address`
*   `fecha_hora`

### j102_negocios
*   `id_negocio` (PK, AI)
*   `id_categoria_negocio` (FK a j103_categorias, NULLABLE)
*   `nombre_negocio`
*   `telefono`
*   `email`
*   `direccion1`, `direccion2`, `ciudad`, `zip_code`
*   `id_pais` (FK a j110_paises)
*   `id_estado` (FK a j111_estados)
*   `dias_trabajo` (ej. '1,2,3,4,5')
*   `hora_inicio`, `hora_cierre`
*   `intervalo_minutos`
*   `activo` (1=Activo, 2=Suspendido, 3=Eliminado)
*   `dias_prueba`
*   `fecha_registro`, `fecha_habilitacion`, `fecha_desactivacion`
*   `background_image_data`, `background_image_type`

### j103_categorias
*   `id_categoria` (PK, AI)
*   `nombre_categoria` (UNIQUE)
*   `descripcion`
*   `activo` (TINYINT)

### j104_servicios
*   `id_servicio` (PK, AI)
*   `id_negocio` (FK a j102_negocios)
*   `nombre_servicio`
*   `duracion_valor`
*   `duracion_unidad` ('Minutos', 'Horas', 'Dias')
*   `precio` (DECIMAL, NULLABLE)
*   `activo` (TINYINT)

### j106_clientes
*   `id_cliente` (PK, AI)
*   `id_negocio` (FK a j102_negocios)
*   `nombre_completo`
*   `numero_celular`
*   `correo_electronico`
*   `activo` (TINYINT, 1=Activo, 0=Inactivo)
*   `notas_adicionales` (TEXT, NULLABLE)

### j108_citas
*   `id_cita` (PK, AI)
*   `id_negocio` (FK a j102_negocios)
*   `tipo_cita` ('Servicio', 'Reunion')
*   `id_cliente` (FK a j106_clientes)
*   `id_servicio` (FK a j104_servicios, NULLABLE)
*   `fecha_hora_inicio`, `fecha_hora_fin`
*   `estado_cita` ('Pendiente', 'Confirmada', 'Completada', 'Cancelada', 'No Asistió', 'Vencida')
*   `descripcion_trabajo`

### j109_invitados_cita
*   `id_invitado` (PK, AI)
*   `id_cita` (FK a j108_citas)
*   `nombre_invitado`
*   `email_invitado`
*   `telefono_invitado` (VARCHAR, NULLABLE)

---

## 6. Guía de Despliegue

### 6.1. Requisitos del Servidor

-   **Servidor Web:** Apache o Nginx.
-   **PHP:** Versión 8.0 o superior.
-   **Gestor de Paquetes:** Acceso a **Composer** para instalar dependencias.
-   **Extensiones de PHP:** `mysqli`, `json`, `mbstring`, `openssl`.
-   **Base de Datos:** MySQL o MariaDB.

### 6.2. Preparación de la Base de Datos

1.  **Exportar la Base de Datos Local:**
    -   Abre phpMyAdmin en tu XAMPP.
    -   Selecciona tu base de datos.
    -   Ve a la pestaña "Exportar", elige el método "Rápido" y formato "SQL".
    -   Haz clic en "Exportar" para descargar el archivo `.sql`.

2.  **Crear e Importar la Base de Datos en el Hosting:**
    -   En el panel de control de tu hosting (cPanel, hPanel, etc.), crea una **nueva base de datos** y un **nuevo usuario**.
    -   Asigna todos los privilegios al usuario sobre la base de datos.
    -   **Anota cuidadosamente:** nombre de la base de datos, nombre de usuario y la contraseña.
    -   Abre phpMyAdmin en tu hosting, selecciona la base de datos recién creada y, en la pestaña "Importar", sube el archivo `.sql` que exportaste.

### 6.3. Configuración y Subida de Archivos

1.  **Configurar `config.php`:**
    -   Este es el paso más crítico. Antes de subir los archivos, edita `config.php` en tu computadora con las credenciales de la base de datos de tu hosting.
    ```php
    // en config.php
    $servername = "localhost"; // Generalmente es 'localhost', pero confírmalo en tu hosting.
    $username   = "NOMBRE_DE_USUARIO_DEL_HOSTING";
    $password   = "CONTRASEÑA_DEL_HOSTING";
    $dbname     = "NOMBRE_DE_LA_BD_DEL_HOSTING";
    ```
    -   **Importante:** Actualiza también las credenciales `SMTP_*` en este mismo archivo para que el envío de correos funcione en producción.

2.  **Subir Archivos al Servidor:**
    -   Conéctate a tu hosting usando un cliente FTP (como FileZilla) o el Administrador de Archivos del panel de control.
    -   Navega al directorio raíz de tu sitio web (normalmente `public_html`).
    -   Sube **todos los archivos y carpetas** del proyecto `zapp_citas` a este directorio.
    -   **Excepción:** **NO subas la carpeta `vendor`**. La instalaremos directamente en el servidor.
    -   **Nota sobre `spa_owner.php`**: Este archivo ahora incluye un parámetro de versión en la carga de `app_owner.js` (ej. `?v=1.0.1`) para mitigar problemas de caché en producción. Se recomienda incrementar este número en cada despliegue que modifique los archivos JavaScript.

3.  **Instalar Dependencias con Composer:**
    -   Una vez subidos los archivos, necesitas instalar las bibliotecas de PHP (PHPMailer, Parsedown, etc.).
    -   Accede a la terminal de tu hosting.
    -   Navega al directorio donde subiste los archivos (ej. `cd public_html`).
    -   Ejecuta el siguiente comando para que Composer descargue e instale todo lo necesario:
    ```bash
    composer install --no-dev --optimize-autoloader
    ```
    -   **Plan B (Si no tienes terminal):** Si tu plan de hosting no incluye acceso a la terminal, ejecuta `composer install --no-dev --optimize-autoloader` en tu computadora local (dentro de `c:\xampp\htdocs\zapp_citas\`). Luego, sube la carpeta `vendor` generada a tu servidor usando FTP (FileZilla).

---

## 7. Verificación Final

Una vez completados los pasos, abre tu dominio en el navegador y realiza pruebas exhaustivas:
-   Inicia sesión en el Panel de Administración.
-   Inicia sesión en la SPA del Propietario y del Cliente.
-   Navega por todas las secciones y verifica que no haya errores.
-   Realiza una acción que interactúe con la base de datos (ej. editar un cliente) para confirmar que la conexión es correcta.
-   Verifica que el envío de correos y la subida de documentos funcionen como se espera.

---

## 8. Herramientas de Diagnóstico

### 8.1. Verificador de PHPMailer (`check_phpmailer.php`)

Este script es una herramienta de diagnóstico crucial para resolver problemas relacionados con el envío de correos electrónicos.

*   **Propósito:** Verificar si la librería `PHPMailer` está correctamente instalada a través de Composer y si es accesible para PHP. Esto permite aislar problemas de envío de correo: si este script funciona, el problema está en la lógica de la API que envía el correo; si falla, el problema está en la instalación de las dependencias.

*   **Ubicación:** `c:\xampp\htdocs\zapp_citas\check_phpmailer.php`

*   **Modo de Uso:**
    1.  Navegar a la URL: `http://localhost/zapp_citas/check_phpmailer.php`
    2.  Observar el resultado.

*   **Resultados Posibles:**
    *   **`¡VERIFICACIÓN EXITOSA!` (Verde):** Significa que Composer y PHPMailer están instalados correctamente. Cualquier error de envío de correo se debe a la configuración SMTP (host, usuario, contraseña) o a la lógica del script que intenta enviar el correo.
    *   **`¡VERIFICACIÓN FALLIDA!` (Rojo):** Indica un problema con la instalación de Composer.
        *   **Causa Común:** El archivo `composer.lock` no está sincronizado con `composer.json`.
        *   **Solución:**
            1.  Abrir una terminal en la carpeta del proyecto (`c:\xampp\htdocs\zapp_citas\`).
            2.  Ejecutar el comando: `composer update`.
            3.  Volver a ejecutar la prueba en el navegador.

### 8.2. Verificador de Composer (`check_composer.php`)

Este script es una herramienta de diagnóstico más completa para resolver problemas con las dependencias del proyecto.

*   **Propósito:** Verificar que el archivo `composer.json` es válido, que la carpeta `vendor` y el `autoload.php` existen, y que las clases principales (como `PHPMailer` y `Parsedown`) son accesibles para PHP.

*   **Ubicación:** `c:\xampp\htdocs\zapp_citas\check_composer.php`

*   **Modo de Uso:**
    1.  Navegar a la URL: `http://localhost/zapp_citas/check_composer.php` (no requiere iniciar sesión).
    2.  Observar el resultado de los 3 pasos de verificación.

*   **Resultados Posibles:**
    *   **Todo en Verde (✅):** La configuración de Composer es correcta.
    *   **Algún error en Rojo (❌):** El script indicará la causa exacta (ej. `composer.json` inválido, `vendor/` no encontrado) y la solución recomendada.


 ## 9.  Internacionalización (i18n) - (Implementado en Diciembre 2025)

Se implementó una estrategia global para permitir que la aplicación funcione fluidamente en español (`es`) e inglés (`en`).

#### ESTRATEGIA APLICADA:

*   **Archivo Central de Traducciones**: `languages.php` contiene un array PHP `$translations` con todas las cadenas de texto para 'es' y 'en'. Esto centraliza y facilita la gestión de los textos.
*   **Función de Ayuda (Helper)**: Se creó una función global `__()` que toma una clave como argumento (ej. `__('login_button')`) y devuelve la cadena traducida según el idioma activo.
*   **Detección y Persistencia del Idioma**: El sistema detecta el idioma con la siguiente prioridad:
    1.  Parámetro en URL (`?lang=en`)
    2.  Cookie de idioma guardada en el navegador.
    3.  Variable de sesión.
    El idioma seleccionado se guarda en la sesión y en una cookie para futuras visitas.
*   **Mensajes de Operación Traducibles**: Se estandarizó el uso de un parámetro `message_key` en las URL de redirección para mostrar mensajes de éxito o error (ej. "Registro creado con éxito") de forma traducible.
*   **Cobertura Completa**: La estrategia se aplicó a todos los componentes de la interfaz de usuario (menús, formularios, tablas), mensajes del servidor, y al contenido de los correos electrónicos transaccionales (confirmación de citas, recuperación de clave, etc.).

#### TABLA DE CHEQUEO DE ARCHIVOS MODIFICADOS PARA I18N:

La implementación de i18n abarcó todos los archivos que presentan texto al usuario en el panel de administración (`zapp_citas`). Esto incluye:

*   **Vistas Principales:** `dashboard.php`, `calendario_ver.php`, etc.
*   **Formularios y Listas CRUD:** Todos los archivos `*_lista.php`, `*_editar.php`, `*_crear.php` para todas las entidades (clientes, servicios, usuarios, etc.).
*   **Componentes de UI:** `navbar.php`, `footer.php`.
*   **Páginas de Autenticación:** `sesion_iniciar.php`, `olvide_clave.php`.
*   **Scripts de Proceso:** Todos los scripts que generan mensajes de feedback para el usuario.
*   **Plantillas de Email:** El contenido de los correos electrónicos se genera utilizando las traducciones para asegurar una comunicación consistente.

---
***FIN DEL DOCUMENTO***

Author: Alexis Gutierrez y Gemini Code Assist (Update: 12/14/2025)
