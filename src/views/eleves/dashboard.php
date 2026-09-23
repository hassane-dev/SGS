<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- Page Header & Action CTA Bar -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _("Dashboard Élèves, Inscriptions & Effectifs") ?></h2>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <?php if (Auth::can('inscrire', 'eleve')): ?>
                            <a href="/eleves/create" class="btn btn-primary me-2">
                                <i class="ph-duotone ph-user-plus me-1"></i><?= _("Nouvelle Inscription") ?>
                            </a>
                        <?php endif; ?>
                        <?php if (Auth::can('reinscrire', 'eleve')): ?>
                            <a href="/reinscriptions" class="btn btn-outline-primary me-2">
                                <i class="ph-duotone ph-arrows-counter-clockwise me-1"></i><?= _("Réinscription") ?>
                            </a>
                        <?php endif; ?>
                        <?php if (Auth::can('view_all', 'eleve')): ?>
                            <a href="/eleves" class="btn btn-light-secondary">
                                <i class="ph-duotone ph-list-bullets me-1"></i><?= _("Liste des Élèves") ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card mb-4">
            <div class="card-body py-3">
                <form id="filterForm" method="GET" action="/eleves/dashboard" class="row g-2 align-items-center">
                    <?php if (!empty($lycees)): ?>
                        <div class="col-md-2">
                            <label class="form-label small mb-1"><?= _("Établissement") ?></label>
                            <select name="lycee_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <?php foreach ($lycees as $l): ?>
                                    <option value="<?= $l['id'] ?>" <?= ($filters['lycee_id'] == $l['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($l['nom_lycee']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-2">
                        <label class="form-label small mb-1"><?= _("Année Académique") ?></label>
                        <select name="annee_academique_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <?php foreach ($annees as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= ($filters['annee_academique_id'] == $a['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a['libelle']) ?> <?= $a['est_active'] ? ' (' . _("Active") . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small mb-1"><?= _("Cycle") ?></label>
                        <select name="cycle_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value=""><?= _("Tous les cycles") ?></option>
                            <?php foreach ($cycles as $c): ?>
                                <option value="<?= $c['id_cycle'] ?>" <?= ($filters['cycle_id'] == $c['id_cycle']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nom_cycle']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small mb-1"><?= _("Classe") ?></label>
                        <select name="classe_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value=""><?= _("Toutes les classes") ?></option>
                            <?php foreach ($classes as $cl): ?>
                                <option value="<?= $cl['id_classe'] ?? $cl['id'] ?>" <?= ($filters['classe_id'] == ($cl['id_classe'] ?? $cl['id'])) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(Classe::getFormattedName($cl)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <a href="/eleves/dashboard" class="btn btn-sm btn-link text-muted me-2"><?= _("Réinitialiser") ?></a>
                        <button type="submit" class="btn btn-sm btn-primary"><?= _("Filtrer") ?></button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($dashboardData['message']) && empty($dashboardData['success'])): ?>
            <div class="alert alert-warning">
                <?= htmlspecialchars($dashboardData['message']) ?>
            </div>
        <?php else: ?>
            <?php $kpis = $dashboardData['kpis'] ?? []; ?>

            <!-- Summary KPI Cards -->
            <div class="row">
                <div class="col-md-6 col-xl-3">
                    <div class="card bg-primary text-white mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h3 class="text-white mb-1" id="kpiTotalActifs"><?= number_format($kpis['total_actifs'] ?? 0, 0, ',', ' ') ?></h3>
                                    <p class="text-white-50 mb-0"><?= _("Élèves Actifs") ?></p>
                                </div>
                                <div class="avatar avatar-lg bg-white bg-opacity-22 text-white rounded-circle">
                                    <i class="ph-duotone ph-student f-32"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card bg-info text-white mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h3 class="text-white mb-1" id="kpiParite">
                                        <?= number_format($kpis['filles'] ?? 0, 0, ',', ' ') ?> <small class="fs-6 opacity-75">F</small> /
                                        <?= number_format($kpis['garcons'] ?? 0, 0, ',', ' ') ?> <small class="fs-6 opacity-75">G</small>
                                    </h3>
                                    <p class="text-white-50 mb-0"><?= sprintf(_("Parité Genre (%s%% F)"), $kpis['taux_filles'] ?? 0) ?></p>
                                </div>
                                <div class="avatar avatar-lg bg-white bg-opacity-22 text-white rounded-circle">
                                    <i class="ph-duotone ph-gender-intersex f-32"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card bg-success text-white mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h3 class="text-white mb-1" id="kpiFluxInscr">
                                        <?= number_format($kpis['nouvelles_inscriptions'] ?? 0, 0, ',', ' ') ?> / <?= number_format($kpis['reinscriptions'] ?? 0, 0, ',', ' ') ?>
                                    </h3>
                                    <p class="text-white-50 mb-0"><?= _("Nouvelles / Réinscriptions") ?></p>
                                </div>
                                <div class="avatar avatar-lg bg-white bg-opacity-22 text-white rounded-circle">
                                    <i class="ph-duotone ph-user-check f-32"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card bg-warning text-white mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h3 class="text-white mb-1" id="kpiEnAttentePaiement"><?= number_format($kpis['en_attente_paiement'] ?? 0, 0, ',', ' ') ?></h3>
                                    <p class="text-white-50 mb-0"><?= _("En attente de paiement") ?></p>
                                </div>
                                <div class="avatar avatar-lg bg-white bg-opacity-22 text-white rounded-circle">
                                    <i class="ph-duotone ph-clock-counter-clockwise f-32"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="row">
                <!-- G1: Effectifs par Niveau -->
                <div class="col-xl-6 col-md-12 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="mb-0"><?= _("Répartition de l'Effectif par Niveau") ?></h5>
                        </div>
                        <div class="card-body">
                            <div id="chartEffectifNiveau" style="min-height: 320px;"></div>
                        </div>
                    </div>
                </div>

                <!-- G2: Parité Filles/Garçons par Niveau -->
                <div class="col-xl-6 col-md-12 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="mb-0"><?= _("Parité Filles / Garçons par Niveau") ?></h5>
                        </div>
                        <div class="card-body">
                            <div id="chartGenderByNiveau" style="min-height: 320px;"></div>
                        </div>
                    </div>
                </div>

                <!-- G3: Types d'inscriptions -->
                <div class="col-xl-6 col-md-12 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="mb-0"><?= _("Répartition : Nouvelles Inscriptions vs Réinscriptions") ?></h5>
                        </div>
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <div id="chartInscriptionTypes" style="width: 100%; min-height: 300px;"></div>
                        </div>
                    </div>
                </div>

                <!-- G4: Statuts et Mouvements -->
                <div class="col-xl-6 col-md-12 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="mb-0"><?= _("Statuts & Mouvements des Élèves") ?></h5>
                        </div>
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <div id="chartMovements" style="width: 100%; min-height: 300px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detail Table by Class -->
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><?= _("Effectifs Détaillés par Classe") ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?= _("Cycle") ?></th>
                                    <th><?= _("Classe") ?></th>
                                    <th class="text-center"><?= _("Filles") ?></th>
                                    <th class="text-center"><?= _("Garçons") ?></th>
                                    <th class="text-center"><?= _("Total Actifs") ?></th>
                                    <th class="text-center"><?= _("Taux Filles") ?></th>
                                    <th class="text-end"><?= _("Action") ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $breakdownClasse = $dashboardData['breakdowns']['by_classe'] ?? []; ?>
                                <?php if (empty($breakdownClasse)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4"><?= _("Aucune classe trouvée pour les filtres sélectionnés.") ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($breakdownClasse as $bRow): ?>
                                        <?php
                                            $tot = (int)$bRow['total_actifs'];
                                            $f = (int)$bRow['filles'];
                                            $rate = $tot > 0 ? round(($f / $tot) * 100, 1) : 0.0;
                                        ?>
                                        <tr>
                                            <td><span class="badge bg-light-primary text-primary"><?= htmlspecialchars($bRow['nom_cycle']) ?></span></td>
                                            <td class="fw-bold"><?= htmlspecialchars($bRow['nom_classe']) ?></td>
                                            <td class="text-center text-success fw-semibold"><?= number_format($f, 0, ',', ' ') ?></td>
                                            <td class="text-center text-info fw-semibold"><?= number_format((int)$bRow['garcons'], 0, ',', ' ') ?></td>
                                            <td class="text-center fw-bold"><?= number_format($tot, 0, ',', ' ') ?></td>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <span class="me-2"><?= $rate ?>%</span>
                                                    <div class="progress w-50" style="height: 6px;">
                                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $rate ?>%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <a href="/eleves?classe_id=<?= $bRow['id_classe'] ?>" class="btn btn-sm btn-link-secondary">
                                                    <i class="ph-duotone ph-users me-1"></i><?= _("Voir élèves") ?>
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
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rawData = <?= json_encode($dashboardData['charts'] ?? []) ?>;

    if (!rawData || !rawData.effectif_niveau) return;

    // G1: Effectif par Niveau
    const optG1 = {
        chart: { type: 'bar', height: 320, toolbar: { show: false } },
        colors: ['#4680ff'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
        dataLabels: { enabled: true },
        series: rawData.effectif_niveau.series || [],
        xaxis: { categories: rawData.effectif_niveau.categories || [] },
        yaxis: { title: { text: "Nombre d'élèves" } }
    };
    new ApexCharts(document.querySelector("#chartEffectifNiveau"), optG1).render();

    // G2: Parité par Niveau
    const optG2 = {
        chart: { type: 'bar', height: 320, toolbar: { show: false } },
        colors: ['#2ca87f', '#04a9f5'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
        dataLabels: { enabled: false },
        series: rawData.gender_by_niveau.series || [],
        xaxis: { categories: rawData.gender_by_niveau.categories || [] }
    };
    new ApexCharts(document.querySelector("#chartGenderByNiveau"), optG2).render();

    // G3: Inscription Types (Donut)
    const optG3 = {
        chart: { type: 'donut', height: 300 },
        colors: ['#2ca87f', '#4680ff'],
        labels: rawData.inscription_types.labels || [],
        series: rawData.inscription_types.series || [],
        legend: { position: 'bottom' }
    };
    new ApexCharts(document.querySelector("#chartInscriptionTypes"), optG3).render();

    // G4: Mouvements (Pie)
    const optG4 = {
        chart: { type: 'pie', height: 300 },
        colors: ['#2ca87f', '#e58a00', '#dc2626', '#6b7280', '#9333ea'],
        labels: rawData.movements.labels || [],
        series: rawData.movements.series || [],
        legend: { position: 'bottom' }
    };
    new ApexCharts(document.querySelector("#chartMovements"), optG4).render();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
