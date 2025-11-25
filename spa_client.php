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
    <title>Portal del Cliente</title>
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
            <a class="navbar-brand" href="#" id="navbar-brand-title">Portal del Cliente</a>
            <div class="collapse navbar-collapse" id="navbarNav"><ul class="navbar-nav ms-auto" id="nav-menu"></ul></div>
        </div>
    </nav>
    <main id="app-container" class="container mt-4"></main>
    <footer class="footer mt-auto py-3 bg-dark text-white-50"><div class="container text-center"><small>©2025. Software development and Authorized by <a href="http://www.acticven.com" target="_blank" class="text-white">WWW.ACTICVEN.COM</a> All rights reserved.</small></div></footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="app_client.js?v=<?php echo filemtime('app_client.js'); ?>"></script>
</body>
</html>