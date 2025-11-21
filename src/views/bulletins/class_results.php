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
                            <h2 class="mb-0"><?= _('Class Results for:') ?> <?= htmlspecialchars(Classe::getFormattedName($classe['niveau'], $classe['serie'], $classe['numero'])) ?></h2>
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
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><?= _('Period:') ?> <strong><?= htmlspecialchars($sequence['nom']) ?></strong></h5>
                        <a href="/bulletins" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> <?= _('Back to Selection') ?>
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="dataTable">
                                <thead>
                                    <tr>
                                        <th><?= _('Student Name') ?></th>
                                        <th><?= _('General Average') ?></th>
                                        <th><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($results)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center"><?= _('No results found for this selection. Please ensure grades have been entered.') ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($results as $result): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($result['prenom'] . ' ' . $result['nom']) ?></td>
                                                <td><strong><?= number_format($result['moyenne_generale'], 2) ?> / 20</strong></td>
                                                <td>
                                                    <a href="/bulletins/student?eleve_id=<?= $result['id_eleve'] ?>&sequence_id=<?= $sequence['id'] ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> <?= _('View Report Card') ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
