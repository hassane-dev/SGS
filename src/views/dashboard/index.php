<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header mb-4">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= htmlspecialchars($title) ?></h2>
                        </div>
                        <p class="text-muted mb-0"><?= _("Vision globale transversale 360° de l'établissement") ?></p>
                    </div>
                    <div class="col-md-4 text-md-end mt-2 mt-md-0">
                        <?php if (!empty($activeYear)): ?>
                            <span class="badge bg-light-primary text-primary fs-6 px-3 py-2">
                                <i class="ph-duotone ph-calendar me-1"></i><?= _("Année Active :") ?> <?= htmlspecialchars($activeYear['libelle']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- Executive Welcome Banner -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white mb-0 shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div>
                                <h4 class="text-white mb-1"><?= _("Bienvenue sur le Cockpit Général SGS,") ?> <?= htmlspecialchars(Auth::get('prenom') ?? Auth::get('email')) ?> !</h4>
                                <p class="text-white-50 mb-0"><?= _("Synthèse consolidée des activités académiques, de la vie scolaire, des finances et des ressources humaines.") ?></p>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="/reporting" class="btn btn-light text-primary">
                                    <i class="ph-duotone ph-chart-pie me-1"></i><?= _("Reporting Décisionnel") ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php $kpis = $dashboardData['kpis'] ?? []; ?>

        <div class="row">
            <!-- 1. PILIER ÉLÈVES & EFFECTIFS -->
            <?php if (!empty($permissions['canSeeEffectifs']) && isset($kpis['effectifs'])): ?>
                <?php $eff = $kpis['effectifs']; ?>
                <div class="col-md-6 col-xl-4 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-header bg-light-primary border-0 d-flex align-items-center justify-content-between py-3">
                            <h5 class="mb-0 text-primary">
                                <i class="ph-duotone ph-student me-2 f-20"></i><?= _("Scolarité & Effectifs") ?>
                            </h5>
                            <span class="badge bg-primary rounded-pill"><?= $eff['total_actifs'] ?> <?= _("Élèves") ?></span>
                        </div>
                        <div class="card-body">
                            <div class="row text-center mb-3">
                                <div class="col-6 border-end">
                                    <h4 class="mb-0 text-dark font-monospace"><?= $eff['total_actifs'] ?></h4>
                                    <small class="text-muted"><?= _("Effectif Total Actif") ?></small>
                                </div>
                                <div class="col-6">
                                    <h4 class="mb-0 text-dark font-monospace"><?= $eff['total_classes'] ?></h4>
                                    <small class="text-muted"><?= _("Classes Ouvertes") ?></small>
                                </div>
                            </div>
                            <div class="p-2 bg-light rounded mb-3">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span><?= _("Filles / Garçons") ?></span>
                                    <span><?= $eff['filles'] ?> F / <?= $eff['garcons'] ?> G</span>
                                </div>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span><?= _("Nouvelles / Réinscriptions") ?></span>
                                    <span><?= $eff['nouvelles_inscriptions'] ?> / <?= $eff['reinscriptions'] ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 pt-0 text-end">
                            <a href="/eleves/dashboard" class="btn btn-sm btn-outline-primary w-100">
                                <?= _("Accéder au Hub Effectifs") ?> <i class="ph-duotone ph-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 2. PILIER ACADÉMIQUE & NOTES -->
            <?php if (!empty($permissions['canSeeAcademic']) && isset($kpis['academic'])): ?>
                <?php $acad = $kpis['academic']; ?>
                <div class="col-md-6 col-xl-4 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-header bg-light-info border-0 d-flex align-items-center justify-content-between py-3">
                            <h5 class="mb-0 text-info">
                                <i class="ph-duotone ph-chalkboard-teacher me-2 f-20"></i><?= _("Académique & Notes") ?>
                            </h5>
                            <span class="badge bg-info rounded-pill"><?= htmlspecialchars($acad['active_sequence_name']) ?></span>
                        </div>
                        <div class="card-body">
                            <div class="row text-center mb-3">
                                <div class="col-6 border-end">
                                    <h4 class="mb-0 text-dark font-monospace">
                                        <?= $acad['average_grade'] !== null ? number_format($acad['average_grade'], 2, ',', ' ') . '/20' : 'N/A' ?>
                                    </h4>
                                    <small class="text-muted"><?= _("Moyenne Générale") ?></small>
                                </div>
                                <div class="col-6">
                                    <h4 class="mb-0 text-dark font-monospace"><?= number_format($acad['completion_rate'], 1, ',', ' ') ?>%</h4>
                                    <small class="text-muted"><?= _("Taux de Complétion") ?></small>
                                </div>
                            </div>
                            <div class="p-2 bg-light rounded mb-3">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span><?= _("Évaluations Enregistrées") ?></span>
                                    <span><strong><?= $acad['total_evaluations'] ?></strong></span>
                                </div>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span><?= _("Statut Séquence") ?></span>
                                    <span class="badge bg-light-<?= $acad['sequence_statut'] === 'ouverte' ? 'success text-success' : 'secondary text-secondary' ?>">
                                        <?= htmlspecialchars(ucfirst($acad['sequence_statut'])) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 pt-0 text-end">
                            <a href="/evaluations/dashboard" class="btn btn-sm btn-outline-info w-100">
                                <?= _("Accéder au Hub Notes") ?> <i class="ph-duotone ph-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 3. PILIER VIE SCOLAIRE & PRÉSENCES -->
            <?php if (!empty($permissions['canSeePresence']) && isset($kpis['presence'])): ?>
                <?php $pres = $kpis['presence']; ?>
                <div class="col-md-6 col-xl-4 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-header bg-light-success border-0 d-flex align-items-center justify-content-between py-3">
                            <h5 class="mb-0 text-success">
                                <i class="ph-duotone ph-user-check me-2 f-20"></i><?= _("Présences & Absences") ?>
                            </h5>
                            <span class="badge bg-success rounded-pill"><?= number_format($pres['presence_rate'], 1, ',', ' ') ?>% <?= _("Présence") ?></span>
                        </div>
                        <div class="card-body">
                            <div class="row text-center mb-3">
                                <div class="col-6 border-end">
                                    <h4 class="mb-0 text-dark font-monospace"><?= number_format($pres['presence_rate'], 1, ',', ' ') ?>%</h4>
                                    <small class="text-muted"><?= _("Taux de Présence (30j)") ?></small>
                                </div>
                                <div class="col-6">
                                    <h4 class="mb-0 text-danger font-monospace"><?= $pres['today_absences_count'] ?></h4>
                                    <small class="text-muted"><?= _("Absences du Jour") ?></small>
                                </div>
                            </div>
                            <div class="p-2 bg-light rounded mb-3">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span><?= _("Retards du jour") ?></span>
                                    <span><strong><?= $pres['today_delays_count'] ?></strong></span>
                                </div>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span><?= _("Absences non justifiées (30j)") ?></span>
                                    <span class="text-danger"><strong><?= $pres['unjustified_absences_count'] ?></strong></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 pt-0 text-end">
                            <a href="/presences/dashboard" class="btn btn-sm btn-outline-success w-100">
                                <?= _("Accéder au Hub Présences") ?> <i class="ph-duotone ph-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 4. PILIER DISCIPLINE -->
            <?php if (!empty($permissions['canSeeDiscipline']) && isset($kpis['discipline'])): ?>
                <?php $disc = $kpis['discipline']; ?>
                <div class="col-md-6 col-xl-4 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-header bg-light-warning border-0 d-flex align-items-center justify-content-between py-3">
                            <h5 class="mb-0 text-warning">
                                <i class="ph-duotone ph-warning-octagon me-2 f-20"></i><?= _("Discipline & Sanctions") ?>
                            </h5>
                            <span class="badge bg-warning rounded-pill"><?= $disc['total_incidents'] ?> <?= _("Incidents") ?></span>
                        </div>
                        <div class="card-body">
                            <div class="row text-center mb-3">
                                <div class="col-6 border-end">
                                    <h4 class="mb-0 text-dark font-monospace"><?= $disc['total_incidents'] ?></h4>
                                    <small class="text-muted"><?= _("Total Incidents") ?></small>
                                </div>
                                <div class="col-6">
                                    <h4 class="mb-0 text-dark font-monospace"><?= $disc['total_sanctions'] ?></h4>
                                    <small class="text-muted"><?= _("Sanctions Prononcées") ?></small>
                                </div>
                            </div>
                            <div class="p-2 bg-light rounded mb-3">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span><?= _("Élèves impliqués / responsables") ?></span>
                                    <span><?= $disc['eleves_impliques'] ?> / <?= $disc['eleves_responsables'] ?></span>
                                </div>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span><?= _("Élèves récidivistes (>=2)") ?></span>
                                    <span class="text-danger"><strong><?= $disc['eleves_recidivistes'] ?></strong></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 pt-0 text-end">
                            <a href="/discipline/dashboard" class="btn btn-sm btn-outline-warning w-100">
                                <?= _("Accéder au Hub Discipline") ?> <i class="ph-duotone ph-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 5. PILIER FINANCE & TRÉSORERIE -->
            <?php if (!empty($permissions['canSeeFinance']) && isset($kpis['finance'])): ?>
                <?php $fin = $kpis['finance']; ?>
                <div class="col-md-6 col-xl-4 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-header bg-light-danger border-0 d-flex align-items-center justify-content-between py-3">
                            <h5 class="mb-0 text-danger">
                                <i class="ph-duotone ph-currency-dollar me-2 f-20"></i><?= _("Finance & Trésorerie") ?>
                            </h5>
                            <span class="badge bg-danger rounded-pill"><?= number_format($fin['taux_recouvrement'], 1, ',', ' ') ?>% <?= _("Recouvrement") ?></span>
                        </div>
                        <div class="card-body">
                            <div class="row text-center mb-3">
                                <div class="col-6 border-end">
                                    <h4 class="mb-0 text-dark font-monospace"><?= number_format($fin['solde_total_liquidites'], 0, ',', ' ') ?></h4>
                                    <small class="text-muted"><?= _("Liquidités (FCFA)") ?></small>
                                </div>
                                <div class="col-6">
                                    <h4 class="mb-0 text-success font-monospace"><?= number_format($fin['encaissements_jour'], 0, ',', ' ') ?></h4>
                                    <small class="text-muted"><?= _("Encaissements du Jour") ?></small>
                                </div>
                            </div>
                            <div class="p-2 bg-light rounded mb-3">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span><?= _("Solde Caisses / Coffre") ?></span>
                                    <span><?= number_format($fin['solde_caisses'], 0, ',', ' ') ?> / <?= number_format($fin['solde_coffre'], 0, ',', ' ') ?></span>
                                </div>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span><?= _("Décaissements du jour") ?></span>
                                    <span class="text-danger"><strong><?= number_format($fin['decaissements_jour'], 0, ',', ' ') ?> FCFA</strong></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 pt-0 text-end d-flex gap-2">
                            <a href="/treasury/dashboard" class="btn btn-sm btn-outline-danger w-50">
                                <?= _("Caisse") ?>
                            </a>
                            <a href="/paiements" class="btn btn-sm btn-danger w-50">
                                <?= _("Scolarité") ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 6. PILIER RH & PAIE -->
            <?php if (!empty($permissions['canSeeRh']) && isset($kpis['rh'])): ?>
                <?php $rh = $kpis['rh']; ?>
                <div class="col-md-6 col-xl-4 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-header bg-light-purple border-0 d-flex align-items-center justify-content-between py-3">
                            <h5 class="mb-0 text-purple" style="color: #6f42c1;">
                                <i class="ph-duotone ph-users-three me-2 f-20"></i><?= _("Ressources Humaines & Paie") ?>
                            </h5>
                            <span class="badge bg-purple rounded-pill" style="background-color: #6f42c1;"><?= $rh['effectif_rh_actif'] ?> <?= _("Agents") ?></span>
                        </div>
                        <div class="card-body">
                            <div class="row text-center mb-3">
                                <div class="col-6 border-end">
                                    <h4 class="mb-0 text-dark font-monospace"><?= $rh['effectif_rh_actif'] ?></h4>
                                    <small class="text-muted"><?= _("Personnel Actif") ?></small>
                                </div>
                                <div class="col-6">
                                    <h4 class="mb-0 text-dark font-monospace"><?= number_format($rh['net_a_payer_periode'], 0, ',', ' ') ?></h4>
                                    <small class="text-muted"><?= _("Masse Net (FCFA)") ?></small>
                                </div>
                            </div>
                            <div class="p-2 bg-light rounded mb-3">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span><?= _("Période Active") ?></span>
                                    <span><strong><?= htmlspecialchars($rh['active_periode_nom']) ?></strong></span>
                                </div>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span><?= _("Bulletins Générés / Réglés") ?></span>
                                    <span><?= $rh['bulletins_count'] ?> / <strong class="text-success"><?= $rh['bulletins_paid_count'] ?></strong></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 pt-0 text-end">
                            <a href="/drh/dashboard" class="btn btn-sm btn-outline-secondary w-100">
                                <?= _("Accéder au Hub RH & Paie") ?> <i class="ph-duotone ph-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
