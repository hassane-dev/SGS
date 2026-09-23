<?php
// src/views/treasury/dashboard.php
require_once __DIR__ . '/../../views/layouts/header_able.php';

$d = $dashboardData ?? [];
$kpis = $d['kpis'] ?? [];
$sess = $d['sessions'] ?? [];
$chart = $d['chart'] ?? [];
$caisses = $d['caisses'] ?? [];
$hub = $d['actions_hub'] ?? [];
$filters = $filters ?? [];

$period = $filters['period'] ?? 'today';
$selectedCompteId = $filters['compte_id'] ?? null;
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- Page Header & Breadcrumb -->
        <div class="page-header mb-4">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-header-title">
                            <h2 class="mb-0">
                                <i class="ph-duotone ph-vault text-primary me-2"></i>
                                <?= _("Hub Caisse & Trésorerie") ?>
                            </h2>
                        </div>
                        <ul class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="/dashboard"><i class="ph-duotone ph-house"></i> <?= _("Accueil") ?></a></li>
                            <li class="breadcrumb-item"><?= _("Finances & Comptabilité") ?></li>
                            <li class="breadcrumb-item active"><?= _("Hub Caisse & Trésorerie") ?></li>
                        </ul>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <?php if (!empty($hub['has_active_session'])): ?>
                            <a href="/treasury/sessions/show/<?= (int)$hub['active_session_id'] ?>" class="btn btn-success shadow-sm">
                                <i class="ph-duotone ph-lock-key me-1"></i> <?= _("Ma Session Ouverte") ?>
                            </a>
                        <?php elseif (!empty($hub['can_open_session'])): ?>
                            <a href="/treasury/sessions/open" class="btn btn-primary shadow-sm">
                                <i class="ph-duotone ph-plus-circle me-1"></i> <?= _("Ouvrir une Session") ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ZONE A: FILTERS & CONTROL BAR -->
        <div class="card mb-4 shadow-sm border-top border-primary border-3">
            <div class="card-body p-3">
                <form method="GET" action="/treasury/dashboard" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label mb-1 fw-bold small text-muted"><i class="ph-duotone ph-calendar me-1"></i><?= _("Période") ?></label>
                        <select name="period" id="filter-period" class="form-select form-select-sm" onchange="toggleCustomDates(this.value)">
                            <option value="today" <?= $period === 'today' ? 'selected' : '' ?>><?= _("Aujourd'hui") ?></option>
                            <option value="7days" <?= $period === '7days' ? 'selected' : '' ?>><?= _("7 derniers jours") ?></option>
                            <option value="30days" <?= $period === '30days' ? 'selected' : '' ?>><?= _("30 derniers jours") ?></option>
                            <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>><?= _("Période personnalisée") ?></option>
                        </select>
                    </div>

                    <div class="col-md-2 custom-date-field" style="display: <?= $period === 'custom' ? 'block' : 'none' ?>;">
                        <label class="form-label mb-1 fw-bold small text-muted"><?= _("Date début") ?></label>
                        <input type="date" name="date_debut" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_debut'] ?? '') ?>">
                    </div>

                    <div class="col-md-2 custom-date-field" style="display: <?= $period === 'custom' ? 'block' : 'none' ?>;">
                        <label class="form-label mb-1 fw-bold small text-muted"><?= _("Date fin") ?></label>
                        <input type="date" name="date_fin" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_fin'] ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label mb-1 fw-bold small text-muted"><i class="ph-duotone ph-bank me-1"></i><?= _("Caisse") ?></label>
                        <select name="compte_id" class="form-select form-select-sm">
                            <option value=""><?= _("Toutes les caisses") ?></option>
                            <?php foreach ($caissesList as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= (int)$selectedCompteId === (int)$c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nom_compte']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end gap-1 mt-3 mt-md-0">
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="ph-duotone ph-funnel me-1"></i><?= _("Filtrer") ?>
                        </button>
                        <a href="/treasury/dashboard" class="btn btn-sm btn-light-secondary" title="<?= _("Réinitialiser") ?>">
                            <i class="ph-duotone ph-arrows-counter-clockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- ZONE B: PRIMARY KPI CARDS -->
        <div class="row g-3 mb-4">
            <!-- KPI 1: Solde courant des caisses -->
            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1 text-uppercase fw-semibold fs-7"><?= _("Solde courant des caisses") ?></p>
                                <h3 class="mb-0 text-primary fw-bold"><?= number_format($kpis['solde_caisses'] ?? 0, 0, ',', ' ') ?> <small class="fs-6 text-muted">FCFA</small></h3>
                                <small class="text-muted d-block mt-1 fs-8"><?= _("Liquide total détenu en caisses") ?></small>
                            </div>
                            <div class="avatar avatar-md bg-light-primary text-primary rounded-circle">
                                <i class="ph-duotone ph-wallet fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI 2: Encaissements de la période -->
            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1 text-uppercase fw-semibold fs-7"><?= _("Encaissements (Net)") ?></p>
                                <h3 class="mb-0 text-success fw-bold">+<?= number_format($kpis['encaissement_periode'] ?? 0, 0, ',', ' ') ?> <small class="fs-6 text-muted">FCFA</small></h3>
                                <small class="text-muted d-block mt-1 fs-8"><?= _("Recettes opérationnelles nettes") ?></small>
                            </div>
                            <div class="avatar avatar-md bg-light-success text-success rounded-circle">
                                <i class="ph-duotone ph-arrow-circle-down-left fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI 3: Décaissements de la période -->
            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm h-100 border-start border-danger border-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1 text-uppercase fw-semibold fs-7"><?= _("Décaissements") ?></p>
                                <h3 class="mb-0 text-danger fw-bold">-<?= number_format($kpis['decaissement_periode'] ?? 0, 0, ',', ' ') ?> <small class="fs-6 text-muted">FCFA</small></h3>
                                <small class="text-muted d-block mt-1 fs-8"><?= _("Règlements dépenses & fournisseurs") ?></small>
                            </div>
                            <div class="avatar avatar-md bg-light-danger text-danger rounded-circle">
                                <i class="ph-duotone ph-arrow-circle-up-right fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI 4: Remis au coffre (période) -->
            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1 text-uppercase fw-semibold fs-7"><?= _("Remis au Coffre") ?></p>
                                <h3 class="mb-0 text-warning fw-bold"><?= number_format($kpis['remis_coffre_periode'] ?? 0, 0, ',', ' ') ?> <small class="fs-6 text-muted">FCFA</small></h3>
                                <small class="text-muted d-block mt-1 fs-8"><?= sprintf(_("Solde coffre : %s FCFA"), number_format($kpis['solde_coffre'] ?? 0, 0, ',', ' ')) ?></small>
                            </div>
                            <div class="avatar avatar-md bg-light-warning text-warning rounded-circle">
                                <i class="ph-duotone ph-vault fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ZONE C: SITUATION DES SESSIONS & ALERTS GRID -->
        <div class="row g-3 mb-4">
            <!-- Card 1: Sessions Ouvertes -->
            <div class="col-md-4">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-header bg-light-success d-flex justify-content-between align-items-center py-2">
                        <span class="fw-bold text-success"><i class="ph-duotone ph-lock-key-open me-1"></i><?= _("Sessions Ouvertes") ?></span>
                        <span class="badge bg-success rounded-pill"><?= (int)($sess['ouvertes']['count'] ?? 0) ?></span>
                    </div>
                    <div class="card-body p-3">
                        <?php if (empty($sess['ouvertes']['list'])): ?>
                            <p class="text-muted fs-7 mb-0 text-center py-2"><i class="ph-duotone ph-info me-1"></i><?= _("Aucune session ouverte actuellement.") ?></p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($sess['ouvertes']['list'] as $sRow): ?>
                                    <a href="/treasury/sessions/show/<?= (int)$sRow['id'] ?>" class="list-group-item list-group-item-action p-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold fs-7"><?= htmlspecialchars($sRow['nom_compte']) ?></div>
                                            <small class="text-muted fs-8"><i class="ph-duotone ph-user me-1"></i><?= htmlspecialchars(trim(($sRow['user_prenom'] ?? '') . ' ' . ($sRow['user_nom'] ?? ''))) ?></small>
                                        </div>
                                        <div class="text-end">
                                            <span class="fw-bold fs-7 text-success"><?= number_format((float)$sRow['solde_theorique'], 0, ',', ' ') ?> FCFA</span>
                                            <small class="d-block text-muted fs-8"><?= date('H:i', strtotime($sRow['date_ouverture'])) ?></small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Card 2: Sessions À Valider (Alertes Écarts) -->
            <div class="col-md-4">
                <div class="card shadow-sm h-100 border-0 <?= !empty($sess['a_valider']['count']) ? 'border-start border-warning border-4' : '' ?>">
                    <div class="card-header bg-light-warning d-flex justify-content-between align-items-center py-2">
                        <span class="fw-bold text-warning-main"><i class="ph-duotone ph-hourglass me-1"></i><?= _("Sessions À Valider") ?></span>
                        <span class="badge bg-warning rounded-pill"><?= (int)($sess['a_valider']['count'] ?? 0) ?></span>
                    </div>
                    <div class="card-body p-3">
                        <?php if (empty($sess['a_valider']['list'])): ?>
                            <p class="text-muted fs-7 mb-0 text-center py-2"><i class="ph-duotone ph-check-circle text-success me-1"></i><?= _("Aucune clôture en attente de validation.") ?></p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($sess['a_valider']['list'] as $sRow): ?>
                                    <?php $ecart = (float)$sRow['ecart']; ?>
                                    <a href="/treasury/sessions/show/<?= (int)$sRow['id'] ?>" class="list-group-item list-group-item-action p-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold fs-7"><?= htmlspecialchars($sRow['nom_compte']) ?></div>
                                            <small class="text-muted fs-8"><i class="ph-duotone ph-user me-1"></i><?= htmlspecialchars(trim(($sRow['user_prenom'] ?? '') . ' ' . ($sRow['user_nom'] ?? ''))) ?></small>
                                        </div>
                                        <div class="text-end">
                                            <span class="fw-bold fs-7"><?= number_format((float)$sRow['solde_reel'], 0, ',', ' ') ?> FCFA</span>
                                            <?php if (abs($ecart) > 0.01): ?>
                                                <span class="badge <?= $ecart > 0 ? 'bg-light-success text-success' : 'bg-light-danger text-danger' ?> d-block fs-8">
                                                    <?= sprintf(_("Écart: %s%s FCFA"), $ecart > 0 ? '+' : '', number_format($ecart, 0, ',', ' ')) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light-success text-success d-block fs-8"><?= _("Équilibrée") ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Card 3: Sessions Validées sur la période -->
            <div class="col-md-4">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-header bg-light-primary d-flex justify-content-between align-items-center py-2">
                        <span class="fw-bold text-primary"><i class="ph-duotone ph-check-circle me-1"></i><?= _("Validées (Période)") ?></span>
                        <span class="badge bg-primary rounded-pill"><?= (int)($sess['validees']['count'] ?? 0) ?></span>
                    </div>
                    <div class="card-body p-3">
                        <?php if (empty($sess['validees']['list'])): ?>
                            <p class="text-muted fs-7 mb-0 text-center py-2"><i class="ph-duotone ph-info me-1"></i><?= _("Aucune session validée sur cette période.") ?></p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach (array_slice($sess['validees']['list'], 0, 5) as $sRow): ?>
                                    <a href="/treasury/sessions/show/<?= (int)$sRow['id'] ?>" class="list-group-item list-group-item-action p-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold fs-7"><?= htmlspecialchars($sRow['nom_compte']) ?></div>
                                            <small class="text-muted fs-8"><i class="ph-duotone ph-user-check me-1"></i><?= htmlspecialchars(trim(($sRow['valideur_prenom'] ?? '') . ' ' . ($sRow['valideur_nom'] ?? ''))) ?></small>
                                        </div>
                                        <div class="text-end">
                                            <span class="fw-bold fs-7 text-primary"><?= number_format((float)$sRow['montant_remis'], 0, ',', ' ') ?> FCFA</span>
                                            <small class="d-block text-muted fs-8"><?= date('d/m H:i', strtotime($sRow['valide_le'])) ?></small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ZONE D: APEXCHARTS TREND CHART -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <h5 class="card-title mb-0">
                    <i class="ph-duotone ph-chart-line-up text-primary me-2"></i>
                    <?= _("Évolution des Flux : Encaissements vs Décaissements") ?>
                </h5>
                <small class="text-muted fs-7"><i class="ph-duotone ph-clock me-1"></i><?= sprintf(_("Période : du %s au %s"), date('d/m/Y', strtotime($d['date_debut'])), date('d/m/Y', strtotime($d['date_fin']))) ?></small>
            </div>
            <div class="card-body">
                <div id="treasury-trend-chart" style="min-height: 320px;"></div>
            </div>
        </div>

        <!-- ZONE E: TABLEAU COMPARATIF DES CAISSES -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <h5 class="card-title mb-0">
                    <i class="ph-duotone ph-squares-four text-primary me-2"></i>
                    <?= _("Comparatif des Caisses de l'Établissement") ?>
                </h5>
                <a href="/treasury/sessions" class="btn btn-sm btn-outline-primary">
                    <i class="ph-duotone ph-list-bullets me-1"></i><?= _("Gestion des Sessions") ?>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= _("Caisse") ?></th>
                            <th><?= _("Caissier / Responsable") ?></th>
                            <th class="text-end"><?= _("Encaissements (Période)") ?></th>
                            <th class="text-end"><?= _("Décaissements (Période)") ?></th>
                            <th class="text-end"><?= _("Solde Courant") ?></th>
                            <th class="text-center"><?= _("Statut Session") ?></th>
                            <th class="text-end"><?= _("Actions") ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($caisses)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="ph-duotone ph-bank fs-2 d-block mb-2"></i>
                                    <?= _("Aucune caisse active configurée pour cet établissement.") ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($caisses as $cRow): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($cRow['nom_compte']) ?></div>
                                        <small class="text-muted fs-8">ID: #<?= (int)$cRow['compte_id'] ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light-secondary text-dark fs-7">
                                            <i class="ph-duotone ph-user me-1"></i>
                                            <?= htmlspecialchars($cRow['person_name']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        +<?= number_format($cRow['encaissements_periode'], 0, ',', ' ') ?> FCFA
                                    </td>
                                    <td class="text-end fw-bold text-danger">
                                        -<?= number_format($cRow['decaissements_periode'], 0, ',', ' ') ?> FCFA
                                    </td>
                                    <td class="text-end fw-bold text-primary fs-6">
                                        <?= number_format($cRow['solde_courant'], 0, ',', ' ') ?> FCFA
                                    </td>
                                    <td class="text-center">
                                        <?php if ($cRow['session_statut'] === 'ouverte'): ?>
                                            <span class="badge bg-success p-2"><i class="ph-duotone ph-lock-key-open me-1"></i><?= _("Ouverte") ?></span>
                                        <?php elseif ($cRow['session_statut'] === 'fermee_a_valider'): ?>
                                            <span class="badge bg-warning p-2"><i class="ph-duotone ph-hourglass me-1"></i><?= _("À valider") ?></span>
                                            <?php if (abs($cRow['session_ecart']) > 0.01): ?>
                                                <small class="d-block text-danger mt-1 fw-bold"><?= sprintf(_("Écart: %s FCFA"), number_format($cRow['session_ecart'], 0, ',', ' ')) ?></small>
                                            <?php endif; ?>
                                        <?php elseif ($cRow['session_statut'] === 'fermee_validee'): ?>
                                            <span class="badge bg-secondary p-2"><i class="ph-duotone ph-check-circle me-1"></i><?= _("Validée") ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted p-2"><i class="ph-duotone ph-minus-circle me-1"></i><?= _("Aucune session") ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($cRow['session_id']): ?>
                                            <a href="/treasury/sessions/show/<?= (int)$cRow['session_id'] ?>" class="btn btn-sm btn-outline-primary" title="<?= _("Consulter la session") ?>">
                                                <i class="ph-duotone ph-eye me-1"></i><?= _("Détails") ?>
                                            </a>
                                        <?php else: ?>
                                            <a href="/treasury/sessions" class="btn btn-sm btn-light-secondary" title="<?= _("Consulter l'historique") ?>">
                                                <i class="ph-duotone ph-list me-1"></i><?= _("Sessions") ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ZONE F: CONTEXTUAL ACTIONS HUB GRID -->
        <div class="card shadow-sm">
            <div class="card-header py-3">
                <h5 class="card-title mb-0">
                    <i class="ph-duotone ph-squares-four text-primary me-2"></i>
                    <?= _("Actions Rapides / Hub de Navigation") ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Action 1: Ouvrir/Gérer Ma Session -->
                    <div class="col-sm-6 col-md-4 col-xl-3">
                        <div class="card bg-light-primary border border-primary-subtle h-100 p-3 text-center">
                            <i class="ph-duotone ph-lock-key fs-1 text-primary mb-2"></i>
                            <h6 class="fw-bold mb-1"><?= _("Gestion Session") ?></h6>
                            <p class="text-muted fs-8 mb-3"><?= _("Ouvrir, consulter ou clôturer ma session de caisse journalière.") ?></p>
                            <?php if (!empty($hub['has_active_session'])): ?>
                                <a href="/treasury/sessions/show/<?= (int)$hub['active_session_id'] ?>" class="btn btn-sm btn-primary w-100 mt-auto">
                                    <i class="ph-duotone ph-eye me-1"></i><?= _("Voir ma session") ?>
                                </a>
                            <?php elseif (!empty($hub['can_open_session'])): ?>
                                <a href="/treasury/sessions/open" class="btn btn-sm btn-primary w-100 mt-auto">
                                    <i class="ph-duotone ph-plus-circle me-1"></i><?= _("Ouvrir une session") ?>
                                </a>
                            <?php else: ?>
                                <a href="/treasury/sessions" class="btn btn-sm btn-outline-primary w-100 mt-auto">
                                    <i class="ph-duotone ph-list-bullets me-1"></i><?= _("Liste des sessions") ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Action 2: Validation des Clôtures -->
                    <?php if (!empty($hub['can_validate_session'])): ?>
                        <div class="col-sm-6 col-md-4 col-xl-3">
                            <div class="card bg-light-warning border border-warning-subtle h-100 p-3 text-center position-relative">
                                <?php if (!empty($hub['pending_validations_count'])): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger fs-8">
                                        <?= (int)$hub['pending_validations_count'] ?>
                                    </span>
                                <?php endif; ?>
                                <i class="ph-duotone ph-hourglass fs-1 text-warning mb-2"></i>
                                <h6 class="fw-bold mb-1"><?= _("Validation Clôtures") ?></h6>
                                <p class="text-muted fs-8 mb-3"><?= _("Contrôler les remises, régulariser les écarts et approuver les sessions.") ?></p>
                                <a href="/treasury/sessions" class="btn btn-sm btn-warning text-dark w-100 mt-auto fw-semibold">
                                    <i class="ph-duotone ph-check-square me-1"></i><?= _("Valider les clôtures") ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Action 3: Journal des Mouvements -->
                    <?php if (!empty($hub['can_view_movements'])): ?>
                        <div class="col-sm-6 col-md-4 col-xl-3">
                            <div class="card bg-light-info border border-info-subtle h-100 p-3 text-center">
                                <i class="ph-duotone ph-receipt fs-1 text-info mb-2"></i>
                                <h6 class="fw-bold mb-1"><?= _("Journal des Mouvements") ?></h6>
                                <p class="text-muted fs-8 mb-3"><?= _("Consulter le grand livre détaillé de trésorerie et encaissements.") ?></p>
                                <a href="/paiements/journal" class="btn btn-sm btn-info text-white w-100 mt-auto">
                                    <i class="ph-duotone ph-list-numbers me-1"></i><?= _("Consulter le journal") ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Action 4: Régler une Dépense / Décaissement -->
                    <?php if (!empty($hub['can_pay_depense'])): ?>
                        <div class="col-sm-6 col-md-4 col-xl-3">
                            <div class="card bg-light-danger border border-danger-subtle h-100 p-3 text-center">
                                <i class="ph-duotone ph-hand-coins fs-1 text-danger mb-2"></i>
                                <h6 class="fw-bold mb-1"><?= _("Décaissements & Dépenses") ?></h6>
                                <p class="text-muted fs-8 mb-3"><?= _("Exécuter les règlements de dépenses et factures fournisseurs approuvées.") ?></p>
                                <a href="/depenses/payments" class="btn btn-sm btn-danger w-100 mt-auto">
                                    <i class="ph-duotone ph-currency-dollar me-1"></i><?= _("Régler une dépense") ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Action 5: Comptes Financiers & Coffre Principal -->
                    <?php if (!empty($hub['can_view_accounts'])): ?>
                        <div class="col-sm-6 col-md-4 col-xl-3">
                            <div class="card bg-light-secondary border border-secondary-subtle h-100 p-3 text-center">
                                <i class="ph-duotone ph-bank fs-1 text-secondary mb-2"></i>
                                <h6 class="fw-bold mb-1"><?= _("Comptes & Coffre") ?></h6>
                                <p class="text-muted fs-8 mb-3"><?= _("Gérer les comptes financiers, caisses, banques et Coffre Principal.") ?></p>
                                <a href="/comptes-financiers" class="btn btn-sm btn-secondary w-100 mt-auto">
                                    <i class="ph-duotone ph-gear me-1"></i><?= _("Gérer les comptes") ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Action 6: Historique Global des Paiements -->
                    <div class="col-sm-6 col-md-4 col-xl-3">
                        <div class="card bg-light border h-100 p-3 text-center">
                            <i class="ph-duotone ph-clock-counter-clockwise fs-1 text-dark mb-2"></i>
                            <h6 class="fw-bold mb-1"><?= _("Historique Paiements") ?></h6>
                            <p class="text-muted fs-8 mb-3"><?= _("Rechercher et imprimer les reçus et relevés d'encaissements.") ?></p>
                            <a href="/paiements/historique" class="btn btn-sm btn-dark w-100 mt-auto">
                                <i class="ph-duotone ph-magnifying-glass me-1"></i><?= _("Voir l'historique") ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
function toggleCustomDates(val) {
    const fields = document.querySelectorAll('.custom-date-field');
    fields.forEach(f => {
        f.style.display = (val === 'custom') ? 'block' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const categories = <?= json_encode($chart['categories'] ?? []) ?>;
    const inflows = <?= json_encode($chart['inflows'] ?? []) ?>;
    const outflows = <?= json_encode($chart['outflows'] ?? []) ?>;

    const options = {
        series: [
            {
                name: "<?= _("Encaissements (Net)") ?>",
                data: inflows
            },
            {
                name: "<?= _("Décaissements") ?>",
                data: outflows
            }
        ],
        chart: {
            type: 'area',
            height: 320,
            toolbar: { show: false },
            zoom: { enabled: false }
        },
        colors: ['#2ca87f', '#dc2626'],
        dataLabels: { enabled: false },
        stroke: {
            curve: 'smooth',
            width: 2
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.05,
                stops: [0, 90, 100]
            }
        },
        xaxis: {
            categories: categories,
            labels: {
                style: { colors: '#64748b', fontSize: '12px' }
            }
        },
        yaxis: {
            labels: {
                formatter: function (val) {
                    return new Intl.NumberFormat('fr-FR').format(val) + ' FCFA';
                },
                style: { colors: '#64748b', fontSize: '12px' }
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return new Intl.NumberFormat('fr-FR').format(val) + ' FCFA';
                }
            }
        },
        grid: {
            borderColor: '#f1f5f9',
            strokeDashArray: 4
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right'
        }
    };

    const chart = new ApexCharts(document.querySelector("#treasury-trend-chart"), options);
    chart.render();
});
</script>

<?php require_once __DIR__ . '/../../views/layouts/footer_able.php'; ?>
