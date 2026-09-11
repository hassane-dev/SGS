<?php
require_once __DIR__ . '/../layouts/header_able.php';
require_once __DIR__ . '/../layouts/sidebar_able.php';
require_once __DIR__ . '/../../models/ParamLycee.php';
require_once __DIR__ . '/../../models/ParamGeneral.php';

$eleveId = $bulletin['eleve']['id_eleve'] ?? $_GET['eleve_id'] ?? null;
$sequenceId = $bulletin['sequence']['id'] ?? $_GET['sequence_id'] ?? null;
$lyceeId = $bulletin['eleve']['lycee_id'] ?? Auth::getLyceeId();

$lyceeParams = $lyceeId ? ParamLycee::findByLyceeId($lyceeId) : [];
$paramGeneral = $lyceeId ? ParamGeneral::findByLyceeId($lyceeId) : ['nb_langue' => 1, 'langue_1' => 'fr_FR'];
?>

<div class="pc-container d-print-none">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header d-print-none">
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
        <div class="row d-print-none">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-print-none">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5><?= _('Actions') ?></h5>
                            <div class="d-flex gap-2">
                                <a href="/bulletins/student/print?eleve_id=<?= urlencode((string)$eleveId) ?>&sequence_id=<?= urlencode((string)$sequenceId) ?>" target="_blank" class="btn btn-primary d-inline-flex align-items-center gap-1">
                                    <i class="ph-duotone ph-printer"></i> <?= _('Official Print / PDF') ?>
                                </a>
                                <button class="btn btn-outline-primary" onclick="window.print();">
                                    <i class="ph-duotone ph-printer"></i> <?= _('Quick Print') ?>
                                </button>
                                <a href="/bulletins" class="btn btn-secondary">
                                    <i class="ph-duotone ph-arrow-left"></i> <?= _('Back') ?>
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

<!-- Standalone Printable Wrapper for window.print() placed OUTSIDE .pc-container to fix blank page bug structurally -->
<div id="bulletin-standalone-print" class="d-none d-print-block">
    <?php
    $bulletinsData = [$bulletin];
    $lycee = $lyceeParams;
    $isFullPage = false;
    include __DIR__ . '/print_template.php';
    ?>
</div>

<style>
@media print {
    body {
        background-color: #ffffff !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .pc-container, .pc-sidebar, .pc-header, .no-print, .d-print-none {
        display: none !important;
    }
    #bulletin-standalone-print {
        display: block !important;
        width: 100% !important;
    }
}
</style>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
