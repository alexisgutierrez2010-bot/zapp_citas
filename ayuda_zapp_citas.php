<?php
require_once __DIR__ . '/languages.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('help_admin_title'); ?></title>
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
            <h1><i class="bi bi-question-circle-fill text-primary"></i> <?php echo __('help_admin_header'); ?></h1>
            <a href="javascript:window.close();" class="btn btn-secondary"><?php echo __('help_close_window'); ?></a>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h4><?php echo __('help_admin_objective_title'); ?></h4>
            </div>
            <div class="card-body">
                <p><?php echo __('help_admin_objective_body'); ?></p>
            </div>
        </div>

        <div class="accordion" id="ayudaAdminAccordion">
            <!-- Dashboard -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDash" aria-expanded="true">
                        <i class="bi bi-speedometer2 me-2"></i> <?php echo __('dashboard'); ?>
                    </button>
                </h2>
                <div id="collapseDash" class="accordion-collapse collapse show" data-bs-parent="#ayudaAdminAccordion">
                    <div class="accordion-body">
                        <p><?php echo __('help_admin_dash_desc'); ?></p>
                        <strong><?php echo __('help_functionalities'); ?></strong>
                        <ul>
                            <li><?php echo __('help_admin_dash_func_1'); ?></li>
                            <li><?php echo __('help_admin_dash_func_2'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Negocios -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNegocios">
                        <i class="bi bi-building me-2"></i> <?php echo __('businesses'); ?>
                    </button>
                </h2>
                <div id="collapseNegocios" class="accordion-collapse collapse" data-bs-parent="#ayudaAdminAccordion">
                    <div class="accordion-body">
                        <p><?php echo __('help_admin_biz_desc'); ?></p>
                        <strong><?php echo __('help_functionalities'); ?></strong>
                        <ul>
                            <li><?php echo __('help_admin_biz_func_1'); ?></li>
                            <li><?php echo __('help_admin_biz_func_2'); ?></li>
                            <li><?php echo __('help_admin_biz_func_3'); ?></li>
                            <li><?php echo __('help_admin_biz_func_4'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Clientes y Servicios -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseClientesServicios">
                        <i class="bi bi-people-fill me-2"></i> <?php echo __('clients'); ?> & <?php echo __('services'); ?>
                    </button>
                </h2>
                <div id="collapseClientesServicios" class="accordion-collapse collapse" data-bs-parent="#ayudaAdminAccordion">
                    <div class="accordion-body">
                        <p><?php echo __('help_admin_clients_services_desc'); ?></p>
                        <strong><?php echo __('help_admin_clients_title'); ?></strong>
                        <ul>
                            <li><?php echo __('help_admin_clients_func_1'); ?></li>
                            <li><?php echo __('help_admin_clients_func_2'); ?></li>
                            <li><?php echo __('help_admin_clients_func_3'); ?></li>
                        </ul>
                        <strong><?php echo __('help_admin_services_title'); ?></strong>
                        <ul>
                            <li><?php echo __('help_admin_services_func_1'); ?></li>
                            <li><?php echo __('help_admin_services_func_2'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Citas y Calendario -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCitas">
                        <i class="bi bi-calendar-check me-2"></i> <?php echo __('appointments'); ?> & <?php echo __('calendar'); ?>
                    </button>
                </h2>
                <div id="collapseCitas" class="accordion-collapse collapse" data-bs-parent="#ayudaAdminAccordion">
                    <div class="accordion-body">
                        <p><?php echo __('help_admin_appoint_desc'); ?></p>
                        <strong><?php echo __('help_functionalities'); ?></strong>
                        <ul>
                            <li><?php echo __('help_admin_appoint_func_1'); ?></li>
                            <li><?php echo __('help_admin_appoint_func_2'); ?></li>
                            <li><?php echo __('help_admin_appoint_func_3'); ?></li>
                            <li><?php echo __('help_admin_appoint_func_4'); ?></li>
                            <li><?php echo __('help_admin_appoint_func_5'); ?></li>
                            <li><?php echo __('help_admin_appoint_func_6'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>