<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Rapports Financiers') ?></h2>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/">Tableau de Bord</a></li>
                            <li class="breadcrumb-item"><a href="/paiements"><?= _('Comptabilité') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Rapports') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Global Stats -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-primary text-white">
                    <div class="card-body">
                        <p class="text-white text-opacity-75 mb-1"><?= _('Total Recettes') ?></p>
                        <h3 class="text-white mb-0"><?= number_format($totalInscriptions + $totalMensualites, 0, ',', ' ') ?> FCFA</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-1"><?= _('Inscriptions') ?></p>
                        <h3 class="mb-0"><?= number_format($totalInscriptions, 0, ',', ' ') ?> FCFA</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-1"><?= _('Mensualités') ?></p>
                        <h3 class="mb-0"><?= number_format($totalMensualites, 0, ',', ' ') ?> FCFA</h3>
                    </div>
                </div>
            </div>

            <!-- Revenue by Mode -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5><?= _('Répartition par mode de paiement') ?></h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Mode</th>
                                        <th class="text-end">Montant</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($byMode as $mode): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($mode['mode_paiement'] ?: 'Non spécifié') ?></td>
                                            <td class="text-end fw-bold"><?= number_format($mode['total'], 0, ',', ' ') ?> FCFA</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Trend -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5><?= _('Évolution des 30 derniers jours') ?></h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-end">Recette</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_reverse($dailyTrend) as $day): ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($day['date'])) ?></td>
                                            <td class="text-end fw-bold"><?= number_format($day['total'], 0, ',', ' ') ?> FCFA</td>
                                        </tr>
                                    <?php endforeach; ?>
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
