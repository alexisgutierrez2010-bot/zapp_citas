<?php
require_once __DIR__ . '/languages.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('help_owner_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card-header { background-color: #343a40; color: white; }
        .accordion-button { font-weight: 500; }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1><i class="bi bi-question-circle-fill text-primary"></i> <?php echo __('help_owner_header'); ?></h1>
            <a href="javascript:window.close();" class="btn btn-secondary"><?php echo __('help_close_window'); ?></a>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h4><?php echo __('help_owner_objective_title'); ?></h4>
            </div>
            <div class="card-body">
                <p><?php echo __('help_owner_objective_body'); ?></p>
            </div>
        </div>

        <div class="accordion" id="ayudaOwnerAccordion">
            <!-- Mi Agenda -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAgenda" aria-expanded="true">
                        <i class="bi bi-journal-bookmark-fill me-2"></i> <?php echo __('spa_owner_nav_my_agenda'); ?>
                    </button>
                </h2>
                <div id="collapseAgenda" class="accordion-collapse collapse show" data-bs-parent="#ayudaOwnerAccordion">
                    <div class="accordion-body">
                        <p><?php echo __('help_owner_agenda_desc'); ?></p>
                        <strong><?php echo __('help_functionalities'); ?></strong>
                        <ul>
                            <li><?php echo __('help_owner_agenda_func_1'); ?></li>
                            <li><?php echo __('help_owner_agenda_func_2'); ?></li>
                            <li><?php echo __('help_owner_agenda_func_3'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Calendario -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCalendar">
                        <i class="bi bi-calendar-week me-2"></i> <?php echo __('spa_owner_nav_calendar'); ?>
                    </button>
                </h2>
                <div id="collapseCalendar" class="accordion-collapse collapse" data-bs-parent="#ayudaOwnerAccordion">
                    <div class="accordion-body">
                        <p><?php echo __('help_owner_calendar_desc'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Mis Clientes y Mis Servicios -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseClientsServices">
                        <i class="bi bi-people-fill me-2"></i> <?php echo __('spa_owner_nav_clients'); ?> & <?php echo __('spa_owner_nav_services'); ?>
                    </button>
                </h2>
                <div id="collapseClientsServices" class="accordion-collapse collapse" data-bs-parent="#ayudaOwnerAccordion">
                    <div class="accordion-body">
                        <p><?php echo __('help_owner_clients_services_desc'); ?></p>
                        <strong><?php echo __('spa_owner_nav_clients'); ?>:</strong>
                        <ul>
                            <li><?php echo __('help_owner_clients_func_1'); ?></li>
                            <li><?php echo __('help_owner_clients_func_2'); ?></li>
                        </ul>
                        <strong><?php echo __('spa_owner_nav_services'); ?>:</strong>
                        <ul>
                            <li><?php echo __('help_owner_services_func_1'); ?></li>
                            <li><?php echo __('help_owner_services_func_2'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Mi Negocio y Mi Perfil -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBusinessProfile">
                        <i class="bi bi-person-badge me-2"></i> <?php echo __('spa_owner_nav_my_business'); ?> & <?php echo __('spa_owner_nav_my_profile'); ?>
                    </button>
                </h2>
                <div id="collapseBusinessProfile" class="accordion-collapse collapse" data-bs-parent="#ayudaOwnerAccordion">
                    <div class="accordion-body">
                        <p><?php echo __('help_owner_business_profile_desc'); ?></p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>