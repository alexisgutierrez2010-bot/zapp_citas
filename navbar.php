<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-24-2025).
// Obtener la hora actual del servidor de la base de datos
// La página que incluye este archivo debe proporcionar la conexión $conn

?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">ZApp Citas</a>
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
              <a class="nav-link" href="usuarios_lista.php">Usuarios</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="categorias_lista.php">Categorías</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="paises_lista.php">Localizaciones</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="auditoria_reporte.php">Auditoría</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="seleccionar_resumen.php">✉️ Manejo Documentos</a>
            </li>
      <?php endif; ?>
      </ul>
      <!-- Opciones de usuario a la derecha -->
      <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
        <ul class="navbar-nav ms-auto">
            <?php if (basename($_SERVER['PHP_SELF']) != 'dashboard.php'): // No mostrar el reloj de la barra si estamos en el dashboard ?>
            <li class="nav-item">
                <span class="navbar-text me-3" id="admin-clock">--:--:--</span>
            </li>
            <?php endif; ?>
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