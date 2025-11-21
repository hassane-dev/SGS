<?php
require_once __DIR__ . '/../layouts/header_able.php';
require_once __DIR__ . '/../layouts/sidebar_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Report Card Generation') ?></h2>
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
                    <div class="card-header">
                        <h5><?= _('Select Class and Period') ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="/bulletins/class_results" method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="classe_id" class="form-label"><?= _('Class') ?></label>
                                        <select name="classe_id" id="classe_id" class="form-select" required>
                                            <option value=""><?= _('-- Select a class --') ?></option>
                                            <?php foreach ($classes as $classe): ?>
                                                <option value="<?= $classe['id_classe'] ?>"><?= htmlspecialchars(Classe::getFormattedName($classe['niveau'], $classe['serie'], $classe['numero'])) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="sequence_id" class="form-label"><?= _('Period') ?></label>
                                        <select name="sequence_id" id="sequence_id" class="form-select" required>
                                            <option value=""><?= _('-- Select a period --') ?></option>
                                            <?php foreach ($sequences as $sequence): ?>
                                                <option value="<?= $sequence['id'] ?>"><?= htmlspecialchars($sequence['nom']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3"><?= _('Show Results') ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
