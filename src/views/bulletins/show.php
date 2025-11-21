<?php
require_once __DIR__ . '/../layouts/header_able.php';
require_once __DIR__ . '/../layouts/sidebar_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header no-print">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Student Report Card') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header no-print">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5><?= _('Actions') ?></h5>
                            <div>
                                <button class="btn btn-primary" onclick="window.print();">
                                    <i class="fas fa-print"></i> <?= _('Print / PDF') ?>
                                </button>
                                <a href="/bulletins/class_results" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> <?= _('Back to Results') ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" id="bulletin-content">
                        <?php
                        // Default layout in case of an issue
                        $layout = $layout ?? ['header', 'info_eleve', 'tableau_notes', 'resume_moyennes'];

                        foreach ($layout as $block_name) {
                            $block_path = __DIR__ . '/blocs/_' . $block_name . '.php';
                            if (file_exists($block_path)) {
                                include $block_path;
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<style>
@media print {
    body {
        background-color: #fff;
    }
    .pc-container, .pc-sidebar, .pc-header, .no-print {
        display: none !important;
    }
    .pc-content {
        padding: 0;
        margin: 0;
    }
    .card {
        border: none;
        box-shadow: none;
    }
    #bulletin-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 0;
        margin: 0;
    }
}
</style>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
