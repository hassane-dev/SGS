<?php
// src/views/evaluations/dashboard.php
require_once __DIR__ . '/../layouts/header_able.php';

$d = $dashboardData ?? [];
$comp = $d['completion'] ?? [];
$perf = $d['performance'] ?? [];
$seq = $d['sequence'] ?? [];
$alerts = $d['alerts'] ?? [];
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- Breadcrumb & Header -->
        <div class="page-header mb-4">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-header-title">
                            <h2 class="mb-0">
                                <i class="ph-duotone ph-chart-bar text-primary me-2"></i>
                                <?= _("Notes & Évaluations") ?>
                            </h2>
                        </div>
                        <ul class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="/dashboard"><i class="ph-duotone ph-house"></i> <?= _("Accueil") ?></a></li>
                            <li class="breadcrumb-item"><?= _("Pédagogie") ?></li>
                            <li class="breadcrumb-item active"><?= _("Notes & Évaluations") ?></li>
                        </ul>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <span class="badge <?= htmlspecialchars($seq['status_badge_class'] ?? 'bg-light-primary text-primary') ?> fs-6 p-2">
                            <i class="ph-duotone <?= ($seq['is_closed'] ?? false) ? 'ph-lock-key' : 'ph-lightning' ?> me-1"></i>
                            <?= htmlspecialchars(($seq['nom'] ?? '') . " — " . ($seq['status_label'] ?? '')) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- HUB ACTIONS / CTA BAR -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0 bg-grd-primary text-white">
                    <div class="card-body p-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <h5 class="text-white mb-1"><i class="ph-duotone ph-rocket me-2"></i><?= _("Actions Rapides & Opérations") ?></h5>
                                <p class="text-white-50 small mb-0"><?= _("Accédez directement aux fonctions de saisie, paramétrage et bulletins.") ?></p>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <?php if (Auth::can('create_own', 'note') || Auth::can('view_all', 'note')): ?>
                                    <a href="/evaluations/select_class" class="btn btn-light btn-sm font-weight-bold">
                                        <i class="ph-duotone ph-pencil-simple-line text-primary me-1"></i><?= _("Saisir / Consulter les Notes") ?>
                                    </a>
                                <?php endif; ?>

                                <?php if (Auth::can('manage_settings', 'evaluation')): ?>
                                    <a href="/evaluations/settings" class="btn btn-outline-light btn-sm">
                                        <i class="ph-duotone ph-calendar-blank me-1"></i><?= _("Périodes de Saisie") ?>
                                    </a>
                                    <a href="/evaluations/deblocage" class="btn btn-outline-light btn-sm">
                                        <i class="ph-duotone ph-lock-key-open me-1"></i><?= _("Déblocages") ?>
                                    </a>
                                    <a href="/evaluations/types" class="btn btn-outline-light btn-sm">
                                        <i class="ph-duotone ph-gear me-1"></i><?= _("Types d'Évaluation") ?>
                                    </a>
                                <?php endif; ?>

                                <?php if (Auth::can('generate', 'bulletin') || Auth::can('validate', 'bulletin') || Auth::can('print', 'bulletin')): ?>
                                    <a href="/bulletins" class="btn btn-warning btn-sm text-dark font-weight-bold">
                                        <i class="ph-duotone ph-file-text me-1"></i><?= _("Gestion des Bulletins") ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Form in Cascade -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="ph-duotone ph-funnel me-2"></i><?= _("Filtres en cascade & Périmètre d'analyse") ?></h5>
            </div>
            <div class="card-body">
                <form id="dashboardFilterForm" method="GET" action="/evaluations/dashboard" class="row g-3 align-items-end">
                    <?php if (!empty($lycees)): ?>
                        <div class="col-md-3">
                            <label class="form-label text-muted small fw-bold"><?= _("Établissement / Lycée") ?></label>
                            <select name="lycee_id" id="filterLycee" class="form-select form-select-sm">
                                <?php foreach ($lycees as $l): ?>
                                    <option value="<?= $l['id'] ?>" <?= ($filters['lycee_id'] == $l['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($l['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-2">
                        <label class="form-label text-muted small fw-bold"><?= _("Année Académique") ?></label>
                        <select name="annee_academique_id" id="filterAnnee" class="form-select form-select-sm">
                            <?php foreach ($annees as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= ($filters['annee_academique_id'] == $a['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a['nom']) ?> <?= $a['est_active'] ? ' (' . _("Active") . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label text-muted small fw-bold"><?= _("Séquence") ?></label>
                        <select name="sequence_id" id="filterSequence" class="form-select form-select-sm">
                            <?php foreach ($sequences as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($filters['sequence_id'] == $s['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['nom']) ?> (<?= ucfirst($s['statut']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label text-muted small fw-bold"><?= _("Cycle") ?></label>
                        <select name="cycle_id" id="filterCycle" class="form-select form-select-sm">
                            <option value=""><?= _("Tous les cycles") ?></option>
                            <?php foreach ($cycles as $c): ?>
                                <option value="<?= $c['id_cycle'] ?? $c['id'] ?>" <?= ($filters['cycle_id'] == ($c['id_cycle'] ?? $c['id'])) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nom_cycle'] ?? $c['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label text-muted small fw-bold"><?= _("Type d'évaluation") ?></label>
                        <select name="type_evaluation_id" id="filterTypeEval" class="form-select form-select-sm">
                            <option value=""><?= _("Tous les types") ?></option>
                            <?php foreach ($typesEvaluation as $te): ?>
                                <option value="<?= $te['id'] ?>" <?= ($filters['type_evaluation_id'] == $te['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($te['libelle']) ?> (<?= $te['nombre_evaluation'] ?> occ.)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-12 text-end">
                        <a href="/evaluations/dashboard" class="btn btn-sm btn-outline-secondary me-2"><i class="ph-duotone ph-arrow-counter-clockwise me-1"></i><?= _("Réinitialiser") ?></a>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="ph-duotone ph-magnifying-glass me-1"></i><?= _("Filtrer") ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ZONE C: ALERTES FACTUELLES -->
        <?php if (!empty($alerts)): ?>
            <div class="row mb-3">
                <?php foreach ($alerts as $al): ?>
                    <div class="col-12">
                        <div class="alert alert-<?= htmlspecialchars($al['type']) ?> alert-dismissible fade show" role="alert">
                            <strong><i class="ph-duotone ph-info me-1"></i> <?= htmlspecialchars($al['title']) ?> :</strong>
                            <?= htmlspecialchars($al['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- ZONE A: ÉTAT DE LA SAISIE (COMPLÉTION) -->
        <div class="card mb-4 border-start border-primary border-4 shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-primary"><i class="ph-duotone ph-check-square-offset me-2"></i><?= _("ZONE A : État de la Saisie & Complétude") ?></h5>
                <span class="badge bg-primary fs-6"><?= $comp['completion_rate'] ?? 0 ?>% <?= _("complété") ?></span>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-6">
                        <div class="p-3 border rounded text-center bg-body">
                            <span class="text-muted small d-block"><?= _("Évaluations Attendues") ?></span>
                            <h3 class="mb-0 text-dark"><?= number_format($comp['expected_evaluations'] ?? 0) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-3 border rounded text-center bg-body">
                            <span class="text-muted small d-block"><?= _("Évaluations Saisies") ?></span>
                            <h3 class="mb-0 text-success"><?= number_format($comp['recorded_evaluations'] ?? 0) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-3 border rounded text-center bg-body">
                            <span class="text-muted small d-block"><?= _("Évaluations Manquantes") ?></span>
                            <h3 class="mb-0 text-danger"><?= number_format($comp['missing_evaluations'] ?? 0) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-3 border rounded text-center bg-body">
                            <span class="text-muted small d-block"><?= _("Élèves Évalués") ?></span>
                            <h3 class="mb-0 text-info"><?= number_format($comp['assessed_students'] ?? 0) ?> / <?= number_format($comp['total_students'] ?? 0) ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small font-weight-bold text-muted"><?= _("Taux de complétude globale des notes") ?></span>
                        <span class="small font-weight-bold text-primary"><?= $comp['completion_rate'] ?? 0 ?>%</span>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $comp['completion_rate'] ?? 0 ?>%;" aria-valuenow="<?= $comp['completion_rate'] ?? 0 ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <!-- Incomplete Classes & Subjects Tables -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border mb-0">
                            <div class="card-header bg-light py-2">
                                <h6 class="mb-0 text-dark"><i class="ph-duotone ph-warning-circle me-1 text-warning"></i><?= _("Classes avec Saisie Incomplète") ?></h6>
                            </div>
                            <div class="table-responsive" style="max-height: 250px;">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th><?= _("Classe") ?></th>
                                            <th class="text-center"><?= _("Attendues") ?></th>
                                            <th class="text-center"><?= _("Saisies") ?></th>
                                            <th class="text-end"><?= _("Taux") ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($comp['incomplete_classes'])): ?>
                                            <?php foreach (array_slice($comp['incomplete_classes'], 0, 10) as $ic): ?>
                                                <tr>
                                                    <td><strong><?= htmlspecialchars($ic['nom_classe']) ?></strong></td>
                                                    <td class="text-center"><?= $ic['expected'] ?></td>
                                                    <td class="text-center text-success"><?= $ic['recorded'] ?></td>
                                                    <td class="text-end">
                                                        <span class="badge bg-light-warning text-warning"><?= $ic['rate'] ?>%</span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center text-muted py-3"><?= _("Toutes les classes sont à 100% complètes !") ?></td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border mb-0">
                            <div class="card-header bg-light py-2">
                                <h6 class="mb-0 text-dark"><i class="ph-duotone ph-books me-1 text-info"></i><?= _("Matières avec Saisie Incomplète") ?></h6>
                            </div>
                            <div class="table-responsive" style="max-height: 250px;">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th><?= _("Matière") ?></th>
                                            <th><?= _("Classe") ?></th>
                                            <th class="text-center"><?= _("Manquantes") ?></th>
                                            <th class="text-end"><?= _("Taux") ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($comp['incomplete_subjects'])): ?>
                                            <?php foreach (array_slice($comp['incomplete_subjects'], 0, 10) as $is): ?>
                                                <tr>
                                                    <td><strong><?= htmlspecialchars($is['nom_matiere']) ?></strong></td>
                                                    <td><?= htmlspecialchars($is['nom_classe']) ?></td>
                                                    <td class="text-center text-danger"><?= $is['missing'] ?></td>
                                                    <td class="text-end">
                                                        <span class="badge bg-light-danger text-danger"><?= $is['rate'] ?>%</span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center text-muted py-3"><?= _("Aucune matière incomplète !") ?></td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ZONE B: PERFORMANCE ACADÉMIQUE -->
        <div class="card mb-4 border-start border-success border-4 shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-success"><i class="ph-duotone ph-trend-up me-2"></i><?= _("ZONE B : Performance Académique") ?></h5>
                <span class="text-muted small">
                    <i class="ph-duotone ph-info me-1"></i>
                    <?= ($seq['is_closed'] ?? false) ? _("Source : Bulletins scellés") : _("Source : Moteur officiel live") ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-6">
                        <div class="p-3 border rounded text-center bg-body">
                            <span class="text-muted small d-block"><?= _("Moyenne Générale") ?></span>
                            <h2 class="mb-0 text-primary"><?= number_format($perf['moyenne_generale'] ?? 0, 2) ?> <small class="fs-6 text-muted">/20</small></h2>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-3 border rounded text-center bg-body">
                            <span class="text-muted small d-block"><?= _("Note Minimale") ?></span>
                            <h2 class="mb-0 text-danger"><?= number_format($perf['min_moyenne'] ?? 0, 2) ?> <small class="fs-6 text-muted">/20</small></h2>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-3 border rounded text-center bg-body">
                            <span class="text-muted small d-block"><?= _("Note Maximale") ?></span>
                            <h2 class="mb-0 text-success"><?= number_format($perf['max_moyenne'] ?? 0, 2) ?> <small class="fs-6 text-muted">/20</small></h2>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-3 border rounded text-center bg-body">
                            <span class="text-muted small d-block"><?= _("Élèves Classés") ?></span>
                            <h2 class="mb-0 text-dark"><?= number_format($perf['assessed_students_count'] ?? 0) ?></h2>
                        </div>
                    </div>
                </div>

                <!-- ApexCharts Visualizations -->
                <div class="row g-3 mb-4">
                    <!-- Distribution Chart -->
                    <div class="col-md-6">
                        <div class="card border mb-0 h-100">
                            <div class="card-header bg-light py-2">
                                <h6 class="mb-0 text-dark"><i class="ph-duotone ph-chart-pie me-1"></i><?= _("Distribution des Moyennes (/20)") ?></h6>
                            </div>
                            <div class="card-body">
                                <div id="chartDistribution" style="min-height: 280px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Class Comparison Chart -->
                    <div class="col-md-6">
                        <div class="card border mb-0 h-100">
                            <div class="card-header bg-light py-2">
                                <h6 class="mb-0 text-dark"><i class="ph-duotone ph-chart-bar-horizontal me-1"></i><?= _("Comparaison des Moyennes par Classe") ?></h6>
                            </div>
                            <div class="card-body">
                                <div id="chartClassComparison" style="min-height: 280px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subject Performance Table -->
                <div class="card border mb-0">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0 text-dark"><i class="ph-duotone ph-list-numbers me-1"></i><?= _("Moyennes par Matière & Appréciations") ?></h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th><?= _("Matière") ?></th>
                                    <th class="text-center"><?= _("Élèves Évalués") ?></th>
                                    <th class="text-center"><?= _("Moyenne (/20)") ?></th>
                                    <th class="text-end"><?= _("Appréciation Institutionnelle") ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($perf['subject_averages'])): ?>
                                    <?php foreach ($perf['subject_averages'] as $sub): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($sub['nom_matiere']) ?></strong></td>
                                            <td class="text-center"><?= $sub['evaluated_students'] ?></td>
                                            <td class="text-center fw-bold text-primary"><?= number_format($sub['moyenne'], 2) ?></td>
                                            <td class="text-end">
                                                <span class="badge bg-light-info text-info"><?= htmlspecialchars($sub['appreciation']) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted py-3"><?= _("Aucune moyenne par matière disponible pour cette sélection.") ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ApexCharts Integration JS -->
<script src="/assets/js/plugins/apexcharts.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Distribution Chart Data
    const distData = <?= json_encode($perf['distribution'] ?? []) ?>;
    const distOptions = {
        series: [{
            name: "Nombre d'élèves",
            data: [
                distData['0_5'] || 0,
                distData['5_10'] || 0,
                distData['10_12'] || 0,
                distData['12_14'] || 0,
                distData['14_16'] || 0,
                distData['16_20'] || 0
            ]
        }],
        chart: {
            type: 'bar',
            height: 280,
            toolbar: { show: false }
        },
        colors: ['#dc3545', '#ffc107', '#17a2b8', '#20c997', '#28a745', '#007bff'],
        plotOptions: {
            bar: { distributed: true, borderRadius: 4, columnWidth: '55%' }
        },
        dataLabels: { enabled: true },
        xaxis: {
            categories: ['[0 - 5[', '[5 - 10[', '[10 - 12[', '[12 - 14[', '[14 - 16[', '[16 - 20]']
        },
        legend: { show: false }
    };
    new ApexCharts(document.querySelector("#chartDistribution"), distOptions).render();

    // 2. Class Comparison Chart Data
    const classData = <?= json_encode($perf['class_comparison'] ?? []) ?>;
    const classCategories = classData.map(c => c.nom_classe);
    const classAverages = classData.map(c => c.moyenne);

    const classOptions = {
        series: [{
            name: "Moyenne Générale",
            data: classAverages
        }],
        chart: {
            type: 'bar',
            height: 280,
            toolbar: { show: false }
        },
        colors: ['#4680ff'],
        plotOptions: {
            bar: { horizontal: true, borderRadius: 4 }
        },
        dataLabels: {
            enabled: true,
            formatter: function(val) { return val.toFixed(2) + " /20"; }
        },
        xaxis: {
            categories: classCategories,
            max: 20
        }
    };
    new ApexCharts(document.querySelector("#chartClassComparison"), classOptions).render();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>