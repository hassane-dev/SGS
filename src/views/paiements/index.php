<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _($title ?? 'Recettes & Caisse') ?></h2>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/paiements"><?= _('Finances') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _($title ?? 'Recettes & Caisse') ?></li>
                        </ul>
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
                                <h5 class="text-white mb-1"><i class="ph-duotone ph-currency-circle-dollar me-2"></i><?= _("Cockpit Recettes & Opérations de Caisse") ?></h5>
                                <p class="text-white-50 small mb-0"><?= _("Gérez les encaissements d'inscription, mensualités, restes à percevoir et reçus.") ?></p>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <?php if (Auth::can('manage', 'paiement')): ?>
                                    <a href="/paiements/pending" class="btn btn-warning btn-sm text-dark font-weight-bold">
                                        <i class="ph-duotone ph-clock me-1"></i><?= _("Paiement Inscription") ?>
                                    </a>
                                    <a href="/mensualites" class="btn btn-light btn-sm font-weight-bold">
                                        <i class="ph-duotone ph-calendar-check text-primary me-1"></i><?= _("Gestion Mensualités") ?>
                                    </a>
                                    <a href="/paiements/restes" class="btn btn-light btn-sm font-weight-bold">
                                        <i class="ph-duotone ph-hand-coins text-primary me-1"></i><?= _("Gestion des Restes") ?>
                                    </a>
                                <?php endif; ?>

                                <?php if (Auth::can('view', 'paiement')): ?>
                                    <a href="/paiements/historique" class="btn btn-outline-light btn-sm">
                                        <i class="ph-duotone ph-clock-counter-clockwise me-1"></i><?= _("Historique Paiements") ?>
                                    </a>
                                    <a href="/paiements/recus" class="btn btn-outline-light btn-sm">
                                        <i class="ph-duotone ph-receipt me-1"></i><?= _("Reçus") ?>
                                    </a>
                                <?php endif; ?>

                                <?php if (Auth::can('view', 'sessions_caisse') || Auth::can('create', 'sessions_caisse')): ?>
                                    <a href="/treasury/sessions" class="btn btn-outline-light btn-sm">
                                        <i class="ph-duotone ph-vault me-1"></i><?= _("Sessions de Caisse") ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques Financières -->
        <div class="row">
            <div class="col-md-6 col-xl-3">
                <div class="card bg-grd-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avtar avtar-l bg-light-primary text-primary">
                                    <i class="ph-duotone ph-money fs-1"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-white mb-1"><?= _('Encaissement Total') ?></h6>
                                <h3 class="text-white mb-0"><?= number_format($totalGlobal ?? 0, 0, ',', ' ') ?> <small>FCFA</small></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card bg-grd-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avtar avtar-l bg-light-success text-success">
                                    <i class="ph-duotone ph-calendar-check fs-1"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-white mb-1"><?= _('Total ce mois') ?></h6>
                                <h3 class="text-white mb-0"><?= number_format($totalMonth ?? 0, 0, ',', ' ') ?> <small>FCFA</small></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card bg-grd-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avtar avtar-l bg-light-info text-info">
                                    <i class="ph-duotone ph-clock fs-1"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-white mb-1"><?= _("Aujourd'hui") ?></h6>
                                <h3 class="text-white mb-0"><?= number_format($totalToday ?? 0, 0, ',', ' ') ?> <small>FCFA</small></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card bg-grd-danger text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avtar avtar-l bg-light-danger text-danger">
                                    <i class="ph-duotone ph-hand-coins fs-1"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-white mb-1"><?= _('Restes à percevoir') ?></h6>
                                <h3 class="text-white mb-0"><?= number_format($arrieresInscriptions ?? 0, 0, ',', ' ') ?> <small>FCFA</small></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alertes Section -->
        <?php if (!empty($alerts)): ?>
            <div class="row">
                <div class="col-12">
                    <?php foreach ($alerts as $alert): ?>
                        <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="ph-duotone ph-warning-circle fs-2 me-3"></i>
                                <div>
                                    <strong><?= _('Attention !') ?></strong> <?= htmlspecialchars($alert['message']) ?>
                                    <?php if (!empty($alert['link'])): ?>
                                        <a href="<?= $alert['link'] ?>" class="alert-link ms-2 text-decoration-underline"><?= _('Consulter la liste') ?></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- État des Inscriptions -->
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?= _('Élèves en attente') ?></h6>
                                <h3 class="mb-0 text-warning"><?= $nbEnAttente ?? 0 ?></h3>
                            </div>
                            <div class="flex-shrink-0 ms-3">
                                <div class="avtar avtar-s bg-light-warning text-warning">
                                    <i class="ph-duotone ph-users"></i>
                                </div>
                            </div>
                        </div>
                        <a href="/paiements/pending" class="btn btn-link-warning p-0 mt-3"><?= _("Voir la file d'attente") ?> <i class="ph-duotone ph-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?= _('Paiements partiels') ?></h6>
                                <h3 class="mb-0 text-info"><?= $nbPartiel ?? 0 ?></h3>
                            </div>
                            <div class="flex-shrink-0 ms-3">
                                <div class="avtar avtar-s bg-light-info text-info">
                                    <i class="ph-duotone ph-user-circle-plus"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-muted small mt-3 mb-0"><?= _('Inscriptions non soldées') ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?= _('Élèves activés') ?></h6>
                                <h3 class="mb-0 text-success"><?= $nbActif ?? 0 ?></h3>
                            </div>
                            <div class="flex-shrink-0 ms-3">
                                <div class="avtar avtar-s bg-light-success text-success">
                                    <i class="ph-duotone ph-check-square"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-muted small mt-3 mb-0"><?= _('Dossiers financiers à jour') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Dernières Transactions -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><?= _('Dernières Opérations de Caisse') ?></h5>
                        <div class="card-header-right">
                            <a href="/paiements/pending" class="btn btn-warning btn-sm">
                                <i class="ph-duotone ph-clock-counter-clockwise me-2"></i><?= _("File d'attente") ?>
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th><?= _('Date & Heure') ?></th>
                                        <th><?= _('Élève') ?></th>
                                        <th><?= _('Type') ?></th>
                                        <th><?= _('Mode') ?></th>
                                        <th><?= _('Caissier') ?></th>
                                        <th class="text-end"><?= _('Montant') ?></th>
                                        <th class="text-center"><?= _('Action') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recentTransactions)): ?>
                                        <?php foreach ($recentTransactions as $t): ?>
                                            <tr>
                                                <td><?= date('d/m/Y H:i', strtotime($t['date'])) ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-0"><?= htmlspecialchars($t['eleve_nom']) ?></h6>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php
                                                    $types = explode(' + ', $t['type']);
                                                    foreach($types as $type):
                                                        $color = ($type == 'Inscription') ? 'success' : 'info';
                                                    ?>
                                                    <span class="badge bg-light-<?= $color ?> text-<?= $color ?>">
                                                        <?= _($type) ?>
                                                    </span>
                                                    <?php endforeach; ?>
                                                </td>
                                                <td><?= $t['mode'] ?: 'N/A' ?></td>
                                                <td><small class="text-muted"><?= htmlspecialchars($t['caissier']) ?></small></td>
                                                <td class="text-end fw-bold text-dark"><?= number_format($t['montant'], 0, ',', ' ') ?> <small>FCFA</small></td>
                                                <td class="text-center">
                                                    <a href="/paiements/show/<?= $t['eleve_id'] ?>" class="btn btn-icon btn-light-primary" title="<?= _("Voir l'historique complet") ?>">
                                                        <i class="ph-duotone ph-receipt"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted"><?= _('Aucune transaction enregistrée pour le moment.') ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
