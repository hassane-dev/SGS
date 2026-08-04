<?php
// src/views/budgets/report.php
include __DIR__ . '/../layouts/header_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ Breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="/budgets">Pilotage Budgétaire</a></li>
                            <li class="breadcrumb-item" aria-current="page">Rapport de Synthèse</li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0">Rapport d'Exécution Budgétaire : <?= htmlspecialchars($budget['libelle']) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Breadcrumb ] end -->

        <!-- Execution metrics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Taux Global d'Exécution</h6>
                        <h2 class="text-primary"><?= $summary['taux_consommation'] ?>%</h2>
                        <div class="progress mt-3" style="height: 10px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $summary['taux_consommation'] ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Crédits Totaux</h6>
                        <h2><?= number_format($summary['credits_totaux'], 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h2>
                        <small class="text-muted">Initiale + Ajustements</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Crédits Disponibles</h6>
                        <h2 class="text-success"><?= number_format($summary['montant_disponible'], 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h2>
                        <small class="text-muted">Solde restant libre</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Rankings -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Rapport Détaillé par Ligne Budgétaire</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Ligne de dépense</th>
                                        <th>Crédits Alloués</th>
                                        <th>Engagements Actifs</th>
                                        <th>Consommations Effectuées</th>
                                        <th>Taux de Consommation</th>
                                        <th>Reste Disponible</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lines as $line): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold text-dark"><?= htmlspecialchars($line['nom_categorie']) ?></span>
                                                <?php if (!empty($line['nom_centre'])): ?>
                                                    <br><small class="text-muted">CC: <?= htmlspecialchars($line['nom_centre']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= number_format($line['credits_totaux'], 0, ',', ' ') ?> FCFA</td>
                                            <td><?= number_format($line['montant_engage'], 0, ',', ' ') ?> FCFA</td>
                                            <td><?= number_format($line['montant_consomme'], 0, ',', ' ') ?> FCFA</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge <?= $line['status_badge'] ?> me-2"><?= $line['pourcentage_consomme'] ?>%</span>
                                                    <div class="progress" style="height: 6px; width: 100px;">
                                                        <div class="progress-bar bg-<?= $line['status_color'] ?>" role="progressbar" style="width: <?= $line['pourcentage_consomme'] ?>%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="fw-bold <?= $line['disponible'] > 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($line['disponible'], 0, ',', ' ') ?> FCFA</span>
                                            </td>
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

<?php include __DIR__ . '/../layouts/footer_able.php'; ?>