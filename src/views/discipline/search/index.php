<?php
/**
 * Vue : Recherche & Registres Disciplinaires (Phase 5.3)
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

$roleBadges = [
    'auteur_principal' => 'bg-danger',
    'co_auteur' => 'bg-warning text-dark',
    'complice' => 'bg-info text-white',
    'victime' => 'bg-primary',
    'temoin' => 'bg-secondary',
];

$incStatutBadges = [
    'signale' => 'bg-warning text-dark',
    'en_instruction' => 'bg-info text-white',
    'traite' => 'bg-success',
    'classe_sans_suite' => 'bg-secondary',
];

$sanctStatutBadges = [
    'prononcee' => 'bg-warning text-dark',
    'en_cours' => 'bg-primary',
    'executee' => 'bg-success',
    'levee' => 'bg-info text-white',
    'annulee' => 'bg-secondary',
];

// Reconstruct GET parameters string for export buttons
$exportQueryParams = $_GET;
unset($exportQueryParams['page']);
$queryString = http_build_query($exportQueryParams);
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
                            <h4 class="mb-0"><i class="ph-duotone ph-magnifying-glass me-2 text-primary"></i>Recherche & Registres Disciplinaires</h4>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="btn-group">
                            <a href="/discipline/export/csv?<?= $queryString ?>" class="btn btn-outline-success btn-sm">
                                <i class="ph-duotone ph-file-csv me-1"></i>Exporter CSV
                            </a>
                            <a href="/discipline/export/pdf?<?= $queryString ?>" target="_blank" class="btn btn-outline-danger btn-sm">
                                <i class="ph-duotone ph-printer me-1"></i>Imprimer / PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Choix de Registre par Onglets -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card mb-0">
                    <div class="card-body p-2">
                        <ul class="nav nav-pills" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link <?= $tab === 'incidents' ? 'active' : '' ?>" href="/discipline/search?<?= http_build_query(array_merge($_GET, ['tab' => 'incidents', 'page' => 1])) ?>">
                                    <i class="ph-duotone ph-warning me-2"></i>Registre des Incidents
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $tab === 'sanctions' ? 'active' : '' ?>" href="/discipline/search?<?= http_build_query(array_merge($_GET, ['tab' => 'sanctions', 'page' => 1])) ?>">
                                    <i class="ph-duotone ph-gavel me-2"></i>Registre des Sanctions
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulaire de Recherche Avancée Collapsible -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center cursor-pointer" data-bs-toggle="collapse" data-bs-target="#collapseFilter">
                <h5 class="mb-0"><i class="ph-duotone ph-funnel me-2 text-primary"></i>Filtres de Recherche Avancée</h5>
                <i class="ph-duotone ph-caret-down fs-5"></i>
            </div>
            <div id="collapseFilter" class="collapse show card-body">
                <form method="GET" action="/discipline/search" class="row g-2 align-items-end">
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">

                    <?php if (!$isTeacher): ?>
                        <div class="col-md-3">
                            <label class="form-label text-muted fs-7 mb-1">Année Académique</label>
                            <select name="annee_academique_id" class="form-select form-select-sm">
                                <?php foreach ($academicYears as $y): ?>
                                    <option value="<?= $y['id'] ?>" <?= ((int)($queryParams['annee_academique_id'] ?? $activeYear['id'] ?? 0) === (int)$y['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($y['libelle']) ?> <?= $y['est_active'] ? '(Active)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Cascade Pédagogique -->
                    <div class="col-md-2">
                        <label class="form-label text-muted fs-7 mb-1">Cycle</label>
                        <select name="cycle_id" id="filter_cycle_id" class="form-select form-select-sm">
                            <option value="">Tous les cycles</option>
                            <?php foreach ($cycles as $cyc): ?>
                                <option value="<?= $cyc['id_cycle'] ?>" <?= ((int)($queryParams['cycle_id'] ?? 0) === (int)$cyc['id_cycle']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cyc['nom_cycle']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label text-muted fs-7 mb-1">Niveau</label>
                        <input type="text" name="niveau" class="form-control form-control-sm" placeholder="ex: 6eme" value="<?= htmlspecialchars($queryParams['niveau'] ?? '') ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label text-muted fs-7 mb-1">Série</label>
                        <input type="text" name="serie" class="form-control form-control-sm" placeholder="ex: C, G" value="<?= htmlspecialchars($queryParams['serie'] ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 mb-1">Classe</label>
                        <select name="classe_id" id="filter_classe_id" class="form-select form-select-sm">
                            <option value="">Toutes les classes</option>
                            <?php foreach ($availableClasses as $c): ?>
                                <option value="<?= $c['id_classe'] ?>" <?= ((int)($queryParams['classe_id'] ?? 0) === (int)$c['id_classe']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nom_classe']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Specific Tab Filters -->
                    <?php if ($tab === 'incidents'): ?>
                        <div class="col-md-3">
                            <label class="form-label text-muted fs-7 mb-1">Type d'Incident</label>
                            <select name="type_incident_id" class="form-select form-select-sm">
                                <option value="">Tous les types</option>
                                <?php foreach ($typeIncidents as $ti): ?>
                                    <option value="<?= $ti['id'] ?>" <?= ((int)($queryParams['type_incident_id'] ?? 0) === (int)$ti['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ti['libelle']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label text-muted fs-7 mb-1">Gravité</label>
                            <select name="niveau_gravite" class="form-select form-select-sm">
                                <option value="">Toutes</option>
                                <option value="Faible" <?= ($queryParams['niveau_gravite'] ?? '') === 'Faible' ? 'selected' : '' ?>>Faible</option>
                                <option value="Moyen" <?= ($queryParams['niveau_gravite'] ?? '') === 'Moyen' ? 'selected' : '' ?>>Moyen</option>
                                <option value="Grave" <?= ($queryParams['niveau_gravite'] ?? '') === 'Grave' ? 'selected' : '' ?>>Grave</option>
                                <option value="Très grave" <?= ($queryParams['niveau_gravite'] ?? '') === 'Très grave' ? 'selected' : '' ?>>Très grave</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label text-muted fs-7 mb-1">Statut Incident</label>
                            <select name="statut" class="form-select form-select-sm">
                                <option value="">Tous</option>
                                <option value="signale" <?= ($queryParams['statut'] ?? '') === 'signale' ? 'selected' : '' ?>>Signalé</option>
                                <option value="en_instruction" <?= ($queryParams['statut'] ?? '') === 'en_instruction' ? 'selected' : '' ?>>En instruction</option>
                                <option value="traite" <?= ($queryParams['statut'] ?? '') === 'traite' ? 'selected' : '' ?>>Traité</option>
                                <option value="classe_sans_suite" <?= ($queryParams['statut'] ?? '') === 'classe_sans_suite' ? 'selected' : '' ?>>Classé sans suite</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label text-muted fs-7 mb-1">Rôle de l'élève</label>
                            <select name="role_implication" class="form-select form-select-sm">
                                <option value="">Tous les rôles</option>
                                <?php foreach ($roleLabels as $key => $lbl): ?>
                                    <option value="<?= $key ?>" <?= ($queryParams['role_implication'] ?? '') === $key ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="col-md-3">
                            <label class="form-label text-muted fs-7 mb-1">Type de Sanction</label>
                            <select name="type_sanction_id" class="form-select form-select-sm">
                                <option value="">Tous les types</option>
                                <?php foreach ($typeSanctions as $ts): ?>
                                    <option value="<?= $ts['id'] ?>" <?= ((int)($queryParams['type_sanction_id'] ?? 0) === (int)$ts['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ts['libelle']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label text-muted fs-7 mb-1">Statut Sanction</label>
                            <select name="statut" class="form-select form-select-sm">
                                <option value="">Tous</option>
                                <option value="prononcee" <?= ($queryParams['statut'] ?? '') === 'prononcee' ? 'selected' : '' ?>>Prononcée</option>
                                <option value="en_cours" <?= ($queryParams['statut'] ?? '') === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                                <option value="executee" <?= ($queryParams['statut'] ?? '') === 'executee' ? 'selected' : '' ?>>Exécutée</option>
                                <option value="levee" <?= ($queryParams['statut'] ?? '') === 'levee' ? 'selected' : '' ?>>Levée</option>
                                <option value="annulee" <?= ($queryParams['statut'] ?? '') === 'annulee' ? 'selected' : '' ?>>Annulée</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-2">
                        <label class="form-label text-muted fs-7 mb-1">Date Début</label>
                        <input type="date" name="date_debut" class="form-control form-control-sm" value="<?= htmlspecialchars($queryParams['date_debut'] ?? '') ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label text-muted fs-7 mb-1">Date Fin</label>
                        <input type="date" name="date_fin" class="form-control form-control-sm" value="<?= htmlspecialchars($queryParams['date_fin'] ?? '') ?>">
                    </div>

                    <div class="col-md-3 d-flex gap-1 ms-auto">
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="ph-duotone ph-magnifying-glass me-1"></i>Rechercher
                        </button>
                        <a href="/discipline/search?tab=<?= $tab ?>" class="btn btn-sm btn-outline-secondary" title="Réinitialiser les filtres">
                            <i class="ph-duotone ph-arrow-counter-clockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ligne Synthèse Visuelle (KPI Cards) -->
        <div class="row mb-4">
            <div class="col-md-2-4 col-sm-6 mb-2">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3 text-center">
                        <span class="text-muted fs-7 fw-semibold d-block mb-1">Incidents Totaux</span>
                        <h4 class="mb-0 text-primary fw-bold"><?= number_format($analytics['totalIncidents']) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-2-4 col-sm-6 mb-2">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3 text-center">
                        <span class="text-muted fs-7 fw-semibold d-block mb-1">Élèves Impliqués</span>
                        <h4 class="mb-0 text-info fw-bold"><?= number_format($analytics['elevesImpliques']) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-2-4 col-sm-6 mb-2">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3 text-center">
                        <span class="text-muted fs-7 fw-semibold d-block mb-1">Élèves Responsables</span>
                        <h4 class="mb-0 text-warning fw-bold"><?= number_format($analytics['elevesResponsables']) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-2-4 col-sm-6 mb-2">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3 text-center">
                        <span class="text-muted fs-7 fw-semibold d-block mb-1">Élèves Récidivistes</span>
                        <h4 class="mb-0 text-danger fw-bold"><?= number_format($analytics['elevesRecidivistes']) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-2-4 col-sm-6 mb-2">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3 text-center">
                        <span class="text-muted fs-7 fw-semibold d-block mb-1">Sanctions Totales</span>
                        <h4 class="mb-0 text-success fw-bold"><?= number_format($analytics['totalSanctions']) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau des Résultats Filtrés -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="ph-duotone ph-list-numbers me-2 text-primary"></i>
                    Résultats du Registre (<?= number_format($dataset['total']) ?> ligne<?= $dataset['total'] > 1 ? 's' : '' ?>)
                </h5>
                <span class="badge bg-light-primary text-primary">Page <?= $dataset['page'] ?> / <?= max(1, $dataset['total_pages']) ?></span>
            </div>

            <div class="card-body p-0">
                <?php if (empty($dataset['items'])): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="ph-duotone ph-file-x fs-1 text-secondary mb-2 d-block"></i>
                        Aucun résultat ne correspond aux critères de recherche spécifiés.
                    </div>
                <?php else: ?>
                    <?php if ($tab === 'incidents'): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Réf. / Date</th>
                                        <th>Élève Concerné</th>
                                        <th>Classe</th>
                                        <th>Type & Gravité</th>
                                        <th>Rôle</th>
                                        <th>Statut</th>
                                        <th>Signalé Par</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dataset['items'] as $item): ?>
                                        <tr>
                                            <td>
                                                <strong class="d-block text-dark"><?= htmlspecialchars($item['incident_code']) ?></strong>
                                                <small class="text-muted"><i class="ph-duotone ph-calendar me-1"></i><?= date('d/m/Y', strtotime($item['date_incident'])) ?></small>
                                            </td>
                                            <td>
                                                <strong class="d-block text-dark"><?= htmlspecialchars($item['eleve_nom_complet']) ?></strong>
                                                <small class="text-muted">Matricule : <?= htmlspecialchars($item['eleve_matricule']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light-primary text-primary fw-bold"><?= htmlspecialchars($item['nom_classe_snapshot'] ?? 'Inconnue') ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-dark"><?= htmlspecialchars($item['type_incident_libelle']) ?></span>
                                                <small class="d-block text-muted">Gravité : <?= htmlspecialchars($item['niveau_gravite']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?= $roleBadges[$item['role_implication']] ?? 'bg-secondary' ?>">
                                                    <?= htmlspecialchars($roleLabels[$item['role_implication']] ?? $item['role_implication']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $incStatutBadges[$item['incident_statut']] ?? 'bg-secondary' ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $item['incident_statut'])) ?>
                                                </span>
                                            </td>
                                            <td><small class="text-muted"><?= htmlspecialchars($item['signale_par_nom'] ?? 'Système') ?></small></td>
                                            <td class="text-end">
                                                <a href="/discipline/incidents/show?id=<?= $item['incident_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="ph-duotone ph-eye me-1"></i>Voir
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date Décision</th>
                                        <th>Élève</th>
                                        <th>Classe</th>
                                        <th>Type Sanction</th>
                                        <th>Période Exécution</th>
                                        <th>Statut</th>
                                        <th>Prononcée Par</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dataset['items'] as $item): ?>
                                        <tr>
                                            <td>
                                                <strong class="d-block text-dark"><?= date('d/m/Y', strtotime($item['date_decision'])) ?></strong>
                                                <?php if (!empty($item['incident_code'])): ?>
                                                    <small class="text-muted">Incident : <?= htmlspecialchars($item['incident_code']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong class="d-block text-dark"><?= htmlspecialchars($item['eleve_nom_complet']) ?></strong>
                                                <small class="text-muted">Matricule : <?= htmlspecialchars($item['eleve_matricule']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light-primary text-primary fw-bold"><?= htmlspecialchars($item['nom_classe_snapshot'] ?? 'Inconnue') ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark"><?= htmlspecialchars($item['type_sanction_libelle']) ?></span>
                                            </td>
                                            <td>
                                                <?php if (!empty($item['date_debut_execution'])): ?>
                                                    <small class="d-block text-muted"><?= date('d/m/Y', strtotime($item['date_debut_execution'])) ?> - <?= date('d/m/Y', strtotime($item['date_fin_execution'])) ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">Non planifiée</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $sanctStatutBadges[$item['sanction_statut']] ?? 'bg-secondary' ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $item['sanction_statut'])) ?>
                                                </span>
                                            </td>
                                            <td><small class="text-muted"><?= htmlspecialchars($item['prononcee_par_nom'] ?? 'Système') ?></small></td>
                                            <td class="text-end">
                                                <a href="/discipline/sanctions/show?id=<?= $item['sanction_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="ph-duotone ph-eye me-1"></i>Voir
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination Server-side -->
            <?php if ($dataset['total_pages'] > 1): ?>
                <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                    <small class="text-muted">Affichage de <?= count($dataset['items']) ?> sur <?= $dataset['total'] ?> enregistrements</small>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($p = 1; $p <= $dataset['total_pages']; $p++): ?>
                            <li class="page-item <?= ($p === $dataset['page']) ? 'active' : '' ?>">
                                <a class="page-link" href="/discipline/search?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>">
                                    <?= $p ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
