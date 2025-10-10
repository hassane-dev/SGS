<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= _('Gestion des Salaires') ?></h1>
        <a href="/salaires/create" class="btn btn-primary">
            <i class="fas fa-plus"></i> <?= _('Enregistrer un paiement') ?>
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary"><?= _('Historique des Salaires') ?></h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><?= _('Période') ?></th>
                            <th><?= _('Membre du personnel') ?></th>
                            <th><?= _('Montant Net') ?></th>
                            <th><?= _('Date de Paiement') ?></th>
                            <th class="text-end"><?= _('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salaires as $salaire): ?>
                            <tr>
                                <td><?= htmlspecialchars($salaire['periode_mois'] . '/' . $salaire['periode_annee']) ?></td>
                                <td><?= htmlspecialchars($salaire['prenom'] . ' ' . $salaire['nom']) ?></td>
                                <td><?= htmlspecialchars(number_format($salaire['montant'], 2)) ?> XAF</td>
                                <td><?= $salaire['date_paiement'] ? htmlspecialchars(date('d/m/Y', strtotime($salaire['date_paiement']))) : '---' ?></td>
                                <td class="text-end">
                                    <a href="/salaires/fiche?id=<?= $salaire['id_salaire'] ?>" class="btn btn-info btn-sm" target="_blank" title="<?= _('Télécharger la fiche de paie') ?>">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>