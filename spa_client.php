<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development ad Autorized by WWW.ACTICVEN.COM All rights reserved.
// Update :Nov-26-2025).
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Citas por Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f4f7f6; 
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        #client-clock {
            font-size: clamp(0.8rem, 3vw, 1rem);
            white-space: nowrap;
            color: rgba(255, 255, 255, 0.75);
            align-self: center;
        }
        #app-container { flex: 1; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#" id="navbar-brand-title">Gestión de Citas por Clientes</a>
            <div class="collapse navbar-collapse" id="navbarNav"><ul class="navbar-nav ms-auto" id="nav-menu"></ul></div>
        </div>
    </nav>
    <main id="app-container" class="container mt-4"></main>
    <?php include 'footer.php'; ?>
    <script src="app_client.js?v=<?php echo filemtime('app_client.js'); ?>"></script>
</body>
</html>