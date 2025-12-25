<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Reestructuración del menú y añadido selector de idioma.
// La página que incluye este archivo debe proporcionar la conexión $conn y las variables de sesión.
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="dashboard.php" style="padding-top: 0; padding-bottom: 0;">
        <img src="logo_zapp_citas.png" alt="Logo ZApp Citas" style="height: 1.5em; margin-right: 10px; filter: drop-shadow(1px 1px 2px rgba(0,0,0,0.5));">
        <span style="text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">ZApp Citas</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav">
        <?php if (isset($rol_session)): ?>
            <li class="nav-item">
              <a class="nav-link" href="negocios_configuracion.php">Negocios</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="servicios_lista.php">Servicios</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="clientes_lista.php">Clientes</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="citas_lista.php">Citas</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="calendario_ver.php">Calendario</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="resenas_lista.php">Reseñas</a>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownConfig" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Configuración
              </a>
              <ul class="dropdown-menu" aria-labelledby="navbarDropdownConfig">
                <li><a class="dropdown-item" href="usuarios_lista.php">Usuarios</a></li>
                <li><a class="dropdown-item" href="categorias_lista.php">Categorías</a></li>
                <li><a class="dropdown-item" href="paises_lista.php">Localizaciones</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="auditoria_reporte.php">Auditoría</a></li>
                <li><a class="dropdown-item" href="seleccionar_resumen.php">Documentos</a></li>
              </ul>
            </li>
      <?php endif; ?>
      </ul>
      <!-- Opciones de usuario a la derecha -->
      <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item">
                <a class="nav-link" href="ayuda_zapp_citas.php" target="_blank">❓ Ayuda</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="perfil_ver.php">👤 <?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?> (Mi Perfil)</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">Salir</a>
            </li>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</nav>