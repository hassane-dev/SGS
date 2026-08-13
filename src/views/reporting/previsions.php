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
            <?php $activeTab = 'previsions'; require_once __DIR__ . '/_tabs.php'; ?>
        </div>

        <!-- Predictor Parameter Card -->
        <div class="card d-print-none mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0 text-white"><i class="ph-duotone ph-sliders me-2"></i><?= _("Configuration du modèle prédictif") ?></h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row align-items-end">
                    <input type="hidden" name="lycee_id" value="<?= htmlspecialchars($selectedLyceeId) ?>">

                    <div class="col-md-3 col-sm-6">
                        <label class="form-label"><?= _("Méthode de projection") ?></label>
                        <select name="method" class="form-select" onchange="this.form.submit()">
                            <option value="baseline" <?= $selectedMethod === 'baseline' ? 'selected' : '' ?>><?= _("Dernier Mois (Baseline)") ?></option>
                            <option value="moving_average" <?= $selectedMethod === 'moving_average' ? 'selected' : '' ?>><?= _("Moyenne Mobile (3 mois)") ?></option>
                            <option value="weighted_moving_average" <?= $selectedMethod === 'weighted_moving_average' ? 'selected' : '' ?>><?= _("Moyenne Mobile Pondérée") ?></option>
                            <option value="linear_trend" <?= $selectedMethod === 'linear_trend' ? 'selected' : '' ?>><?= _("Tendance Linéaire (Régression)") ?></option>
                            <option value="exponential_smoothing" <?= $selectedMethod === 'exponential_smoothing' ? 'selected' : '' ?>><?= _("Lissage Exponentiel Simple") ?></option>
                        </select>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <label class="form-label"><?= _("Scénario d'analyse") ?></label>
                        <select name="scenario" class="form-select" onchange="this.form.submit()">
                            <option value="prudent" <?= $selectedScenario === 'prudent' ? 'selected' : '' ?>><?= _("Prudent (-15%)") ?></option>
                            <option value="central" <?= $selectedScenario === 'central' ? 'selected' : '' ?>><?= _("Central (Baseline)") ?></option>
                            <option value="optimiste" <?= $selectedScenario === 'optimiste' ? 'selected' : '' ?>><?= _("Optimiste (+15%)") ?></option>
                        </select>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <label class="form-label"><?= _("Horizon de prévision") ?></label>
                        <select name="horizon" class="form-select" onchange="this.form.submit()">
                            <option value="1" <?= $selectedHorizon === 1 ? 'selected' : '' ?>><?= _("1 mois") ?></option>
                            <option value="3" <?= $selectedHorizon === 3 ? 'selected' : '' ?>><?= _("3 mois (Trimestre)") ?></option>
                            <option value="6" <?= $selectedHorizon === 6 ? 'selected' : '' ?>><?= _("6 mois (Semestre)") ?></option>
                        </select>
                    </div>

                    <?php if (Auth::can('view_all_lycees', 'reporting') && !empty($lycees)): ?>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label"><?= _("Établissement") ?></label>
                            <select name="lycee_id" class="form-select" onchange="this.form.submit()">
                                <?php foreach ($lycees as $l): ?>
                                    <option value="<?= $l['id'] ?>" <?= $selectedLyceeId == $l['id'] ? 'selected' : '' ?>><?= htmlspecialchars($l['nom_lycee']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Forecaster Result Card -->
        <div class="card">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white"><i class="ph-duotone ph-trend-up me-2"></i><?= _("Prévisions et Intervalles d'Incertitude") ?></h5>
                <span class="badge bg-light-info text-dark font-monospace"><?= _("Généré le: ") ?><?= htmlspecialchars($forecast['date_calcul']) ?></span>
            </div>
            <div class="card-body">
                <?php if (isset($forecast['statut_qualite']) && $forecast['statut_qualite'] === 'INSUFFISANT'): ?>
                    <div class="alert alert-warning text-center py-4 mb-0">
                        <i class="ph-duotone ph-warning-circle fs-1 mb-2 d-block text-warning"></i>
                        <h5><?= _("Données Historiques Insuffisantes") ?></h5>
                        <p class="mb-0 text-muted"><?= htmlspecialchars($forecast['message']) ?></p>
                    </div>
                <?php else: ?>
                    <div class="row align-items-center">
                        <div class="col-md-6 text-center border-end">
                            <p class="text-muted mb-1"><?= _("Flux Net Prévu (M+1)") ?></p>
                            <h1 class="display-4 text-primary font-bold font-monospace"><?= number_format($forecast['valeur_prevue'], 2, ',', ' ') ?> FCFA</h1>
                            <div class="mt-3">
                                <span class="badge bg-light-<?= $forecast['statut_qualite'] === 'EXCELLENT' ? 'success' : 'warning' ?> text-dark p-2">
                                    <i class="ph-duotone ph-activity me-1"></i><?= _("Qualité du modèle: ") ?><?= htmlspecialchars($forecast['statut_qualite']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6 ps-md-4">
                            <h5><?= _("Analyse d'Incertitude & Scénarios") ?></h5>
                            <p class="text-muted small mb-4"><?= _("Ces projections financières calculent automatiquement un intervalle de confiance basé sur la méthode statistique sélectionnée.") ?></p>

                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="text-danger"><strong><i class="ph-duotone ph-caret-down me-1"></i><?= _("Borne Basse Prévue") ?></strong></span>
                                    <span class="font-monospace text-danger font-bold"><?= number_format($forecast['borne_basse'], 2, ',', ' ') ?> FCFA</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="text-primary"><strong><i class="ph-duotone ph-caret-right me-1"></i><?= _("Scénario Central") ?></strong></span>
                                    <span class="font-monospace text-primary font-bold"><?= number_format($forecast['scenarios']['central'], 2, ',', ' ') ?> FCFA</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="text-success"><strong><i class="ph-duotone ph-caret-up me-1"></i><?= _("Borne Haute Prévue") ?></strong></span>
                                    <span class="font-monospace text-success font-bold"><?= number_format($forecast['borne_haute'], 2, ',', ' ') ?> FCFA</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Warning Disclaimer -->
                    <div class="alert alert-light-secondary mt-4 mb-0 small">
                        <i class="ph-duotone ph-info-circle me-1 text-info"></i>
                        <?= _("Note légale : Les prévisions financières constituent des projections basées sur des modèles mathématiques extrapolés de l'historique de trésorerie réel. Elles ne doivent pas être considérées comme des certitudes budgétaires absolues.") ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
