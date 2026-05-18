<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= $title ?></h2>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/">Tableau de Bord</a></li>
                            <li class="breadcrumb-item"><a href="/paiements">Finances</a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= $title ?></li>
                        </ul>
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
                                <h6 class="text-white mb-1">Encaissement Total</h6>
                                <h3 class="text-white mb-0"><?= number_format($totalGlobal, 0, ',', ' ') ?> <small>FCFA</small></h3>
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
                                <h6 class="text-white mb-1">Total ce mois</h6>
                                <h3 class="text-white mb-0"><?= number_format($totalMonth, 0, ',', ' ') ?> <small>FCFA</small></h3>
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
                                <h6 class="text-white mb-1">Aujourd'hui</h6>
                                <h3 class="text-white mb-0"><?= number_format($totalToday, 0, ',', ' ') ?> <small>FCFA</small></h3>
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
                                <h6 class="text-white mb-1">Restes à percevoir</h6>
                                <h3 class="text-white mb-0"><?= number_format($arrieresInscriptions, 0, ',', ' ') ?> <small>FCFA</small></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- État des Inscriptions -->
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Élèves en attente</h6>
                                <h3 class="mb-0 text-warning"><?= $nbEnAttente ?></h3>
                            </div>
                            <div class="flex-shrink-0 ms-3">
                                <div class="avtar avtar-s bg-light-warning text-warning">
                                    <i class="ph-duotone ph-users"></i>
                                </div>
                            </div>
                        </div>
                        <a href="/paiements/pending" class="btn btn-link-warning p-0 mt-3">Voir la file d'attente <i class="ph-duotone ph-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Paiements partiels</h6>
                                <h3 class="mb-0 text-info"><?= $nbPartiel ?></h3>
                            </div>
                            <div class="flex-shrink-0 ms-3">
                                <div class="avtar avtar-s bg-light-info text-info">
                                    <i class="ph-duotone ph-user-circle-plus"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-muted small mt-3 mb-0">Inscriptions non soldées</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Élèves activés</h6>
                                <h3 class="mb-0 text-success"><?= $nbActif ?></h3>
                            </div>
                            <div class="flex-shrink-0 ms-3">
                                <div class="avtar avtar-s bg-light-success text-success">
                                    <i class="ph-duotone ph-check-square"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-muted small mt-3 mb-0">Dossiers financiers à jour</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Dernières Transactions -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Dernières Opérations de Caisse</h5>
                        <div class="card-header-right">
                            <a href="/paiements/pending" class="btn btn-warning btn-sm">
                                <i class="ph-duotone ph-clock-counter-clockwise me-2"></i>File d'attente
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Date & Heure</th>
                                        <th>Élève</th>
                                        <th>Type</th>
                                        <th>Mode</th>
                                        <th>Caissier</th>
                                        <th class="text-end">Montant</th>
                                        <th class="text-center">Action</th>
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
                                                <span class="badge bg-light-<?= $t['type'] == 'Inscription' ? 'success' : 'info' ?> text-<?= $t['type'] == 'Inscription' ? 'success' : 'info' ?>">
                                                    <?= $t['type'] ?>
                                                </span>
                                            </td>
                                            <td><?= $t['mode'] ?: 'N/A' ?></td>
                                            <td><small class="text-muted"><?= htmlspecialchars($t['caissier']) ?></small></td>
                                            <td class="text-end fw-bold text-dark"><?= number_format($t['montant'], 0, ',', ' ') ?> <small>FCFA</small></td>
                                            <td class="text-center">
                                                <a href="/paiements/show/<?= $t['eleve_id'] ?>" class="btn btn-icon btn-light-primary" title="Voir l'historique complet">
                                                    <i class="ph-duotone ph-receipt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($recentTransactions)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">Aucune transaction enregistrée pour le moment.</td>
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
