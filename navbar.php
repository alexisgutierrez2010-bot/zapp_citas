<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Dec-05-2025). Reestructuración del menú y añadido selector de idioma.
// La página que incluye este archivo debe proporcionar la conexión $conn y las variables de sesión.
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php?lang=<?php echo $lang; ?>"><?php echo __('zapp_citas'); ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav">
        <?php if (isset($rol_session)): ?>
            <li class="nav-item">
              <a class="nav-link" href="negocios_configuracion.php?lang=<?php echo $lang; ?>"><?php echo __('businesses'); ?></a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="servicios_lista.php?lang=<?php echo $lang; ?>"><?php echo __('services'); ?></a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="clientes_lista.php?lang=<?php echo $lang; ?>"><?php echo __('clients'); ?></a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="citas_lista.php?lang=<?php echo $lang; ?>"><?php echo __('appointments'); ?></a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="calendario_ver.php?lang=<?php echo $lang; ?>"><?php echo __('calendar'); ?></a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="citas_cerrar_vencidas.php?lang=<?php echo $lang; ?>"><?php echo __('close_appointments'); ?></a>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownConfig" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?php echo __('configuration'); ?>
              </a>
              <ul class="dropdown-menu" aria-labelledby="navbarDropdownConfig">
                <li><a class="dropdown-item" href="usuarios_lista.php?lang=<?php echo $lang; ?>"><?php echo __('users'); ?></a></li>
                <li><a class="dropdown-item" href="categorias_lista.php?lang=<?php echo $lang; ?>"><?php echo __('categories'); ?></a></li>
                <li><a class="dropdown-item" href="paises_lista.php?lang=<?php echo $lang; ?>"><?php echo __('locations'); ?></a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="auditoria_reporte.php?lang=<?php echo $lang; ?>"><?php echo __('audit'); ?></a></li>
                <li><a class="dropdown-item" href="seleccionar_resumen.php?lang=<?php echo $lang; ?>"><?php echo __('documents'); ?></a></li>
              </ul>
            </li>
      <?php endif; ?>
      </ul>
      <!-- Opciones de usuario a la derecha -->
      <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
        <ul class="navbar-nav ms-auto">
            <?php
                // Lógica para construir los enlaces del selector de idioma
                $queryParams = $_GET;
                $currentPage = basename($_SERVER['PHP_SELF']);

                $queryParams['lang'] = 'es';
                $es_link = $currentPage . '?' . http_build_query($queryParams);

                $queryParams['lang'] = 'en';
                $en_link = $currentPage . '?' . http_build_query($queryParams);
            ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    🌐 Idioma
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
                    <li><a class="dropdown-item <?php echo ($lang === 'es') ? 'active' : ''; ?>" href="<?php echo $es_link; ?>">Español</a></li>
                    <li><a class="dropdown-item <?php echo ($lang === 'en') ? 'active' : ''; ?>" href="<?php echo $en_link; ?>">English</a></li>
                </ul>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="perfil_ver.php?lang=<?php echo $lang; ?>">👤 <?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?> (<?php echo __('my_profile'); ?>)</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php"><?php echo __('logout'); ?></a>
            </li>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</nav>