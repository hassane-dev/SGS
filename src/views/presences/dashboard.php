<?php
// src/views/presences/dashboard.php
require_once __DIR__ . '/../layouts/header_able.php';

$data = $data ?? [];
$filters = $data['filters'] ?? [];
$lycees = $data['lycees'] ?? [];
$cycles = $data['cycles'] ?? [];
$classes = $data['classes'] ?? [];
$annees = $data['annees'] ?? [];
$dashboardData = $data['dashboardData'] ?? [];
$canViewAll = $data['canViewAll'] ?? false;

$kpis = $dashboardData['kpis'] ?? [];
$period = $dashboardData['period'] ?? [];
$timeline = $dashboardData['timeline'] ?? ['categories' => [], 'series' => []];
$classRates = $dashboardData['class_rates'] ?? [];
$repeatedAbsences = $dashboardData['repeated_absences']['list'] ?? [];
$threshold = $dashboardData['repeated_absences']['threshold'] ?? 5;
$alerts = $dashboardData['alerts'] ?? [];

$totalEnrolled = $dashboardData['total_enrolled_students'] ?? 0;
?>

<div class="pc-container">
    <div class="pc-content">

        <!-- Page Header & Action Hub Bar -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center me-0 ms-0">
                    <div class="col-md-6 ps-0">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><i class="ph-duotone ph-calendar-check me-2 text-primary"></i><?= _("Présences & Absences") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Accueil") ?></a></li>
                            <li class="breadcrumb-item"><?= _("Scolarité") ?></li>
                            <li class="breadcrumb-item"><?= _("Hub Présences") ?></li>
                        </ul>
                    </div>
                    <div class="col-md-6 text-end pe-0">
                        <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                            <?php if (!empty($classes)): ?>
                                <?php $firstClasseId = $classes[0]['id'] ?? $classes[0]['id_classe'] ?? 0; ?>
                                <a href="/presences/gerer/<?= $firstClasseId ?>" class="btn btn-sm btn-primary">
                                    <i class="ph-duotone ph-clipboard-text me-1"></i><?= _("Gérer les présences") ?>
                                </a>
                            <?php endif; ?>
                            <?php if (Auth::can('view_incidents', 'discipline')): ?>
                                <a href="/discipline/incidents" class="btn btn-sm btn-outline-secondary">
                                    <i class="ph-duotone ph-warning-circle me-1"></i><?= _("Incidents") ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card mb-4">
            <div class="card-body py-3">
                <form id="presenceFilterForm" method="GET" action="/presences/dashboard" class="row g-3 align-items-end">
                    <?php if (!empty($lycees)): ?>
                        <div class="col-md-2">
                            <label class="form-label small text-muted mb-1"><?= _("Lycée") ?></label>
                            <select name="lycee_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <?php foreach ($lycees as $lyc): ?>
                                    <option value="<?= $lyc['id'] ?>" <?= ($filters['lycee_id'] == $lyc['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($lyc['nom_lycee']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1"><?= _("Année Académique") ?></label>
                        <select name="annee_academique_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <?php foreach ($annees as $an): ?>
                                <option value="<?= $an['id'] ?>" <?= ($filters['annee_academique_id'] == $an['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($an['libelle'] ?? $an['nom'] ?? '') ?> <?= $an['est_active'] ? '('. _('Active') .')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1"><?= _("Cycle") ?></label>
                        <select name="cycle_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value=""><?= _("Tous les cycles") ?></option>
                            <?php foreach ($cycles as $cyc): ?>
                                <option value="<?= $cyc['id_cycle'] ?>" <?= ($filters['cycle_id'] == $cyc['id_cycle']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cyc['nom_cycle']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1"><?= _("Classe") ?></label>
                        <select name="classe_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value=""><?= _("Toutes les classes") ?></option>
                            <?php foreach ($classes as $c): ?>
                                <?php $cId = $c['id'] ?? $c['id_classe']; ?>
                                <option value="<?= $cId ?>" <?= ($filters['classe_id'] == $cId) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(($c['niveau'] ?? '') . ' ' . ($c['serie'] ?? '') . ' ' . ($c['numero'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1"><?= _("Date Début") ?></label>
                        <input type="date" name="date_debut" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_debut'] ?? '') ?>" onchange="this.form.submit()">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1"><?= _("Date Fin") ?></label>
                        <input type="date" name="date_fin" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_fin'] ?? '') ?>" onchange="this.form.submit()">
                    </div>
                </form>
            </div>
        </div>

        <!-- Descriptive Factual Alerts -->
        <?php if (!empty($alerts)): ?>
            <div class="row mb-3">
                <div class="col-12">
                    <?php foreach ($alerts as $alt): ?>
                        <div class="alert alert-<?= htmlspecialchars($alt['type']) ?> alert-dismissible fade show mb-2 py-2" role="alert">
                            <i class="ph-duotone ph-info me-2 fs-5 align-middle"></i>
                            <strong><?= htmlspecialchars($alt['title']) ?> :</strong> <?= htmlspecialchars($alt['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Zone A — Macro KPI Cards (2D: Occurrences & Distinct Students) -->
        <div class="row">
            <!-- Taux Global de Présence -->
            <div class="col-md-6 col-xl-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted d-block mb-1 fs-6"><?= _("Taux de Présence") ?></span>
                                <h3 class="mb-0 text-success fw-bold"><?= number_format($kpis['presence_rate'] ?? 100, 1) ?>%</h3>
                                <small class="text-muted d-block mt-1">
                                    <i class="ph-duotone ph-users me-1"></i><?= sprintf(_("%d élèves inscrits"), $totalEnrolled) ?>
                                </small>
                            </div>
                            <div class="avtar bg-light-success text-success rounded-circle">
                                <i class="ph-duotone ph-chart-line-up fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Absences du Jour -->
            <div class="col-md-6 col-xl-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted d-block mb-1 fs-6"><?= _("Absences du Jour") ?></span>
                                <h3 class="mb-0 text-danger fw-bold"><?= $kpis['today_absences']['occurrences'] ?? 0 ?> <span class="fs-6 fw-normal text-muted"><?= _("occ.") ?></span></h3>
                                <small class="text-danger fw-semibold d-block mt-1">
                                    <i class="ph-duotone ph-user-minus me-1"></i><?= sprintf(_("%d élève(s) concerné(s)"), $kpis['today_absences']['students'] ?? 0) ?>
                                </small>
                            </div>
                            <div class="avtar bg-light-danger text-danger rounded-circle">
                                <i class="ph-duotone ph-x-circle fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Retards du Jour -->
            <div class="col-md-6 col-xl-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted d-block mb-1 fs-6"><?= _("Retards du Jour") ?></span>
                                <h3 class="mb-0 text-warning fw-bold"><?= $kpis['today_delays']['occurrences'] ?? 0 ?> <span class="fs-6 fw-normal text-muted"><?= _("occ.") ?></span></h3>
                                <small class="text-warning fw-semibold d-block mt-1">
                                    <i class="ph-duotone ph-clock me-1"></i><?= sprintf(_("%d élève(s) concerné(s)"), $kpis['today_delays']['students'] ?? 0) ?>
                                </small>
                            </div>
                            <div class="avtar bg-light-warning text-warning rounded-circle">
                                <i class="ph-duotone ph-clock-afternoon fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Absences Période -->
            <div class="col-md-6 col-xl-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted d-block mb-1 fs-6"><?= _("Absences Période") ?></span>
                                <h3 class="mb-0 text-primary fw-bold"><?= $kpis['period_absences']['occurrences'] ?? 0 ?> <span class="fs-6 fw-normal text-muted"><?= _("occ.") ?></span></h3>
                                <small class="text-primary fw-semibold d-block mt-1">
                                    <i class="ph-duotone ph-users-three me-1"></i><?= sprintf(_("%d élève(s) concerné(s)"), $kpis['period_absences']['students'] ?? 0) ?>
                                </small>
                            </div>
                            <div class="avtar bg-light-primary text-primary rounded-circle">
                                <i class="ph-duotone ph-calendar-blank fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Breakdown Cards: Justified vs Unjustified & Delays Period -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card card-body py-3 border-start border-4 border-danger">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted d-block"><?= _("Absences non justifiées (Période)") ?></small>
                            <span class="h5 mb-0 text-danger fw-bold"><?= $kpis['unjustified_absences']['occurrences'] ?? 0 ?> <?= _("occurrences") ?></span>
                        </div>
                        <span class="badge bg-light-danger text-danger fs-6"><?= $kpis['unjustified_absences']['students'] ?? 0 ?> <?= _("élèves") ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-body py-3 border-start border-4 border-info">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted d-block"><?= _("Absences justifiées (Période)") ?></small>
                            <span class="h5 mb-0 text-info fw-bold"><?= $kpis['justified_absences']['occurrences'] ?? 0 ?> <?= _("occurrences") ?></span>
                        </div>
                        <span class="badge bg-light-info text-info fs-6"><?= $kpis['justified_absences']['students'] ?? 0 ?> <?= _("élèves") ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-body py-3 border-start border-4 border-warning">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted d-block"><?= _("Retards cumulés (Période)") ?></small>
                            <span class="h5 mb-0 text-warning fw-bold"><?= $kpis['delays']['occurrences'] ?? 0 ?> <?= _("occurrences") ?></span>
                        </div>
                        <span class="badge bg-light-warning text-warning fs-6"><?= $kpis['delays']['students'] ?? 0 ?> <?= _("élèves") ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Zone B — ApexCharts Timeline (4 Mutually Exclusive Statuses) -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0"><i class="ph-duotone ph-chart-bar me-2 text-primary"></i><?= _("Évolution Temporelle des Présences (Appels Généraux)") ?></h5>
                        <small class="text-muted"><?= sprintf(_("Du %s au %s"), htmlspecialchars($period['date_debut'] ?? ''), htmlspecialchars($period['date_fin'] ?? '')) ?></small>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($timeline['categories'])): ?>
                            <div id="presenceTimelineChart" style="min-height: 320px;"></div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="ph-duotone ph-calendar-x text-muted display-4 d-block mb-2"></i>
                                <p class="text-muted mb-0"><?= _("Aucune donnée d'appel enregistrée sur la période sélectionnée.") ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Zone C — Class Absence Rates Table -->
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0"><i class="ph-duotone ph-list-numbers me-2 text-primary"></i><?= _("Taux d'Absence par Classe") ?></h5>
                        <span class="badge bg-light-secondary text-secondary"><?= count($classRates) ?> <?= _("classes") ?></span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _("Classe") ?></th>
                                        <th class="text-center"><?= _("Effectif") ?></th>
                                        <th class="text-center"><?= _("Occurrences") ?></th>
                                        <th class="text-center"><?= _("Absences") ?></th>
                                        <th class="text-center"><?= _("Élèves touchés") ?></th>
                                        <th class="text-end pe-3"><?= _("Taux d'absence") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($classRates)): ?>
                                        <?php foreach ($classRates as $cr): ?>
                                            <tr>
                                                <td class="fw-semibold">
                                                    <a href="/presences/gerer/<?= $cr['classe_id'] ?>" class="text-body">
                                                        <?= htmlspecialchars($cr['nom_classe']) ?>
                                                    </a>
                                                </td>
                                                <td class="text-center"><?= $cr['effectif_actif'] ?></td>
                                                <td class="text-center"><?= $cr['occurrences_enregistrees'] ?></td>
                                                <td class="text-center text-danger fw-bold"><?= $cr['occurrences_absence'] ?></td>
                                                <td class="text-center"><?= $cr['eleves_concernes'] ?></td>
                                                <td class="text-end pe-3">
                                                    <span class="badge <?= $cr['taux_absence'] >= 15 ? 'bg-light-danger text-danger' : ($cr['taux_absence'] >= 8 ? 'bg-light-warning text-warning' : 'bg-light-success text-success') ?> fs-6">
                                                        <?= number_format($cr['taux_absence'], 1) ?>%
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                <?= _("Aucune statistique de classe disponible pour ce périmètre.") ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Zone D — Repeated Absences Alert List -->
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0"><i class="ph-duotone ph-warning me-2 text-warning"></i><?= _("Absentéisme Répété") ?></h5>
                        <span class="badge bg-light-warning text-warning"><?= sprintf(_("Seuil ≥ %d abs."), $threshold) ?></span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($repeatedAbsences)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($repeatedAbsences as $ra): ?>
                                    <div class="list-group-item d-flex align-items-center justify-content-between py-3">
                                        <div>
                                            <a href="/eleves/discipline?id=<?= $ra['id_eleve'] ?>" class="fw-bold text-body text-decoration-none">
                                                <?= htmlspecialchars($ra['nom'] . ' ' . $ra['prenom']) ?>
                                            </a>
                                            <small class="d-block text-muted">
                                                <span class="badge bg-light-secondary text-dark me-1"><?= htmlspecialchars($ra['nom_classe']) ?></span>
                                                Matricule : <?= htmlspecialchars($ra['matricule'] ?? '-') ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-danger fs-6"><?= $ra['total_occurrences_absence'] ?> <?= _("absences") ?></span>
                                            <small class="d-block text-muted mt-1">
                                                <?= $ra['absences_unjustified'] ?> <?= _("non just.") ?> / <?= $ra['absences_justified'] ?> <?= _("just.") ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="ph-duotone ph-check-circle text-success display-4 d-block mb-2"></i>
                                <p class="text-muted mb-0"><?= sprintf(_("Aucun élève n'a atteint le seuil de %d absences sur l'année active."), $threshold) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ApexCharts Integration Script -->
<script src="/assets/js/plugins/apexcharts.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const timelineData = <?= json_encode($timeline) ?>;

    if (timelineData && timelineData.categories && timelineData.categories.length > 0) {
        const options = {
            chart: {
                type: 'bar',
                height: 320,
                stacked: true,
                toolbar: { show: false },
                zoom: { enabled: false }
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    legend: { position: 'bottom', offsetX: -10, offsetLeft: 0 }
                }
            }],
            plotOptions: {
                bar: { horizontal: false, borderRadius: 4, columnWidth: '50%' }
            },
            colors: ['#2ca87f', '#e58a00', '#dc2626', '#17a2b8'],
            series: timelineData.series,
            xaxis: {
                categories: timelineData.categories,
                labels: { rotate: -45 }
            },
            legend: { position: 'top' },
            fill: { opacity: 1 },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + " <?= _("occurrences") ?>";
                    }
                }
            }
        };

        const chart = new ApexCharts(document.querySelector("#presenceTimelineChart"), options);
        chart.render();
    }
});
</script>
<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
