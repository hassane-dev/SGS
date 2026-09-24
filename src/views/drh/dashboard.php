<?php
// src/views/drh/dashboard.php
require_once __DIR__ . '/../layouts/header_able.php';

$selectedPeriode = $selected_periode ?? null;
$periodesOptions = $periodes_options ?? [];
$effectifRhActif = $effectif_rh_actif ?? 0;
$effectifByRole = $effectif_by_role ?? [];
$eligibilitePaie = $eligibilite_paie ?? [];
$finSummary = $financial_summary ?? [];
$g1Trend = $g1_trend_6m ?? [];
$g2EmployerCost = $g2_employer_cost ?? [];
$g2DeductionsBreakdown = $g2_deductions_breakdown ?? [];
$g3Pipeline = $g3_service_fait_pipeline ?? [];
$g4Deadlines = $g4_contract_deadlines ?? [];

$canViewRh = Auth::can('view_all', 'drh') || Auth::can('view_one', 'drh');
$canViewPaie = Auth::can('view', 'paie');
$canCreateRh = Auth::can('create', 'drh');
$canManageContrats = Auth::can('manage_contrats', 'drh') || Auth::can('create_contracts', 'drh');
$canValidateService = Auth::can('validate', 'paie');
$canCalculatePaie = Auth::can('calculate', 'paie');
$canSettlePaie = Auth::can('settle', 'paie');
?>

<div class="pc-container">
    <div class="pc-content">

        <!-- HEADER & PAGE TITLE -->
        <div class="page-header mb-4">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <div class="page-header-title">
                            <h2 class="mb-0 text-primary d-flex align-items-center gap-2">
                                <i class="ph-duotone ph-chart-line-up fs-1"></i>
                                <?= _('Hub Opérationnel RH & Paie') ?>
                            </h2>
                        </div>
                        <p class="text-muted mb-0 mt-1">
                            <?= _('Porte d\'entrée principale et pilotage à 360° du personnel, de la rémunération et de la trésorerie.') ?>
                        </p>
                    </div>
                    <div class="col-md-5 text-md-end mt-3 mt-md-0">
                        <form method="GET" action="/drh/dashboard" class="d-inline-flex gap-2 align-items-center justify-content-end">
                            <label for="periode_id_select" class="form-label mb-0 fw-bold small text-muted text-nowrap"><?= _('Période de Paie :') ?></label>
                            <select name="periode_id" id="periode_id_select" class="form-select form-select-sm auto-submit-select" style="min-width: 220px;" onchange="this.form.submit()">
                                <?php foreach ($periodesOptions as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= ($selectedPeriode && (int)$selectedPeriode['id'] === (int)$p['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['code_periode']) ?> (<?= sprintf('%02d/%d', $p['mois'], $p['annee']) ?>) - <?= ucfirst($p['statut']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACTION HUB / SHORTCUTS (RBAC-GATED) -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body p-3 bg-light-primary rounded-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="fw-bold text-primary d-flex align-items-center gap-2">
                        <i class="ph-duotone ph-lightning fs-4"></i>
                        <span><?= _('Raccourcis Opérationnels :') ?></span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($canCreateRh): ?>
                            <a href="/drh/create" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                                <i class="ph-bold ph-user-plus"></i> <?= _('Nouveau Membre') ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($canManageContrats): ?>
                            <a href="/drh" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                                <i class="ph-bold ph-file-text"></i> <?= _('Contrat / Avenant') ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($canValidateService): ?>
                            <a href="/paie/cahier-texte" class="btn btn-sm btn-outline-info d-inline-flex align-items-center gap-1">
                                <i class="ph-bold ph-check-square"></i> <?= _('Valider Heures') ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($canCalculatePaie): ?>
                            <a href="/paie/bulletins/prepare?periode_id=<?= $selectedPeriode['id'] ?? '' ?>" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1">
                                <i class="ph-bold ph-calculator"></i> <?= _('Préparer la Paie') ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($canViewPaie): ?>
                            <a href="/paie/bulletins?periode_id=<?= $selectedPeriode['id'] ?? '' ?>" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                                <i class="ph-bold ph-receipt"></i> <?= _('Bulletins') ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($canSettlePaie): ?>
                            <a href="/paie/historique?statut_reglement=non_paye" class="btn btn-sm btn-warning d-inline-flex align-items-center gap-1 text-dark">
                                <i class="ph-bold ph-currency-circle-dollar"></i> <?= _('Exécuter les Règlements') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- BLOC 1: SUMMARY KPI CARDS -->
        <div class="row g-3 mb-4">
            <!-- KPI 1: Effectif RH Actif -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted fw-bold small uppercase tracking-wider"><?= _('Effectif RH Actif') ?></span>
                            <div class="avtar avtar-sm bg-light-primary text-primary rounded-circle">
                                <i class="ph-duotone ph-users-three fs-4"></i>
                            </div>
                        </div>
                        <h2 class="mb-1 fw-bold text-dark"><?= number_format($effectifRhActif) ?></h2>
                        <div class="small text-muted d-flex align-items-center gap-1">
                            <i class="ph-bold ph-check-circle text-success"></i>
                            <span><?= _('Contrats actifs à ce jour') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI 2: Salariés Éligibles Paie -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted fw-bold small uppercase tracking-wider"><?= _('Salariés Éligibles') ?></span>
                            <div class="avtar avtar-sm bg-light-info text-info rounded-circle">
                                <i class="ph-duotone ph-user-check fs-4"></i>
                            </div>
                        </div>
                        <h2 class="mb-1 fw-bold text-info"><?= number_format($eligibilitePaie['total_eligibles'] ?? 0) ?></h2>
                        <div class="small text-muted d-flex align-items-center justify-content-between">
                            <span><?= sprintf(_('%d calculables'), $eligibilitePaie['calculables'] ?? 0) ?></span>
                            <span class="badge bg-light-success text-success"><?= sprintf(_('%d bulletins'), $eligibilitePaie['bulletin_existant'] ?? 0) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI 3: Masse Salariale Net à Payer -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted fw-bold small uppercase tracking-wider"><?= _('Masse Salariale Nette') ?></span>
                            <div class="avtar avtar-sm bg-light-success text-success rounded-circle">
                                <i class="ph-duotone ph-money fs-4"></i>
                            </div>
                        </div>
                        <h2 class="mb-1 fw-bold text-success"><?= number_format($finSummary['net_a_payer'] ?? 0, 0, ',', ' ') ?> <small class="fs-6 text-muted">FCFA</small></h2>
                        <div class="small text-muted d-flex align-items-center justify-content-between">
                            <span><?= _('Coût total employeur :') ?></span>
                            <span class="fw-bold text-dark"><?= number_format($finSummary['cout_total_employeur'] ?? 0, 0, ',', ' ') ?> FCFA</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI 4: Règlement Effectif vs Restant -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted fw-bold small uppercase tracking-wider"><?= _('Décaissements Réels') ?></span>
                            <div class="avtar avtar-sm bg-light-warning text-warning rounded-circle">
                                <i class="ph-duotone ph-wallet fs-4"></i>
                            </div>
                        </div>
                        <h2 class="mb-1 fw-bold text-warning"><?= number_format($finSummary['montant_effectivement_regle'] ?? 0, 0, ',', ' ') ?> <small class="fs-6 text-muted">FCFA</small></h2>
                        <div class="small text-muted d-flex align-items-center justify-content-between">
                            <span><?= sprintf(_('%d payés sur %d'), $finSummary['bulletins_payes'] ?? 0, $finSummary['total_bulletins'] ?? 0) ?></span>
                            <span class="fw-bold text-danger"><?= sprintf(_('Reste: %s FCFA'), number_format($finSummary['reste_a_regler'] ?? 0, 0, ',', ' ')) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BLOC 2: CHARTS ROW 1 (G1 & G2) -->
        <div class="row g-3 mb-4">
            <!-- G1: Historical 6-Month Trend -->
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between pb-0">
                        <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                            <i class="ph-duotone ph-trend-up text-primary fs-4"></i>
                            <?= _('G1 — Évolution Salariale & Coût Employeur (6 Derniers Mois)') ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="chart-g1-trend" style="min-height: 310px;"></div>
                    </div>
                </div>
            </div>

            <!-- G2: Employer Cost Breakdown (Additive) -->
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between pb-0">
                        <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                            <i class="ph-duotone ph-chart-pie text-success fs-4"></i>
                            <?= _('G2 — Décomposition Coût Employeur (Mois Actif)') ?>
                        </h5>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-center">
                        <div id="chart-g2-cost" style="min-height: 250px;"></div>
                        <div class="mt-3 pt-2 border-top text-center text-muted small">
                            <span><?= _('Total Coût Employeur :') ?> </span>
                            <strong class="text-dark fs-6"><?= number_format($g2EmployerCost['cout_total_employeur'] ?? 0, 0, ',', ' ') ?> FCFA</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BLOC 3: CHARTS ROW 2 (G3 & G4) -->
        <div class="row g-3 mb-4">
            <!-- G3: Service Fait Pipeline -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between pb-0">
                        <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                            <i class="ph-duotone ph-chalkboard-teacher text-info fs-4"></i>
                            <?= _('G3 — Suivi du Service Fait Enseignant') ?>
                        </h5>
                        <a href="/paie/cahier-texte" class="btn btn-link btn-sm p-0 text-decoration-none"><?= _('Détails') ?> &rarr;</a>
                    </div>
                    <div class="card-body">
                        <div id="chart-g3-service-fait" style="min-height: 220px;"></div>
                        <div class="row text-center mt-3 pt-2 border-top">
                            <div class="col-4 border-end">
                                <div class="text-muted small mb-1"><?= _('Heures Réalisées') ?></div>
                                <div class="fw-bold text-dark fs-5"><?= number_format($g3Pipeline['heures_realisees'] ?? 0, 1) ?> h</div>
                            </div>
                            <div class="col-4 border-end">
                                <div class="text-muted small mb-1"><?= _('Heures Validées') ?></div>
                                <div class="fw-bold text-info fs-5"><?= number_format($g3Pipeline['heures_validees'] ?? 0, 1) ?> h</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted small mb-1"><?= _('Intégrées Bulletin') ?></div>
                                <div class="fw-bold text-success fs-5"><?= number_format($g3Pipeline['heures_integrees'] ?? 0, 1) ?> h</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- G4: Contract Expiration Deadlines -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between pb-0">
                        <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                            <i class="ph-duotone ph-clock-afternoon text-warning fs-4"></i>
                            <?= _('G4 — Suivi des Échéances Contrats CDD') ?>
                        </h5>
                        <a href="/drh" class="btn btn-link btn-sm p-0 text-decoration-none"><?= _('Registre DRH') ?> &rarr;</a>
                    </div>
                    <div class="card-body">
                        <!-- Navigation Tabs for Deadlines -->
                        <ul class="nav nav-pills nav-fill mb-3 bg-light rounded p-1" id="pills-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active py-1 small fw-bold" id="tab-exp-tab" data-bs-toggle="pill" data-bs-target="#tab-exp" type="button" role="tab">
                                    <i class="ph-bold ph-warning-circle text-danger"></i> <?= _('Expirés') ?> (<?= $g4Deadlines['expires_count'] ?? 0 ?>)
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-1 small fw-bold" id="tab-30-tab" data-bs-toggle="pill" data-bs-target="#tab-30" type="button" role="tab">
                                    <i class="ph-bold ph-alarm text-warning"></i> <?= _('<= 30 jours') ?> (<?= $g4Deadlines['exp30_count'] ?? 0 ?>)
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-1 small fw-bold" id="tab-60-tab" data-bs-toggle="pill" data-bs-target="#tab-60" type="button" role="tab">
                                    <i class="ph-bold ph-calendar text-info"></i> <?= _('31 - 60 jours') ?> (<?= $g4Deadlines['exp60_count'] ?? 0 ?>)
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="pills-tabContent">
                            <!-- Tab: Expired -->
                            <div class="tab-pane fade show active" id="tab-exp" role="tabpanel">
                                <?php if (empty($g4Deadlines['expires_list'])): ?>
                                    <div class="text-center py-4 text-muted small">
                                        <i class="ph-duotone ph-check-circle fs-1 text-success d-block mb-1"></i>
                                        <?= _('Aucun contrat actif expiré à régulariser.') ?>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th><?= _('Salarié') ?></th>
                                                    <th><?= _('Contrat') ?></th>
                                                    <th><?= _('Fin prévu') ?></th>
                                                    <th class="text-end"><?= _('Action') ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($g4Deadlines['expires_list'] as $c): ?>
                                                    <tr>
                                                        <td class="fw-bold"><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></td>
                                                        <td><span class="badge bg-light-danger text-danger"><?= htmlspecialchars($c['contrat_libelle'] ?? 'CDD') ?></span></td>
                                                        <td class="text-danger fw-bold"><?= date('d/m/Y', strtotime($c['date_fin'])) ?></td>
                                                        <td class="text-end">
                                                            <a href="/drh/show?id=<?= $c['personnel_id'] ?>" class="btn btn-icon btn-sm btn-light-primary"><i class="ph-bold ph-pencil-simple"></i></a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Tab: <= 30 Days -->
                            <div class="tab-pane fade" id="tab-30" role="tabpanel">
                                <?php if (empty($g4Deadlines['exp30_list'])): ?>
                                    <div class="text-center py-4 text-muted small">
                                        <i class="ph-duotone ph-check-circle fs-1 text-success d-block mb-1"></i>
                                        <?= _('Aucun contrat arrivant à échéance sous 30 jours.') ?>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th><?= _('Salarié') ?></th>
                                                    <th><?= _('Contrat') ?></th>
                                                    <th><?= _('Date d\'échéance') ?></th>
                                                    <th class="text-end"><?= _('Action') ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($g4Deadlines['exp30_list'] as $c): ?>
                                                    <tr>
                                                        <td class="fw-bold"><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></td>
                                                        <td><span class="badge bg-light-warning text-warning"><?= htmlspecialchars($c['contrat_libelle'] ?? 'CDD') ?></span></td>
                                                        <td class="text-warning fw-bold"><?= date('d/m/Y', strtotime($c['date_fin'])) ?></td>
                                                        <td class="text-end">
                                                            <a href="/drh/show?id=<?= $c['personnel_id'] ?>" class="btn btn-icon btn-sm btn-light-primary"><i class="ph-bold ph-pencil-simple"></i></a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Tab: 31-60 Days -->
                            <div class="tab-pane fade" id="tab-60" role="tabpanel">
                                <?php if (empty($g4Deadlines['exp60_list'])): ?>
                                    <div class="text-center py-4 text-muted small">
                                        <i class="ph-duotone ph-check-circle fs-1 text-success d-block mb-1"></i>
                                        <?= _('Aucun contrat arrivant à échéance entre 31 et 60 jours.') ?>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th><?= _('Salarié') ?></th>
                                                    <th><?= _('Contrat') ?></th>
                                                    <th><?= _('Date d\'échéance') ?></th>
                                                    <th class="text-end"><?= _('Action') ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($g4Deadlines['exp60_list'] as $c): ?>
                                                    <tr>
                                                        <td class="fw-bold"><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></td>
                                                        <td><span class="badge bg-light-info text-info"><?= htmlspecialchars($c['contrat_libelle'] ?? 'CDD') ?></span></td>
                                                        <td class="text-info fw-bold"><?= date('d/m/Y', strtotime($c['date_fin'])) ?></td>
                                                        <td class="text-end">
                                                            <a href="/drh/show?id=<?= $c['personnel_id'] ?>" class="btn btn-icon btn-sm btn-light-primary"><i class="ph-bold ph-pencil-simple"></i></a>
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

        <!-- BLOC 4: PIPELINE DE PAIE DE LA PÉRIODE (VISUALISATION DU WORKFLOW) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                    <i class="ph-duotone ph-git-commit text-primary fs-4"></i>
                    <?= _('Progression du Workflow de Paie du Mois') ?> (<?= htmlspecialchars($selectedPeriode['code_periode'] ?? 'Période') ?>)
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center g-3 align-items-center">
                    <!-- Step 1: Service Fait -->
                    <div class="col-12 col-md-3">
                        <div class="p-3 bg-light rounded-3 h-100 border border-info border-2">
                            <span class="badge bg-info mb-2"><?= _('Étape 1') ?></span>
                            <h6 class="fw-bold mb-1"><?= _('Service Fait') ?></h6>
                            <div class="text-muted small"><?= sprintf(_('%s / %s h validées'), number_format($g3Pipeline['heures_validees'] ?? 0, 0), number_format($g3Pipeline['heures_realisees'] ?? 0, 0)) ?></div>
                        </div>
                    </div>

                    <!-- Step 2: Préparation & Calcul -->
                    <div class="col-12 col-md-3">
                        <div class="p-3 bg-light rounded-3 h-100 border border-primary border-2">
                            <span class="badge bg-primary mb-2"><?= _('Étape 2') ?></span>
                            <h6 class="fw-bold mb-1"><?= _('Calcul Bulletins') ?></h6>
                            <div class="text-muted small"><?= sprintf(_('%d calculés sur %d'), $finSummary['total_bulletins'] ?? 0, $eligibilitePaie['total_eligibles'] ?? 0) ?></div>
                        </div>
                    </div>

                    <!-- Step 3: Validation & Comptabilisation -->
                    <div class="col-12 col-md-3">
                        <div class="p-3 bg-light rounded-3 h-100 border border-success border-2">
                            <span class="badge bg-success mb-2"><?= _('Étape 3') ?></span>
                            <h6 class="fw-bold mb-1"><?= _('Validation') ?></h6>
                            <div class="text-muted small"><?= sprintf(_('%d bulletins validés'), $finSummary['bulletins_valides'] ?? 0) ?></div>
                        </div>
                    </div>

                    <!-- Step 4: Règlement & Trésorerie -->
                    <div class="col-12 col-md-3">
                        <div class="p-3 bg-light rounded-3 h-100 border border-warning border-2">
                            <span class="badge bg-warning text-dark mb-2"><?= _('Étape 4') ?></span>
                            <h6 class="fw-bold mb-1"><?= _('Règlement') ?></h6>
                            <div class="text-muted small"><?= sprintf(_('%d payés (%s FCFA)'), $finSummary['bulletins_payes'] ?? 0, number_format($finSummary['montant_effectivement_regle'] ?? 0, 0, ',', ' ')) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ApexCharts Integration Script -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // --- G1 Chart: 6-Month Trend ---
    const g1Categories = <?= json_encode(array_column($g1Trend, 'code_periode')) ?>;
    const g1NetData = <?= json_encode(array_map('floatval', array_column($g1Trend, 'net_a_payer'))) ?>;
    const g1RegleData = <?= json_encode(array_map('floatval', array_column($g1Trend, 'montant_effectivement_regle'))) ?>;
    const g1CoutEmployerData = <?= json_encode(array_map('floatval', array_column($g1Trend, 'cout_total_employeur'))) ?>;

    const optionsG1 = {
        series: [
            { name: '<?= _("Coût Total Employeur") ?>', data: g1CoutEmployerData },
            { name: '<?= _("Net à Payer Officiel") ?>', data: g1NetData },
            { name: '<?= _("Décaissement Réel Trésorerie") ?>', data: g1RegleData }
        ],
        chart: { type: 'bar', height: 310, toolbar: { show: false } },
        colors: ['#4680ff', '#2ca87f', '#e58a00'],
        plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 4 } },
        dataLabels: { enabled: false },
        stroke: { show: true, width: 2, colors: ['transparent'] },
        xaxis: { categories: g1Categories },
        yaxis: { labels: { formatter: (val) => val.toLocaleString() + ' FCFA' } },
        fill: { opacity: 1 },
        tooltip: { y: { formatter: (val) => val.toLocaleString() + ' FCFA' } }
    };
    new ApexCharts(document.querySelector("#chart-g1-trend"), optionsG1).render();

    // --- G2 Chart: Employer Cost Additive Breakdown ---
    const g2EmployerData = [
        <?= (float)($g2EmployerCost['salaire_base'] ?? 0) ?>,
        <?= (float)($g2EmployerCost['primes_indemnites_heures'] ?? 0) ?>,
        <?= (float)($g2EmployerCost['cotisations_patronales'] ?? 0) ?>
    ];

    const optionsG2 = {
        series: g2EmployerData,
        labels: ['<?= _("Salaire de Base") ?>', '<?= _("Primes & Heures") ?>', '<?= _("Cotisations Patronales") ?>'],
        chart: { type: 'donut', height: 250 },
        colors: ['#4680ff', '#13c2c2', '#e58a00'],
        legend: { position: 'bottom' },
        dataLabels: { enabled: true, formatter: (val) => val.toFixed(1) + '%' },
        tooltip: { y: { formatter: (val) => val.toLocaleString() + ' FCFA' } }
    };
    new ApexCharts(document.querySelector("#chart-g2-cost"), optionsG2).render();

    // --- G3 Chart: Service Fait Pipeline ---
    const g3Data = [
        <?= (float)($g3Pipeline['heures_realisees'] ?? 0) ?>,
        <?= (float)($g3Pipeline['heures_validees'] ?? 0) ?>,
        <?= (float)($g3Pipeline['heures_integrees'] ?? 0) ?>
    ];

    const optionsG3 = {
        series: [{ name: '<?= _("Heures Pédagogiques") ?>', data: g3Data }],
        chart: { type: 'bar', height: 220, toolbar: { show: false } },
        colors: ['#13c2c2'],
        plotOptions: { bar: { borderRadius: 4, horizontal: true, barHeight: '50%' } },
        dataLabels: { enabled: true, formatter: (val) => val.toFixed(1) + ' h' },
        xaxis: { categories: ['<?= _("1. Réalisées (Cahier)") ?>', '<?= _("2. Validées (Service Fait)") ?>', '<?= _("3. Intégrées (Bulletin)") ?>'] }
    };
    new ApexCharts(document.querySelector("#chart-g3-service-fait"), optionsG3).render();

});
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
