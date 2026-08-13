<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header d-print-none">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= htmlspecialchars($title) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- Navigation Tabs -->
        <div class="d-print-none">
            <?php $activeTab = 'comparaison'; require_once __DIR__ . '/_tabs.php'; ?>
        </div>

        <!-- School Comparison Card -->
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0 text-white"><i class="ph-duotone ph-chart-bar me-2"></i><?= _("Indicateurs Normalisés et Classement Comparatif des Établissements") ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th><?= _("Établissement") ?></th>
                                <th class="text-center"><?= _("Effectif Élèves") ?></th>
                                <th class="text-end"><?= _("Liquidités") ?></th>
                                <th class="text-end"><?= _("Total Recettes") ?></th>
                                <th class="text-end"><?= _("Total Charges") ?></th>
                                <th class="text-end"><?= _("Charges par Élève") ?></th>
                                <th class="text-center"><?= _("Exécution Budgétaire") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comparisonData as $row): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['nom_lycee']) ?></strong></td>
                                    <td class="text-center"><span class="badge bg-light-primary text-primary font-bold"><?= number_format($row['effectif'], 0, ',', ' ') ?></span></td>
                                    <td class="text-end font-monospace"><?= number_format($row['liquidites'], 2, ',', ' ') ?> FCFA</td>
                                    <td class="text-end font-monospace text-success"><?= number_format($row['recettes'], 2, ',', ' ') ?> FCFA</td>
                                    <td class="text-end font-monospace text-danger"><?= number_format($row['charges'], 2, ',', ' ') ?> FCFA</td>
                                    <td class="text-end font-monospace text-warning font-bold">
                                        <?= number_format($row['charges_par_eleve'], 2, ',', ' ') ?> FCFA
                                    </td>
                                    <td class="text-center">
                                        <div class="progress" style="height: 12px; min-width: 100px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= min(100, $row['taux_execution']) ?>%" aria-valuenow="<?= htmlspecialchars($row['taux_execution']) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small class="text-muted mt-1 d-block"><?= number_format($row['taux_execution'], 2, ',', ' ') ?>%</small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
