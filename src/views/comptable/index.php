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
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Statistiques -->
            <div class="col-md-6 col-xl-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="text-white">Total Collecté</h6>
                        <h2 class="text-white"><?= number_format($totalGlobal, 0, ',', ' ') ?> <small>FCFA</small></h2>
                        <p class="mb-0">Inscriptions + Mensualités</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="text-white">Inscriptions</h6>
                        <h2 class="text-white"><?= number_format($totalInscriptions, 0, ',', ' ') ?> <small>FCFA</small></h2>
                        <p class="mb-0">Frais d'inscription versés</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="text-white">Mensualités</h6>
                        <h2 class="text-white"><?= number_format($totalMensualites, 0, ',', ' ') ?> <small>FCFA</small></h2>
                        <p class="mb-0">Scolarité mensuelle</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h6 class="text-white">Arriérés Inscription</h6>
                        <h2 class="text-white"><?= number_format($arrieresInscriptions, 0, ',', ' ') ?> <small>FCFA</small></h2>
                        <p class="mb-0">Reste à payer</p>
                    </div>
                </div>
            </div>

            <!-- Dernières Transactions -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Dernières Transactions</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Élève</th>
                                        <th>Type</th>
                                        <th class="text-end">Montant</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentTransactions as $t): ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i', strtotime($t['date'])) ?></td>
                                            <td><?= htmlspecialchars($t['eleve_nom']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $t['type'] == 'Inscription' ? 'success' : 'info' ?>">
                                                    <?= $t['type'] ?>
                                                </span>
                                            </td>
                                            <td class="text-end fw-bold"><?= number_format($t['montant_verse'], 0, ',', ' ') ?> FCFA</td>
                                            <td class="text-center">
                                                <a href="/paiements/show/<?= $t['eleve_id'] ?>" class="btn btn-sm btn-icon btn-outline-primary">
                                                    <i class="ti ti-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($recentTransactions)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Aucune transaction récente.</td>
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
