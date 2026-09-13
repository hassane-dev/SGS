<?php
/**
 * Vue : Tableau de Bord Analytique - Suivi et Parcours Scolaire de l'Élève.
 */
require_once __DIR__ . '/../layouts/header_able.php';
require_once __DIR__ . '/../layouts/sidebar_able.php';
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">

        <!-- Fil d'Ariane -->
        <div class="page-header mb-4">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/dashboard"><?= _('Accueil') ?></a></li>
                            <li class="breadcrumb-item"><a href="/eleves"><?= _('Élèves') ?></a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?= _('Suivi & Parcours Scolaire') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- En-tête de l'élève -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm bg-primary text-white">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avtar avtar-xl bg-white text-primary rounded-circle fs-2 fw-bold">
                                    <?= strtoupper(substr($eleve['prenom'], 0, 1) . substr($eleve['nom'], 0, 1)) ?>
                                </div>
                                <div>
                                    <h3 class="text-white mb-1"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h3>
                                    <p class="mb-0 text-white-50">
                                        <span class="me-3"><i class="ph-duotone ph-identification-card me-1"></i><?= _('Matricule :') ?> <strong><?= htmlspecialchars($eleve['matricule'] ?? $eleve['identifiant_public'] ?? 'N/A') ?></strong></span>
                                        <span><i class="ph-duotone ph-gender-intersex me-1"></i><?= _('Sexe :') ?> <strong><?= htmlspecialchars($eleve['sexe'] ?? 'N/A') ?></strong></span>
                                    </p>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="/eleves/details?id=<?= $eleve['id_eleve'] ?>" class="btn btn-light text-primary fw-bold">
                                    <i class="ph-duotone ph-user me-1"></i><?= _('Fiche Élève') ?>
                                </a>
                                <a href="/eleves" class="btn btn-outline-light">
                                    <i class="ph-duotone ph-arrow-left me-1"></i><?= _('Retour') ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cartes Décisionnelles de Synthèse -->
        <div class="row g-3 mb-4">
            <!-- Carte 1 : Dernière Moyenne -->
            <div class="col-md-6 col-xl-2">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-3">
                        <span class="text-muted fs-7 text-uppercase fw-semibold"><?= _('Dernière Moyenne') ?></span>
                        <h4 class="mb-1 mt-2 text-primary">
                            <?= $performanceSummary['latest_average'] !== null ? number_format($performanceSummary['latest_average'], 2) : '—' ?>
                            <small class="fs-6 text-muted">/20</small>
                        </h4>
                        <div>
                            <?php if ($performanceSummary['average_delta'] > 0): ?>
                                <span class="badge bg-light-success text-success"><i class="ph-duotone ph-arrow-up me-1"></i>+<?= number_format($performanceSummary['average_delta'], 2) ?></span>
                            <?php elseif ($performanceSummary['average_delta'] < 0): ?>
                                <span class="badge bg-light-danger text-danger"><i class="ph-duotone ph-arrow-down me-1"></i><?= number_format($performanceSummary['average_delta'], 2) ?></span>
                            <?php else: ?>
                                <span class="badge bg-light-secondary text-secondary"><?= _('Stable') ?></span>
                            <?php endif; ?>
                            <span class="text-muted fs-7 ms-1"><?= _('depuis préc.') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carte 2 : Position dans la Classe -->
            <div class="col-md-6 col-xl-2">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-3">
                        <span class="text-muted fs-7 text-uppercase fw-semibold"><?= _('Rang Récent') ?></span>
                        <h4 class="mb-1 mt-2 text-dark">
                            <?= htmlspecialchars($performanceSummary['latest_rank'] ?? '—') ?>
                            <?php if ($performanceSummary['effectif']): ?>
                                <small class="fs-6 text-muted">/ <?= (int)$performanceSummary['effectif'] ?></small>
                            <?php endif; ?>
                        </h4>
                        <div>
                            <?php if ($performanceSummary['rank_delta'] > 0): ?>
                                <span class="badge bg-light-success text-success"><i class="ph-duotone ph-arrow-up me-1"></i>+<?= (int)$performanceSummary['rank_delta'] ?> <?= _('places') ?></span>
                            <?php elseif ($performanceSummary['rank_delta'] < 0): ?>
                                <span class="badge bg-light-danger text-danger"><i class="ph-duotone ph-arrow-down me-1"></i><?= (int)$performanceSummary['rank_delta'] ?> <?= _('places') ?></span>
                            <?php else: ?>
                                <span class="badge bg-light-secondary text-secondary"><?= _('Rang inchangé') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carte 3 : Écart Classe -->
            <div class="col-md-6 col-xl-2">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-3">
                        <span class="text-muted fs-7 text-uppercase fw-semibold"><?= _('Écart vs Classe') ?></span>
                        <h4 class="mb-1 mt-2 <?= ($performanceSummary['latest_class_gap'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= $performanceSummary['latest_class_gap'] !== null ? sprintf("%+0.2f", $performanceSummary['latest_class_gap']) : '—' ?>
                            <small class="fs-6 text-muted">pts</small>
                        </h4>
                        <div class="text-muted fs-7">
                            <?= ($performanceSummary['latest_class_gap'] ?? 0) >= 0 ? _('Au-dessus de la classe') : _('En dessous de la classe') ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carte 4 : Tendance Générale -->
            <div class="col-md-6 col-xl-2">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-3">
                        <span class="text-muted fs-7 text-uppercase fw-semibold"><?= _('Tendance Générale') ?></span>
                        <h5 class="mb-1 mt-2">
                            <?php if ($performanceSummary['general_trend'] === _('En progression')): ?>
                                <span class="badge bg-success fs-6"><i class="ph-duotone ph-trend-up me-1"></i><?= _('En progression') ?></span>
                            <?php elseif ($performanceSummary['general_trend'] === _('En régression')): ?>
                                <span class="badge bg-danger fs-6"><i class="ph-duotone ph-trend-down me-1"></i><?= _('En régression') ?></span>
                            <?php else: ?>
                                <span class="badge bg-info fs-6"><i class="ph-duotone ph-minus me-1"></i><?= htmlspecialchars($performanceSummary['general_trend']) ?></span>
                            <?php endif; ?>
                        </h5>
                        <div class="text-muted fs-7 mt-2">
                            <?= htmlspecialchars($performanceSummary['period_label'] ?? _('Historique')) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carte 5 : Meilleure Matière -->
            <div class="col-md-6 col-xl-2">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-3">
                        <span class="text-muted fs-7 text-uppercase fw-semibold"><?= _('Meilleure Matière') ?></span>
                        <h6 class="mb-1 mt-2 text-truncate fw-bold text-dark" title="<?= htmlspecialchars($performanceSummary['best_subject']['nom'] ?? '—') ?>">
                            <?= htmlspecialchars($performanceSummary['best_subject']['nom'] ?? '—') ?>
                        </h6>
                        <span class="badge bg-light-success text-success fw-bold">
                            <?= isset($performanceSummary['best_subject']['note']) ? number_format($performanceSummary['best_subject']['note'], 2) . ' / 20' : '—' ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Carte 6 : Matière à Surveiller -->
            <div class="col-md-6 col-xl-2">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-3">
                        <span class="text-muted fs-7 text-uppercase fw-semibold"><?= _('À Surveiller') ?></span>
                        <h6 class="mb-1 mt-2 text-truncate fw-bold text-dark" title="<?= htmlspecialchars($performanceSummary['worst_subject']['nom'] ?? '—') ?>">
                            <?= htmlspecialchars($performanceSummary['worst_subject']['nom'] ?? '—') ?>
                        </h6>
                        <span class="badge bg-light-danger text-danger fw-bold">
                            <?= isset($performanceSummary['worst_subject']['note']) ? number_format($performanceSummary['worst_subject']['note'], 2) . ' / 20' : '—' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ligne de Graphiques G1 (Moyenne Générale) & G2 (Élève vs Classe) -->
        <div class="row mb-4 g-3">
            <!-- G1: Évolution de la Moyenne Générale -->
            <div class="col-xl-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                        <h5 class="mb-0"><i class="ph-duotone ph-chart-line-up text-primary me-2"></i><?= _('G1 — Évolution de la Moyenne Générale') ?></h5>
                        <span class="badge bg-light-primary text-primary"><?= _('Chronologique') ?></span>
                    </div>
                    <div class="card-body">
                        <div id="chart-general-average-trend" style="min-height: 320px;"></div>
                    </div>
                </div>
            </div>

            <!-- G2: Élève vs Moyenne de Classe -->
            <div class="col-xl-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                        <h5 class="mb-0"><i class="ph-duotone ph-chart-bar text-success me-2"></i><?= _('G2 — Élève vs Moyenne de Classe') ?></h5>
                        <span class="badge bg-light-success text-success"><?= _('Comparatif') ?></span>
                    </div>
                    <div class="card-body">
                        <div id="chart-student-vs-class" style="min-height: 320px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ligne de Graphiques G3 (Rang) & G5 (Profil Académique) -->
        <div class="row mb-4 g-3">
            <!-- G3: Évolution du Rang -->
            <div class="col-xl-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                        <h5 class="mb-0"><i class="ph-duotone ph-trophy text-warning me-2"></i><?= _('G3 — Évolution du Rang dans la Classe') ?></h5>
                        <small class="text-muted"><?= _('Axe inversé (1er en haut)') ?></small>
                    </div>
                    <div class="card-body">
                        <div id="chart-rank-trend" style="min-height: 320px;"></div>
                    </div>
                </div>
            </div>

            <!-- G5: Profil par Matière (Dernière séquence) -->
            <div class="col-xl-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                        <h5 class="mb-0"><i class="ph-duotone ph-chart-polar text-info me-2"></i><?= _('G5 — Profil Académique par Matière') ?></h5>
                        <span class="badge bg-light-info text-info"><?= htmlspecialchars($latestSubjectProfile['period_label'] ?? _('Dernière séquence')) ?></span>
                    </div>
                    <div class="card-body">
                        <div id="chart-subject-profile" style="min-height: 320px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- G4: Évolution par Matière avec Sélection Dynamique -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h5 class="mb-0"><i class="ph-duotone ph-books text-purple me-2"></i><?= _('G4 — Évolution de la Moyenne par Matière') ?></h5>
                        <div class="d-flex align-items-center gap-2">
                            <label for="select-subject-filter" class="form-label mb-0 fs-7 fw-semibold text-muted"><?= _('Sélectionner une matière :') ?></label>
                            <select id="select-subject-filter" class="form-select form-select-sm w-auto">
                                <option value="all"><?= _('Toutes les matières') ?></option>
                                <?php foreach ($subjectTrendSeries['subjects'] as $subj): ?>
                                    <option value="<?= (int)$subj['matiere_id'] ?>"><?= htmlspecialchars($subj['nom_matiere']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="chart-subject-trend" style="min-height: 350px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Analytique : Points Forts, Axes d'Amélioration & Variations -->
        <div class="row mb-4 g-3">
            <!-- Points Forts -->
            <div class="col-md-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm border-start border-4 border-success">
                    <div class="card-body">
                        <h6 class="card-title text-success fw-bold"><i class="ph-duotone ph-thumbs-up me-2"></i><?= _('Points Forts') ?></h6>
                        <?php if (empty($performanceMetrics['latest_subjects_sorted'])): ?>
                            <p class="text-muted fs-7 mb-0"><?= _('Aucune donnée disponible.') ?></p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush fs-7">
                                <?php
                                $top3 = array_slice($performanceMetrics['latest_subjects_sorted'], 0, 3);
                                foreach ($top3 as $s):
                                ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($s['nom_matiere']) ?></span>
                                        <span class="badge bg-success fw-bold"><?= number_format($s['annual_average'], 2) ?> / 20</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Axes d'Amélioration -->
            <div class="col-md-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm border-start border-4 border-danger">
                    <div class="card-body">
                        <h6 class="card-title text-danger fw-bold"><i class="ph-duotone ph-warning-circle me-2"></i><?= _('Axes d\'Amélioration') ?></h6>
                        <?php if (empty($performanceMetrics['latest_subjects_sorted'])): ?>
                            <p class="text-muted fs-7 mb-0"><?= _('Aucune donnée disponible.') ?></p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush fs-7">
                                <?php
                                $sortedReverse = array_reverse($performanceMetrics['latest_subjects_sorted']);
                                $bottom3 = array_slice($sortedReverse, 0, 3);
                                foreach ($bottom3 as $s):
                                ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($s['nom_matiere']) ?></span>
                                        <span class="badge bg-danger fw-bold"><?= number_format($s['annual_average'], 2) ?> / 20</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Plus Forte Progression -->
            <div class="col-md-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm border-start border-4 border-info">
                    <div class="card-body">
                        <h6 class="card-title text-info fw-bold"><i class="ph-duotone ph-trend-up me-2"></i><?= _('Plus Forte Progression') ?></h6>
                        <?php if (empty($performanceMetrics['top_progressions'])): ?>
                            <p class="text-muted fs-7 mb-0"><?= _('S'évalue sur au moins 2 années.') ?></p>
                        <?php else:
                            $bestProg = $performanceMetrics['top_progressions'][0];
                        ?>
                            <div class="mt-2">
                                <h6 class="mb-1 text-dark fw-bold"><?= htmlspecialchars($bestProg['nom_matiere']) ?></h6>
                                <p class="mb-1 fs-7 text-muted"><?= htmlspecialchars($bestProg['first_average']) ?> &rarr; <?= htmlspecialchars($bestProg['latest_average']) ?> / 20</p>
                                <span class="badge bg-light-success text-success fw-bold fs-6">
                                    +<?= number_format($bestProg['total_delta'], 2) ?> pts
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Plus Forte Régression -->
            <div class="col-md-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm border-start border-4 border-warning">
                    <div class="card-body">
                        <h6 class="card-title text-warning fw-bold"><i class="ph-duotone ph-trend-down me-2"></i><?= _('Plus Forte Régression') ?></h6>
                        <?php if (empty($performanceMetrics['top_regressions'])): ?>
                            <p class="text-muted fs-7 mb-0"><?= _('S'évalue sur au moins 2 années.') ?></p>
                        <?php else:
                            $worstProg = $performanceMetrics['top_regressions'][0];
                        ?>
                            <div class="mt-2">
                                <h6 class="mb-1 text-dark fw-bold"><?= htmlspecialchars($worstProg['nom_matiere']) ?></h6>
                                <p class="mb-1 fs-7 text-muted"><?= htmlspecialchars($worstProg['first_average']) ?> &rarr; <?= htmlspecialchars($worstProg['latest_average']) ?> / 20</p>
                                <span class="badge bg-light-danger text-danger fw-bold fs-6">
                                    <?= number_format($worstProg['total_delta'], 2) ?> pts
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section : Chronologie du Parcours Scolaire -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0"><i class="ph-duotone ph-path me-2 text-primary"></i><?= _('Historique du Parcours Scolaire') ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($timeline)): ?>
                            <p class="text-muted mb-0"><?= _('Aucun historique d\'inscription trouvé pour cet élève.') ?></p>
                        <?php else: ?>
                            <div class="d-flex flex-wrap gap-3 align-items-center justify-content-start">
                                <?php foreach ($timeline as $index => $t): ?>
                                    <div class="p-3 bg-light rounded border text-center flex-fill position-relative" style="min-width: 200px;">
                                        <span class="badge bg-primary mb-2"><?= htmlspecialchars($t['annee_libelle']) ?></span>
                                        <h6 class="mb-1 text-dark fw-bold"><?= htmlspecialchars($t['classe_libelle']) ?></h6>
                                        <p class="mb-0 text-muted fs-7"><?= htmlspecialchars($t['nom_cycle'] ?? _('Cycle non spécifié')) ?></p>
                                    </div>
                                    <?php if ($index < count($timeline) - 1): ?>
                                        <div class="text-primary fs-3 d-none d-md-block"><i class="ph-duotone ph-arrow-right"></i></div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section : Relevés & Snapshots des Bulletins Officiels -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                        <h5 class="mb-0"><i class="ph-duotone ph-seal-check text-success me-2"></i><?= _('Historique des Bulletins Officiels Scellés') ?></h5>
                        <small class="text-muted"><?= _('Source : snapshots officiels') ?></small>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($bulletinSnapshots)): ?>
                            <div class="p-4 text-center text-muted"><?= _('Aucun bulletin officiel scellé enregistré pour cet élève.') ?></div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th><?= _('Année Académique') ?></th>
                                            <th><?= _('Séquence') ?></th>
                                            <th class="text-center"><?= _('Moyenne Générale') ?></th>
                                            <th class="text-center"><?= _('Moyenne Classe') ?></th>
                                            <th class="text-center"><?= _('Rang Officiel') ?></th>
                                            <th><?= _('Appréciation') ?></th>
                                            <th class="text-center"><?= _('Statut') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bulletinSnapshots as $b): ?>
                                            <tr>
                                                <td class="fw-semibold"><?= htmlspecialchars($b['annee_libelle']) ?></td>
                                                <td><?= htmlspecialchars($b['sequence_nom']) ?></td>
                                                <td class="text-center fw-bold text-primary fs-6"><?= number_format($b['moyenne_generale'], 2) ?> / 20</td>
                                                <td class="text-center text-muted"><?= $b['moyenne_classe'] !== null ? number_format($b['moyenne_classe'], 2) . ' / 20' : '—' ?></td>
                                                <td class="text-center fw-semibold"><?= htmlspecialchars($b['rang'] ?? 'N/A') ?></td>
                                                <td class="fs-7 text-muted"><?= htmlspecialchars($b['appreciation'] ?? '—') ?></td>
                                                <td class="text-center">
                                                    <?php if ($b['statut'] === 'publie'): ?>
                                                        <span class="badge bg-success"><?= _('Publié') ?></span>
                                                    <?php elseif ($b['statut'] === 'valide'): ?>
                                                        <span class="badge bg-primary"><?= _('Validé') ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark"><?= _('Provisoire') ?></span>
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

    </div>
</div>
<!-- [ Main Content ] end -->

<!-- Script ApexCharts pour les visualisations -->
<script src="/assets/js/plugins/apexcharts.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    // Injecter proprement les données JSON échappées depuis PHP
    const generalAverageData = <?= json_encode($generalAverageTrend, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const studentVsClassData = <?= json_encode($studentVsClassSeries, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const rankTrendData = <?= json_encode($rankTrendSeries, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const subjectProfileData = <?= json_encode($latestSubjectProfile, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const subjectTrendData = <?= json_encode($subjectTrendSeries, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    // G1 : Graphique Évolution Moyenne Générale
    if (generalAverageData && generalAverageData.categories && generalAverageData.categories.length > 0) {
        const optionsG1 = {
            chart: {
                type: 'line',
                height: 320,
                toolbar: { show: false }
            },
            stroke: { curve: 'smooth', width: 3 },
            colors: ['#4680ff'],
            series: generalAverageData.series,
            xaxis: { categories: generalAverageData.categories },
            yaxis: { min: 0, max: 20, forceNiceScale: true },
            markers: { size: 6, hover: { size: 8 } },
            tooltip: {
                y: {
                    formatter: function (val, opts) {
                        const status = generalAverageData.statuses[opts.dataPointIndex];
                        return val.toFixed(2) + " / 20 (" + (status === 'officiel' ? 'Officiel' : 'Provisoire') + ")";
                    }
                }
            }
        };
        new ApexCharts(document.querySelector("#chart-general-average-trend"), optionsG1).render();
    } else {
        document.querySelector("#chart-general-average-trend").innerHTML = '<div class="text-center text-muted p-4"><?= _("Données insuffisantes pour afficher la courbe.") ?></div>';
    }

    // G2 : Graphique Élève vs Classe
    if (studentVsClassData && studentVsClassData.categories && studentVsClassData.categories.length > 0) {
        const optionsG2 = {
            chart: {
                type: 'line',
                height: 320,
                toolbar: { show: false }
            },
            stroke: { curve: 'smooth', width: [3, 2], dashArray: [0, 4] },
            colors: ['#2ca87f', '#dc3545'],
            series: studentVsClassData.series,
            xaxis: { categories: studentVsClassData.categories },
            yaxis: { min: 0, max: 20, forceNiceScale: true },
            markers: { size: 5 },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val !== null ? val.toFixed(2) + " / 20" : "N/A";
                    }
                }
            }
        };
        new ApexCharts(document.querySelector("#chart-student-vs-class"), optionsG2).render();
    } else {
        document.querySelector("#chart-student-vs-class").innerHTML = '<div class="text-center text-muted p-4"><?= _("Données insuffisantes pour la comparaison.") ?></div>';
    }

    // G3 : Graphique Évolution du Rang (Axe Y Inversé)
    if (rankTrendData && rankTrendData.categories && rankTrendData.categories.length > 0) {
        const maxEff = Math.max(...rankTrendData.effectifs.filter(e => e !== null), 40);
        const optionsG3 = {
            chart: {
                type: 'line',
                height: 320,
                toolbar: { show: false }
            },
            stroke: { curve: 'straight', width: 3 },
            colors: ['#e3a008'],
            series: [{ name: 'Rang', data: rankTrendData.ranks }],
            xaxis: { categories: rankTrendData.categories },
            yaxis: {
                reversed: true,
                min: 1,
                max: maxEff,
                forceNiceScale: true
            },
            markers: { size: 6 },
            tooltip: {
                custom: function({ series, seriesIndex, dataPointIndex, w }) {
                    const rStr = rankTrendData.rank_strings[dataPointIndex];
                    const eff = rankTrendData.effectifs[dataPointIndex];
                    return '<div class="p-2 fw-semibold">Rang : ' + rStr + (eff ? ' / ' + eff + ' élèves' : '') + '</div>';
                }
            }
        };
        new ApexCharts(document.querySelector("#chart-rank-trend"), optionsG3).render();
    } else {
        document.querySelector("#chart-rank-trend").innerHTML = '<div class="text-center text-muted p-4"><?= _("Données de rang non disponibles.") ?></div>';
    }

    // G5 : Profil par Matière (Radar / Barres)
    if (subjectProfileData && subjectProfileData.subjects && subjectProfileData.subjects.length > 0) {
        const optionsG5 = {
            chart: {
                type: 'bar',
                height: 320,
                toolbar: { show: false }
            },
            plotOptions: {
                bar: { horizontal: true, barHeight: '50%', borderRadius: 4 }
            },
            colors: ['#0284c7'],
            series: [{ name: 'Moyenne Matière', data: subjectProfileData.averages }],
            xaxis: { categories: subjectProfileData.subjects, min: 0, max: 20 },
            tooltip: {
                y: {
                    formatter: function(val) { return val.toFixed(2) + " / 20"; }
                }
            }
        };
        new ApexCharts(document.querySelector("#chart-subject-profile"), optionsG5).render();
    } else {
        document.querySelector("#chart-subject-profile").innerHTML = '<div class="text-center text-muted p-4"><?= _("Profil par matière non disponible.") ?></div>';
    }

    // G4 : Graphique Évolution par Matière avec Filtrage
    let subjectChartInstance = null;

    function renderSubjectTrendChart(filterSubjectId) {
        if (!subjectTrendData || !subjectTrendData.subjects || subjectTrendData.subjects.length === 0) {
            document.querySelector("#chart-subject-trend").innerHTML = '<div class="text-center text-muted p-4"><?= _("Aucune donnée d\'évolution par matière.") ?></div>';
            return;
        }

        let filteredSeries = [];
        if (filterSubjectId === 'all') {
            filteredSeries = subjectTrendData.subjects.map(s => ({
                name: s.nom_matiere,
                data: s.data
            }));
        } else {
            const target = subjectTrendData.subjects.find(s => s.matiere_id == filterSubjectId);
            if (target) {
                filteredSeries = [{
                    name: target.nom_matiere,
                    data: target.data
                }];
            }
        }

        const optionsG4 = {
            chart: {
                type: 'line',
                height: 350,
                toolbar: { show: true }
            },
            stroke: { curve: 'smooth', width: 3 },
            series: filteredSeries,
            xaxis: { categories: subjectTrendData.categories },
            yaxis: { min: 0, max: 20, forceNiceScale: true },
            markers: { size: 5 },
            tooltip: {
                y: {
                    formatter: function(val) { return val !== null ? val.toFixed(2) + " / 20" : "N/A"; }
                }
            }
        };

        if (subjectChartInstance) {
            subjectChartInstance.destroy();
        }
        subjectChartInstance = new ApexCharts(document.querySelector("#chart-subject-trend"), optionsG4);
        subjectChartInstance.render();
    }

    renderSubjectTrendChart('all');

    const selectFilter = document.querySelector("#select-subject-filter");
    if (selectFilter) {
        selectFilter.addEventListener("change", function(e) {
            renderSubjectTrendChart(e.target.value);
        });
    }

});
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
