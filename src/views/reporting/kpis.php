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
            <?php $activeTab = 'kpis'; require_once __DIR__ . '/_tabs.php'; ?>
        </div>

        <!-- Success/Error Alerts -->
        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <?php unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

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

        <!-- KPI Catalog List -->
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0 text-white"><i class="ph-duotone ph-list me-2"></i><?= _("Catalogue Canonique des KPI & Seuils d'Alerte") ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th><?= _("KPI Code") ?></th>
                                <th><?= _("Nom de l'Indicateur") ?></th>
                                <th><?= _("Catégorie") ?></th>
                                <th class="text-end"><?= _("Valeur Actuelle") ?></th>
                                <th><?= _("Unité") ?></th>
                                <th><?= _("Seuils (Danger / Warning / Objectif)") ?></th>
                                <th class="text-center"><?= _("Actions") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($definitions as $code => $def): ?>
                                <?php if ($code === 'liquidites_par_compte') continue; ?>
                                <tr>
                                    <td><span class="badge bg-light-primary text-primary"><?= htmlspecialchars($def['code']) ?></span></td>
                                    <td>
                                        <strong><?= htmlspecialchars($def['libelle']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($def['formule']) ?></small>
                                    </td>
                                    <td><span class="badge bg-light-secondary text-dark"><?= htmlspecialchars($def['categorie']) ?></span></td>
                                    <td class="text-end font-monospace font-bold text-<?= htmlspecialchars($statuses[$code] ?? 'success') ?>">
                                        <?= number_format($kpis[$code] ?? 0, $def['precision'], ',', ' ') ?>
                                    </td>
                                    <td><?= htmlspecialchars($def['unite']) ?></td>
                                    <td>
                                        <?php if (isset($thresholds[$code])): ?>
                                            <span class="text-danger small font-monospace"><?= number_format($thresholds[$code]['seuil_danger'], 2, ',', ' ') ?></span> /
                                            <span class="text-warning small font-monospace"><?= number_format($thresholds[$code]['seuil_warning'], 2, ',', ' ') ?></span> /
                                            <span class="text-success small font-monospace"><?= number_format($thresholds[$code]['objectif'], 2, ',', ' ') ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small"><em><?= _("Par défaut") ?></em></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (Auth::can('threshold_manage', 'reporting')): ?>
                                            <!-- Button to trigger custom configuration form modal -->
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#collapseSeuil_<?= htmlspecialchars($code) ?>">
                                                <i class="ph-duotone ph-gear me-1"></i><?= _("Régler seuils") ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- Seuil Config Drawer Form -->
                                <?php if (Auth::can('threshold_manage', 'reporting')): ?>
                                    <tr class="collapse d-print-none bg-light" id="collapseSeuil_<?= htmlspecialchars($code) ?>">
                                        <td colspan="7">
                                            <form action="/reporting/threshold/save" method="POST" class="p-3">
                                                <input type="hidden" name="lycee_id" value="<?= htmlspecialchars($selectedLyceeId) ?>">
                                                <input type="hidden" name="kpi_code" value="<?= htmlspecialchars($code) ?>">
                                                <div class="row">
                                                    <div class="col-md-2">
                                                        <label class="form-label small"><?= _("Seuil Danger") ?></label>
                                                        <input type="number" step="0.01" name="seuil_danger" class="form-control form-control-sm" value="<?= htmlspecialchars($thresholds[$code]['seuil_danger'] ?? '0.00') ?>">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label small"><?= _("Seuil Warning") ?></label>
                                                        <input type="number" step="0.01" name="seuil_warning" class="form-control form-control-sm" value="<?= htmlspecialchars($thresholds[$code]['seuil_warning'] ?? '0.00') ?>">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label small"><?= _("Objectif") ?></label>
                                                        <input type="number" step="0.01" name="objectif" class="form-control form-control-sm" value="<?= htmlspecialchars($thresholds[$code]['objectif'] ?? '0.00') ?>">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small"><?= _("Sens interprétation") ?></label>
                                                        <select name="sens_variation" class="form-select form-select-sm">
                                                            <option value="croissant" <?= ($thresholds[$code]['sens_variation'] ?? $def['sens_interpretation']) === 'croissant' ? 'selected' : '' ?>><?= _("Croissant (Plus élevé est meilleur)") ?></option>
                                                            <option value="decroissant" <?= ($thresholds[$code]['sens_variation'] ?? $def['sens_interpretation']) === 'decroissant' ? 'selected' : '' ?>><?= _("Décroissant (Moins élevé est meilleur)") ?></option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3 mt-4 text-end">
                                                        <button type="submit" class="btn btn-sm btn-primary"><i class="ph-duotone ph-floppy-disk me-1"></i><?= _("Enregistrer") ?></button>
                                                    </div>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
