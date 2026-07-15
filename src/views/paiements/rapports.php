<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header d-print-none">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title"><h2 class="mb-0"><?= htmlspecialchars($title) ?></h2></div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="/paiements">Comptabilité</a></li>
                            <li class="breadcrumb-item" aria-current="page">Rapports & États</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Printable Header (Visible only when printing) -->
        <div class="d-none d-print-block mb-4 text-center">
            <h2><?= htmlspecialchars($title) ?></h2>
            <h5>Année Académique : <?= htmlspecialchars($activeYear['libelle'] ?? 'N/A') ?></h5>
            <p class="mb-1"><?= _('Généré le') ?> <?= date('d/m/Y H:i') ?></p>
            <hr>
        </div>

        <!-- Navigation Tabs (Points 8) -->
        <ul class="nav nav-tabs mb-4 d-print-none" id="rapportsTabs" role="tablist">
            <li class="nav-tab nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                    <i class="ph-duotone ph-chart-bar me-2"></i>Synthèse Générale
                </button>
            </li>
            <li class="nav-tab nav-item" role="presentation">
                <button class="nav-link" id="classes-tab" data-bs-toggle="tab" data-bs-target="#classes" type="button" role="tab" aria-controls="classes" aria-selected="false">
                    <i class="ph-duotone ph-chalkboard-teacher me-2"></i>Situation par classe
                </button>
            </li>
            <li class="nav-tab nav-item" role="presentation">
                <button class="nav-link" id="annuelle-tab" data-bs-toggle="tab" data-bs-target="#annuelle" type="button" role="tab" aria-controls="annuelle" aria-selected="false">
                    <i class="ph-duotone ph-calendar me-2"></i>Situation annuelle globale
                </button>
            </li>
        </ul>

        <div class="tab-content" id="rapportsTabsContent">
            <!-- 1. Synthèse Générale -->
            <div class="tab-pane fade show active d-print-block" id="general" role="tabpanel" aria-labelledby="general-tab">
                <!-- Filtres -->
                <div class="card mb-4 d-print-none">
                    <div class="card-body">
                        <form method="GET" action="/paiements/rapports" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Période du</label>
                                <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($filters['date_debut']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">au</label>
                                <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($filters['date_fin']) ?>">
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary w-100">Actualiser</button>
                                    <button type="button" onclick="window.print()" class="btn btn-secondary"><i class="ph-duotone ph-printer"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row">
                    <!-- Totaux par Type -->
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-0 py-3"><h5>Répartition par nature</h5></div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table mb-0">
                                        <thead>
                                            <tr>
                                                <th class="ps-4">Nature</th>
                                                <th class="text-end pe-4">Montant</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $totalN = 0; foreach ($statsType as $s): $totalN += $s['total']; ?>
                                                <tr>
                                                    <td class="ps-4"><?= htmlspecialchars($s['type']) ?></td>
                                                    <td class="text-end pe-4 fw-bold"><?= number_format($s['total'], 0, ',', ' ') ?> <small>FCFA</small></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th class="ps-4">TOTAL</th>
                                                <th class="text-end pe-4 fw-bold text-primary fs-5"><?= number_format($totalN, 0, ',', ' ') ?> <small>FCFA</small></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Totaux par Mode -->
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-0 py-3"><h5>Répartition par mode de règlement</h5></div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table mb-0">
                                        <thead>
                                            <tr>
                                                <th class="ps-4">Mode</th>
                                                <th class="text-end pe-4">Montant</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $totalM = 0; foreach ($statsMode as $s): $totalM += $s['total']; ?>
                                                <tr>
                                                    <td class="ps-4"><?= htmlspecialchars($s['mode']) ?></td>
                                                    <td class="text-end pe-4 fw-bold"><?= number_format($s['total'], 0, ',', ' ') ?> <small>FCFA</small></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th class="ps-4">TOTAL</th>
                                                <th class="text-end pe-4 fw-bold text-success fs-5"><?= number_format($totalM, 0, ',', ' ') ?> <small>FCFA</small></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Évolution Quotidienne -->
                    <div class="col-12 mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-0 py-3"><h5>Évolution des encaissements</h5></div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">Date</th>
                                                <th class="text-end pe-4">Montant collecté</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($evolution)): ?>
                                                <tr>
                                                    <td colspan="2" class="text-center py-4 text-muted">Aucun encaissement sur cette période.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($evolution as $e): ?>
                                                    <tr>
                                                        <td class="ps-4"><?= date('d/m/Y', strtotime($e['date'])) ?></td>
                                                        <td class="text-end pe-4 fw-bold text-dark"><?= number_format($e['total'], 0, ',', ' ') ?> <small>FCFA</small></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Situation financière par classe -->
            <div class="tab-pane fade d-print-block" id="classes" role="tabpanel" aria-labelledby="classes-tab">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="ph-duotone ph-chalkboard-teacher me-2 text-primary"></i>Situation Financière des Classes (Taux de Recouvrement)</h5>
                        <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary d-print-none"><i class="ph-duotone ph-printer me-1"></i>Imprimer</button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Classe</th>
                                        <th class="text-end">Attendu global</th>
                                        <th class="text-end">Recouvré (Payé)</th>
                                        <th class="text-end text-danger">Restes (Dettes)</th>
                                        <th class="text-center">Taux</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($classesFinances)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">Aucune donnée disponible pour cette année active.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($classesFinances as $cf): ?>
                                            <tr>
                                                <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($cf['nom_classe']) ?></td>
                                                <td class="text-end"><?= number_format($cf['expected'], 0, ',', ' ') ?> FCFA</td>
                                                <td class="text-end text-success fw-bold"><?= number_format($cf['paid'], 0, ',', ' ') ?> FCFA</td>
                                                <td class="text-end text-danger fw-bold"><?= number_format($cf['remaining'], 0, ',', ' ') ?> FCFA</td>
                                                <td class="text-center">
                                                    <div class="d-flex align-items-center justify-content-center">
                                                        <span class="me-2 fw-bold"><?= number_format($cf['rate'], 1) ?>%</span>
                                                        <div class="progress d-print-none" style="width: 80px; height: 6px;">
                                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $cf['rate'] ?>%" aria-valuenow="<?= $cf['rate'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Situation financière annuelle globale -->
            <div class="tab-pane fade d-print-block" id="annuelle" role="tabpanel" aria-labelledby="annuelle-tab">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="ph-duotone ph-calendar me-2 text-success"></i>Bilan Financier de l'Année Académique Active</h5>
                        <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary d-print-none"><i class="ph-duotone ph-printer me-1"></i>Imprimer le bilan</button>
                    </div>
                    <div class="card-body">
                        <div class="row text-center mb-4">
                            <div class="col-md-4 mb-3">
                                <div class="p-3 bg-light-primary rounded">
                                    <h6 class="text-primary text-uppercase mb-1 small fw-bold">Budget Attendu Total</h6>
                                    <h3 class="mb-0 fw-bold"><?= number_format($grandExpected, 0, ',', ' ') ?> <small class="fs-6 text-muted">FCFA</small></h3>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="p-3 bg-light-success rounded">
                                    <h6 class="text-success text-uppercase mb-1 small fw-bold">Recouvrement Effectué (Payé)</h6>
                                    <h3 class="mb-0 fw-bold"><?= number_format($grandPaid, 0, ',', ' ') ?> <small class="fs-6 text-muted">FCFA</small></h3>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="p-3 bg-light-danger rounded">
                                    <h6 class="text-danger text-uppercase mb-1 small fw-bold">Reste à Recouvrer (Dette)</h6>
                                    <h3 class="mb-0 fw-bold"><?= number_format($grandRemaining, 0, ',', ' ') ?> <small class="fs-6 text-muted">FCFA</small></h3>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Section -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-bold text-dark">Taux Global de Recouvrement</span>
                                <span class="fw-bold text-success fs-5"><?= $grandExpected > 0 ? number_format(($grandPaid / $grandExpected) * 100, 2) : '100' ?>%</span>
                            </div>
                            <div class="progress" style="height: 12px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $grandExpected > 0 ? ($grandPaid / $grandExpected) * 100 : 100 ?>%" aria-valuenow="<?= $grandExpected > 0 ? ($grandPaid / $grandExpected) * 100 : 100 ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
