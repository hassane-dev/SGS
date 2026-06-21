<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title"><h2 class="mb-0"><?= $title ?></h2></div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/">Accueil</a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= $title ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="/paiements/rapports" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Période du</label>
                        <input type="date" name="date_debut" class="form-control" value="<?= $filters['date_debut'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">au</label>
                        <input type="date" name="date_fin" class="form-control" value="<?= $filters['date_fin'] ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Actualiser le rapport</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <!-- Totaux par Type -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5>Répartition par nature</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nature</th>
                                        <th class="text-end">Montant</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $totalN = 0; foreach ($statsType as $s): $totalN += $s['total']; ?>
                                        <tr>
                                            <td><?= $s['type'] ?></td>
                                            <td class="text-end fw-bold"><?= number_format($s['total'], 0, ',', ' ') ?> <small>FCFA</small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>TOTAL</th>
                                        <th class="text-end fw-bold text-primary fs-5"><?= number_format($totalN, 0, ',', ' ') ?> <small>FCFA</small></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Totaux par Mode -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5>Répartition par mode de règlement</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Mode</th>
                                        <th class="text-end">Montant</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $totalM = 0; foreach ($statsMode as $s): $totalM += $s['total']; ?>
                                        <tr>
                                            <td><?= $s['mode'] ?></td>
                                            <td class="text-end fw-bold"><?= number_format($s['total'], 0, ',', ' ') ?> <small>FCFA</small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>TOTAL</th>
                                        <th class="text-end fw-bold text-success fs-5"><?= number_format($totalM, 0, ',', ' ') ?> <small>FCFA</small></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Évolution Quotidienne -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h5>Évolution des encaissements</h5></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-end">Montant collecté</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($evolution as $e): ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($e['date'])) ?></td>
                                            <td class="text-end fw-bold text-dark"><?= number_format($e['total'], 0, ',', ' ') ?> <small>FCFA</small></td>
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
