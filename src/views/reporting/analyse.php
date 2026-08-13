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
            <?php $activeTab = 'analyse'; require_once __DIR__ . '/_tabs.php'; ?>
        </div>

        <!-- Lycee Filter -->
        <?php if (Auth::can('view_all_lycees', 'reporting') && !empty($lycees)): ?>
            <div class="card mb-4 d-print-none">
                <div class="card-body">
                    <form method="GET" class="row align-items-center">
                        <div class="col-md-4">
                            <label class="form-label"><?= _("Établissement") ?></label>
                            <select name="lycee_id" class="form-select" onchange="this.form.submit()">
                                <?php foreach ($lycees as $l): ?>
                                    <option value="<?= $l['id'] ?>" <?= $selectedLyceeId == $l['id'] ? 'selected' : '' ?>><?= htmlspecialchars($l['nom_lycee']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Historical Trend Card -->
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0 text-white"><i class="ph-duotone ph-calendar me-2"></i><?= _("Évolution Mensuelle Réelle des Flux (12 derniers mois)") ?></h5>
            </div>
            <div class="card-body">
                <?php if (empty($history)): ?>
                    <div class="alert alert-info text-center py-4 mb-0">
                        <i class="ph-duotone ph-info-circle fs-2 mb-2 d-block"></i>
                        <?= _("Aucun mouvement de trésorerie disponible pour le tracé de tendance.") ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th><?= _("Période (Mois)") ?></th>
                                    <th class="text-end"><?= _("Total Entrées (Recettes)") ?></th>
                                    <th class="text-end"><?= _("Total Sorties (Dépenses)") ?></th>
                                    <th class="text-end"><?= _("Flux Net Mensuel") ?></th>
                                    <th class="text-center"><?= _("Tendance Visuelle") ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $pt): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($pt['mois']) ?></strong></td>
                                        <td class="text-end text-success font-monospace font-bold"><?= number_format($pt['entrees'], 2, ',', ' ') ?> FCFA</td>
                                        <td class="text-end text-danger font-monospace"><?= number_format($pt['sorties'], 2, ',', ' ') ?> FCFA</td>
                                        <td class="text-end font-monospace font-bold text-<?= $pt['net'] >= 0 ? 'success' : 'danger' ?>">
                                            <?= number_format($pt['net'], 2, ',', ' ') ?> FCFA
                                        </td>
                                        <td class="text-center">
                                            <?php if ($pt['net'] >= 0): ?>
                                                <span class="badge bg-light-success text-success"><i class="ph-duotone ph-arrow-circle-up me-1"></i><?= _("POSITIF") ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-light-danger text-danger"><i class="ph-duotone ph-arrow-circle-down me-1"></i><?= _("DEFICITAIRE") ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
