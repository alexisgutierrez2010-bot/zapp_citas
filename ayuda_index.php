<?php
require_once __DIR__ . '/languages.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('help_index_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card-header { background-color: #343a40; color: white; }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1><i class="bi bi-question-circle-fill text-primary"></i> <?php echo __('help_index_header'); ?></h1>
            <a href="javascript:window.close();" class="btn btn-secondary"><?php echo __('help_close_window'); ?></a>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h4><?php echo __('help_index_objective_title'); ?></h4>
            </div>
            <div class="card-body">
                <p><?php echo __('help_index_objective_body'); ?></p>
            </div>
        </div>

        <div class="accordion" id="ayudaAccordion">
            <!-- Sección ZApp Citas -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        <i class="bi bi-gear-wide-connected me-2"></i> <strong><?php echo __('help_index_admin_title'); ?></strong>
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#ayudaAccordion">
                    <div class="accordion-body">
                        <p><?php echo __('help_index_admin_desc'); ?></p>
                        <strong><?php echo __('help_functionalities'); ?></strong>
                        <ul>
                            <li><?php echo __('help_index_admin_func_1'); ?></li>
                            <li><?php echo __('help_index_admin_func_2'); ?></li>
                            <li><?php echo __('help_index_admin_func_3'); ?></li>
                            <li><?php echo __('help_index_admin_func_4'); ?></li>
                        </ul>
                        <strong><?php echo __('help_access'); ?></strong> <?php echo __('help_index_admin_access'); ?>
                    </div>
                </div>
            </div>

            <!-- Sección App Cliente -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        <i class="bi bi-person-circle me-2"></i> <strong><?php echo __('help_index_client_title'); ?></strong>
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#ayudaAccordion">
                    <div class="accordion-body">
                        <p><?php echo __('help_index_client_desc'); ?></p>
                        <strong><?php echo __('help_functionalities'); ?></strong>
                        <ul>
                            <li><?php echo __('help_index_client_func_1'); ?></li>
                            <li><?php echo __('help_index_client_func_2'); ?></li>
                            <li><?php echo __('help_index_client_func_3'); ?></li>
                            <li><?php echo __('help_index_client_func_4'); ?></li>
                        </ul>
                        <strong><?php echo __('help_access'); ?></strong> <?php echo __('help_index_client_access'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>