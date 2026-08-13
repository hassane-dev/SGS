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
            <?php $activeTab = 'dashboard'; require_once __DIR__ . '/_tabs.php'; ?>
        </div>

        <!-- Lycee Filter for multi-school access -->
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
                        <div class="col-md-3">
                            <label class="form-label"><?= _("Date début") ?></label>
                            <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($filters['date_debut'] ?? date('Y-m-01')) ?>" onchange="this.form.submit()">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><?= _("Date fin") ?></label>
                            <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($filters['date_fin'] ?? date('Y-m-d')) ?>" onchange="this.form.submit()">
                        </div>
                        <div class="col-md-2 mt-4 text-end">
                            <a href="/reporting/export?lycee_id=<?= $selectedLyceeId ?>&date_debut=<?= $filters['date_debut'] ?>&date_fin=<?= $filters['date_fin'] ?>" class="btn btn-outline-secondary w-100">
                                <i class="ph-duotone ph-download me-2"></i><?= _("Exporter") ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- KPIs Grid -->
        <div class="row">
            <!-- Liquidités Totales -->
            <div class="col-md-4 col-sm-6">
                <div class="card bg-light-<?= htmlspecialchars($statuses['liquidites_totales'] ?? 'success') ?> text-dark mb-4">
                    <div class="card-body">
                        <h6><?= _("Liquidités Totales") ?></h6>
                        <h3><?= number_format($kpis['liquidites_totales'], 2, ',', ' ') ?> FCFA</h3>
                        <p class="mb-0 text-muted small"><i class="ph-duotone ph-bank"></i> <?= _("Comptes financiers actifs") ?></p>
                    </div>
                </div>
            </div>

            <!-- Recettes Scolaires -->
            <div class="col-md-4 col-sm-6">
                <div class="card bg-light-<?= htmlspecialchars($statuses['recettes_scolaires'] ?? 'success') ?> text-dark mb-4">
                    <div class="card-body">
                        <h6><?= _("Recettes Scolaires") ?></h6>
                        <h3><?= number_format($kpis['recettes_scolaires'], 2, ',', ' ') ?> FCFA</h3>
                        <p class="mb-0 text-muted small"><i class="ph-duotone ph-student"></i> <?= _("Inscriptions & Scolarités validées") ?></p>
                    </div>
                </div>
            </div>

            <!-- Dépenses Réalisées (Consommation Budget) -->
            <div class="col-md-4 col-sm-6">
                <div class="card bg-light-<?= htmlspecialchars($statuses['consommation_budget'] ?? 'success') ?> text-dark mb-4">
                    <div class="card-body">
                        <h6><?= _("Dépenses Réalisées") ?></h6>
                        <h3><?= number_format($kpis['consommation_budget'], 2, ',', ' ') ?> FCFA</h3>
                        <p class="mb-0 text-muted small"><i class="ph-duotone ph-receipt"></i> <?= _("Consommation sur budget") ?></p>
                    </div>
                </div>
            </div>

            <!-- Résultat Net -->
            <div class="col-md-4 col-sm-6">
                <div class="card bg-light-<?= htmlspecialchars($statuses['resultat'] ?? 'success') ?> text-dark mb-4">
                    <div class="card-body">
                        <h6><?= _("Résultat Comptable") ?></h6>
                        <h3><?= number_format($kpis['resultat'], 2, ',', ' ') ?> FCFA</h3>
                        <p class="mb-0 text-muted small"><i class="ph-duotone ph-calculator"></i> <?= _("Produits (Cl 7) - Charges (Cl 6)") ?></p>
                    </div>
                </div>
            </div>

            <!-- Taux de Recouvrement -->
            <div class="col-md-4 col-sm-6">
                <div class="card bg-light-<?= htmlspecialchars($statuses['taux_recouvrement'] ?? 'success') ?> text-dark mb-4">
                    <div class="card-body">
                        <h6><?= _("Taux de Recouvrement") ?></h6>
                        <h3><?= number_format($kpis['taux_recouvrement'], 2, ',', ' ') ?>%</h3>
                        <p class="mb-0 text-muted small"><i class="ph-duotone ph-percent"></i> <?= _("Inscriptions encaissées / prévues") ?></p>
                    </div>
                </div>
            </div>

            <!-- Dette Fournisseur -->
            <div class="col-md-4 col-sm-6">
                <div class="card bg-light-<?= htmlspecialchars($statuses['dette_fournisseur'] ?? 'success') ?> text-dark mb-4">
                    <div class="card-body">
                        <h6><?= _("Dette Fournisseur") ?></h6>
                        <h3><?= number_format($kpis['dette_fournisseur'], 2, ',', ' ') ?> FCFA</h3>
                        <p class="mb-0 text-muted small"><i class="ph-duotone ph-handshake"></i> <?= _("Factures d'achat restant à payer") ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reconciliations & Audit Card -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0 text-white"><i class="ph-duotone ph-shield-check me-2"></i><?= _("Rapprochement & Audit Financier Unifié (Conformité de Vérité)") ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th><?= _("Identifiant") ?></th>
                                        <th><?= _("Libellé du Rapprochement") ?></th>
                                        <th class="text-end"><?= _("Valeur Source") ?></th>
                                        <th class="text-end"><?= _("Valeur Calculée") ?></th>
                                        <th class="text-end"><?= _("Écart") ?></th>
                                        <th class="text-center"><?= _("Statut de Conformité") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reconciliations as $code => $rec): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($rec['code']) ?></strong></td>
                                            <td><?= htmlspecialchars($rec['libelle']) ?></td>
                                            <td class="text-end text-primary font-monospace"><?= number_format($rec['valeur_source'], 2, ',', ' ') ?></td>
                                            <td class="text-end text-success font-monospace"><?= number_format($rec['valeur_calculee'], 2, ',', ' ') ?></td>
                                            <td class="text-end font-monospace text-<?= $rec['ecart'] > 0 ? 'danger' : 'muted' ?>">
                                                <?= number_format($rec['ecart'], 2, ',', ' ') ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($rec['statut'] === 'OK'): ?>
                                                    <span class="badge bg-light-success text-success"><i class="ph-duotone ph-check-circle me-1"></i><?= _("CONFORME") ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-light-danger text-danger"><i class="ph-duotone ph-warning-circle me-1"></i><?= _("ÉCART CONSTATE") ?></span>
                                                <?php endif; ?>
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

    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
