<?php
/**
 * Vue : Tableau de Bord & Statistiques Disciplinaires (Phase 5.2)
 */
require_once __DIR__ . '/../../layouts/header_able.php';
require_once __DIR__ . '/../../layouts/sidebar_able.php';

$roleLabels = [
    'auteur_principal' => 'Auteur Principal',
    'co_auteur' => 'Co-auteur',
    'complice' => 'Complice',
    'victime' => 'Victime',
    'temoin' => 'Témoin',
];

$sanctStatutLabels = [
    'prononcee' => 'Prononcée',
    'en_cours' => 'En cours',
    'executee' => 'Exécutée',
    'levee' => 'Levée',
    'annulee' => 'Annulée',
];
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">

        <!-- Fil d'Ariane -->
        <div class="page-header mb-3">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-header-title">
                            <h4 class="mb-0"><i class="ph-duotone ph-chart-line-up me-2 text-primary"></i>Tableau de Bord & Statistiques Disciplinaires</h4>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <span class="badge bg-light-primary text-primary fs-6 px-3 py-2">
                            <i class="ph-duotone ph-calendar me-1"></i>
                            Année : <strong><?= htmlspecialchars($activeYear['libelle'] ?? 'Active') ?></strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres du Tableau de Bord -->
        <div class="card shadow-sm mb-4">
            <div class="card-body p-3">
                <form method="GET" action="/discipline/dashboard" class="row g-2 align-items-end">
                    <?php if (!$isTeacher): ?>
                        <div class="col-md-3">
                            <label class="form-label text-muted fs-7 mb-1">Année Académique</label>
                            <select name="annee_academique_id" class="form-select form-select-sm">
                                <?php foreach ($academicYears as $y): ?>
                                    <option value="<?= $y['id'] ?>" <?= ((int)$selectedAnneeId === (int)$y['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($y['libelle']) ?> <?= $y['est_active'] ? '(Active)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 mb-1">Classe</label>
                        <select name="classe_id" class="form-select form-select-sm">
                            <option value="">Toutes les classes</option>
                            <?php foreach ($availableClasses as $c): ?>
                                <option value="<?= $c['id_classe'] ?>" <?= ((int)$selectedClasseId === (int)$c['id_classe']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nom_classe']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label text-muted fs-7 mb-1">Date Début</label>
                        <input type="date" name="date_debut" class="form-control form-select-sm" value="<?= htmlspecialchars($dateDebut ?? '') ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label text-muted fs-7 mb-1">Date Fin</label>
                        <input type="date" name="date_fin" class="form-control form-select-sm" value="<?= htmlspecialchars($dateFin ?? '') ?>">
                    </div>

                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="ph-duotone ph-funnel me-1"></i>Filtrer
                        </button>
                        <a href="/discipline/dashboard" class="btn btn-sm btn-outline-secondary" title="Réinitialiser">
                            <i class="ph-duotone ph-arrow-counter-clockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ligne 1 : Cartes KPI -->
        <div class="row mb-4">
            <div class="col-md-2-4 col-sm-6 mb-3 mb-md-0">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1 fs-7 fw-semibold">Incidents Totaux</p>
                                <h3 class="mb-0 text-primary fw-bold"><?= number_format($totalIncidents) ?></h3>
                                <small class="text-muted fs-8">Hors classés sans suite</small>
                            </div>
                            <div class="avtar bg-light-primary text-primary">
                                <i class="ph-duotone ph-warning fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-2-4 col-sm-6 mb-3 mb-md-0">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1 fs-7 fw-semibold">Élèves Impliqués</p>
                                <h3 class="mb-0 text-info fw-bold"><?= number_format($elevesImpliques) ?></h3>
                                <small class="text-muted fs-8">Tous rôles confondus</small>
                            </div>
                            <div class="avtar bg-light-info text-info">
                                <i class="ph-duotone ph-users-three fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-2-4 col-sm-6 mb-3 mb-md-0">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1 fs-7 fw-semibold">Élèves Responsables</p>
                                <h3 class="mb-0 text-warning fw-bold"><?= number_format($elevesResponsables) ?></h3>
                                <small class="text-muted fs-8">Auteurs & Complices</small>
                            </div>
                            <div class="avtar bg-light-warning text-warning">
                                <i class="ph-duotone ph-user-focus fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-2-4 col-sm-6 mb-3 mb-md-0">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1 fs-7 fw-semibold">Élèves Récidivistes</p>
                                <h3 class="mb-0 text-danger fw-bold"><?= number_format($elevesRecidivistes) ?></h3>
                                <small class="text-muted fs-8">&ge; 2 incidents distincts</small>
                            </div>
                            <div class="avtar bg-light-danger text-danger">
                                <i class="ph-duotone ph-arrows-clockwise fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-2-4 col-sm-6 mb-3 mb-md-0">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1 fs-7 fw-semibold">Sanctions Totales</p>
                                <h3 class="mb-0 text-success fw-bold"><?= number_format($totalSanctions) ?></h3>
                                <small class="text-muted fs-8">
                                    <?php if ($avgDelayDays !== null): ?>
                                        Délai moy: <strong><?= $avgDelayDays ?> j</strong>
                                    <?php else: ?>
                                        Prononcées / En cours
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div class="avtar bg-light-success text-success">
                                <i class="ph-duotone ph-gavel fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ligne 2 : Graphiques Graphique 1 & Graphique 2 -->
        <div class="row mb-4">
            <div class="col-lg-8 mb-3 mb-lg-0">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="ph-duotone ph-trend-up me-2 text-primary"></i>Évolution Mensuelle des Incidents et Sanctions</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($monthlySeries['months'])): ?>
                            <div class="p-5 text-center text-muted">
                                <i class="ph-duotone ph-chart-line-down fs-1 text-secondary mb-2 d-block"></i>
                                Aucune donnée mensuelle enregistrée pour cette période.
                            </div>
                        <?php else: ?>
                            <div id="chart-monthly-evolution" style="min-height: 320px;"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="ph-duotone ph-pie-chart me-2 text-warning"></i>Incidents par Niveau de Gravité</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($severityStats)): ?>
                            <div class="p-5 text-center text-muted">
                                <i class="ph-duotone ph-chart-pie-slice fs-1 text-secondary mb-2 d-block"></i>
                                Aucun incident enregistré.
                            </div>
                        <?php else: ?>
                            <div id="chart-severity-pie" style="min-height: 320px;"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ligne 3 : Graphiques Graphique 3 & Graphique 4 -->
        <div class="row mb-4">
            <div class="col-lg-6 mb-3 mb-lg-0">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="ph-duotone ph-chart-bar me-2 text-success"></i>Répartition des Sanctions par Statut</h5>
                    </div>
                    <div class="card-body">
                        <?php if (array_sum($sanctionsBreakdown) === 0): ?>
                            <div class="p-5 text-center text-muted">
                                <i class="ph-duotone ph-chart-bar fs-1 text-secondary mb-2 d-block"></i>
                                Aucune sanction enregistrée.
                            </div>
                        <?php else: ?>
                            <div id="chart-sanctions-status" style="min-height: 300px;"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="ph-duotone ph-users-three me-2 text-info"></i>Répartition des Rôles d'Implication des Élèves</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($roleStats)): ?>
                            <div class="p-5 text-center text-muted">
                                <i class="ph-duotone ph-user-list fs-1 text-secondary mb-2 d-block"></i>
                                Aucun rôle enregistré.
                            </div>
                        <?php else: ?>
                            <div id="chart-roles-donut" style="min-height: 300px;"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ligne 4 : Tableaux Synthétiques Top 5 -->
        <div class="row mb-4">
            <!-- Top 5 Classes Les Plus Concernées -->
            <div class="col-lg-6 mb-3 mb-lg-0">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="ph-duotone ph-chalkboard me-2 text-primary"></i>Top 5 Classes les Plus Concernées</h5>
                        <span class="badge bg-light-primary text-primary">Consolidé</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($topClasses)): ?>
                            <div class="p-4 text-center text-muted">
                                <i class="ph-duotone ph-check-circle fs-2 text-success mb-2 d-block"></i>
                                Aucune classe concernée par un incident.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Classe</th>
                                            <th class="text-end">Nombre d'Incidents</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topClasses as $idx => $cls): ?>
                                            <tr>
                                                <td><span class="badge bg-light-secondary text-dark"><?= $idx + 1 ?></span></td>
                                                <td><strong class="text-dark"><?= htmlspecialchars($cls['nom_classe']) ?></strong></td>
                                                <td class="text-end fw-bold text-danger"><?= $cls['total_incidents'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top 5 Types d'Incidents Récurrents -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="ph-duotone ph-list-bullets me-2 text-danger"></i>Top 5 Types d'Incidents Récurrents</h5>
                        <span class="badge bg-light-danger text-danger">Récurrence</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($topTypes)): ?>
                            <div class="p-4 text-center text-muted">
                                <i class="ph-duotone ph-check-circle fs-2 text-success mb-2 d-block"></i>
                                Aucun type d'incident enregistré.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Type d'Incident</th>
                                            <th>Gravité</th>
                                            <th class="text-end">Occurrences</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topTypes as $idx => $typ): ?>
                                            <tr>
                                                <td><span class="badge bg-light-secondary text-dark"><?= $idx + 1 ?></span></td>
                                                <td>
                                                    <strong class="d-block text-dark"><?= htmlspecialchars($typ['libelle']) ?></strong>
                                                    <small class="text-muted">Code : <?= htmlspecialchars($typ['code']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light-warning text-warning fw-bold">
                                                        <?= htmlspecialchars($typ['niveau_gravite'] ?? 'N/A') ?>
                                                    </span>
                                                </td>
                                                <td class="text-end fw-bold text-primary"><?= $typ['total_incidents'] ?></td>
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

    </div>
</div>
<!-- [ Main Content ] end -->

<!-- ApexCharts Integration Scripts -->
<script src="/assets/js/plugins/apexcharts.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Chart 1: Monthly Evolution
    <?php if (!empty($monthlySeries['months'])): ?>
    var optionsMonthly = {
        chart: {
            type: 'line',
            height: 320,
            toolbar: { show: false }
        },
        stroke: { curve: 'smooth', width: 3 },
        colors: ['#0d6efd', '#dc3545'],
        series: [
            { name: 'Incidents', data: <?= json_encode($monthlySeries['incidents']) ?> },
            { name: 'Sanctions', data: <?= json_encode($monthlySeries['sanctions']) ?> }
        ],
        xaxis: {
            categories: <?= json_encode($monthlySeries['months']) ?>,
            title: { text: 'Mois' }
        },
        yaxis: { title: { text: 'Nombre' } },
        legend: { position: 'top' }
    };
    new ApexCharts(document.querySelector("#chart-monthly-evolution"), optionsMonthly).render();
    <?php endif; ?>

    // Chart 2: Severity Pie
    <?php if (!empty($severityStats)): ?>
    var severityLabels = <?= json_encode(array_keys($severityStats)) ?>;
    var severityValues = <?= json_encode(array_values($severityStats)) ?>;
    var optionsSeverity = {
        chart: { type: 'pie', height: 320 },
        labels: severityLabels,
        series: severityValues,
        colors: ['#198754', '#0dcaf0', '#ffc107', '#dc3545'],
        legend: { position: 'bottom' }
    };
    new ApexCharts(document.querySelector("#chart-severity-pie"), optionsSeverity).render();
    <?php endif; ?>

    // Chart 3: Sanctions Status Bar
    <?php if (array_sum($sanctionsBreakdown) > 0): ?>
    var statusLabels = <?= json_encode(array_values($sanctStatutLabels)) ?>;
    var statusValues = <?= json_encode([
        $sanctionsBreakdown['prononcee'],
        $sanctionsBreakdown['en_cours'],
        $sanctionsBreakdown['executee'],
        $sanctionsBreakdown['levee'],
        $sanctionsBreakdown['annulee']
    ]) ?>;
    var optionsSanctions = {
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        plotOptions: { bar: { distributed: true, borderRadius: 4, columnWidth: '50%' } },
        colors: ['#ffc107', '#0d6efd', '#198754', '#0dcaf0', '#6c757d'],
        series: [{ name: 'Sanctions', data: statusValues }],
        xaxis: { categories: statusLabels },
        legend: { show: false }
    };
    new ApexCharts(document.querySelector("#chart-sanctions-status"), optionsSanctions).render();
    <?php endif; ?>

    // Chart 4: Roles Donut
    <?php if (!empty($roleStats)): ?>
    var rawRoleKeys = <?= json_encode(array_keys($roleStats)) ?>;
    var roleLabelsMap = <?= json_encode($roleLabels) ?>;
    var roleDisplayLabels = rawRoleKeys.map(function(k) { return roleLabelsMap[k] || k; });
    var roleValues = <?= json_encode(array_values($roleStats)) ?>;
    var optionsRoles = {
        chart: { type: 'donut', height: 300 },
        labels: roleDisplayLabels,
        series: roleValues,
        colors: ['#dc3545', '#ffc107', '#0dcaf0', '#0d6efd', '#6c757d'],
        legend: { position: 'bottom' }
    };
    new ApexCharts(document.querySelector("#chart-roles-donut"), optionsRoles).render();
    <?php endif; ?>

});
</script>

<style>
/* Responsive layout helper for 5-column KPI cards row */
@media (min-width: 992px) {
    .col-md-2-4 {
        flex: 0 0 auto;
        width: 20%;
    }
}
</style>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
