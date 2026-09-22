<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _($title) ?></h2>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/paiements"><?= _('Finances') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _($title) ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques Financières -->
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted fw-semibold mb-1 small text-uppercase"><?= _('Encaissement Total') ?></p>
                                <h3 class="mb-0 fw-bold text-primary"><?= number_format($totalGlobal, 0, ',', ' ') ?> <small class="fs-6 fw-normal text-muted">FCFA</small></h3>
                            </div>
                            <div class="avtar avtar-lg bg-light-primary text-primary rounded-3">
                                <i class="ph-duotone ph-money fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted fw-semibold mb-1 small text-uppercase"><?= _('Total ce mois') ?></p>
                                <h3 class="mb-0 fw-bold text-success"><?= number_format($totalMonth, 0, ',', ' ') ?> <small class="fs-6 fw-normal text-muted">FCFA</small></h3>
                            </div>
                            <div class="avtar avtar-lg bg-light-success text-success rounded-3">
                                <i class="ph-duotone ph-calendar-check fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted fw-semibold mb-1 small text-uppercase"><?= _("Aujourd'hui") ?></p>
                                <h3 class="mb-0 fw-bold text-info"><?= number_format($totalToday, 0, ',', ' ') ?> <small class="fs-6 fw-normal text-muted">FCFA</small></h3>
                            </div>
                            <div class="avtar avtar-lg bg-light-info text-info rounded-3">
                                <i class="ph-duotone ph-clock fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted fw-semibold mb-1 small text-uppercase"><?= _('Restes à percevoir') ?></p>
                                <h3 class="mb-0 fw-bold text-danger"><?= number_format($arrieresInscriptions, 0, ',', ' ') ?> <small class="fs-6 fw-normal text-muted">FCFA</small></h3>
                            </div>
                            <div class="avtar avtar-lg bg-light-danger text-danger rounded-3">
                                <i class="ph-duotone ph-hand-coins fs-2"></i>
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
                                <h3 class="mb-0 text-warning"><?= $nbEnAttente ?></h3>
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
                                <h3 class="mb-0 text-info"><?= $nbPartiel ?></h3>
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
                                <h3 class="mb-0 text-success"><?= $nbActif ?></h3>
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
                                    <?php if (empty($recentTransactions)): ?>
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
