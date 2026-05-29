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
                            <li class="breadcrumb-item" aria-current="page"><?= $title ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Liste des paiements de salaires</h5>
                        <a href="/salaires/create" class="btn btn-primary">
                            <i class="ph-duotone ph-plus-circle me-2"></i>Enregistrer un Salaire
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Période</th>
                                        <th>Membre du Personnel</th>
                                        <th>Montant Net</th>
                                        <th>Date de Paiement</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($salaires as $salaire): ?>
                                        <tr>
                                            <td><span class="badge bg-light-secondary"><?= htmlspecialchars($salaire['mois'] . '/' . $salaire['annee']) ?></span></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-0"><?= htmlspecialchars($salaire['prenom'] . ' ' . $salaire['nom']) ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="fw-bold"><?= number_format($salaire['montant_net'], 0, ',', ' ') ?> FCFA</td>
                                            <td><?= $salaire['date_paiement'] ? date('d/m/Y', strtotime($salaire['date_paiement'])) : 'N/A' ?></td>
                                            <td class="text-end">
                                                <a href="/salaires/fiche?id=<?= $salaire['id_salaire'] ?>" class="btn btn-icon btn-light-success" target="_blank" title="Télécharger la fiche de paie">
                                                    <i class="ph-duotone ph-file-pdf"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($salaires)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">Aucun paiement de salaire enregistré.</td>
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
