<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= _('Gestion des Salaires') ?></h1>

    <?php if (isset($_GET['generated'])): ?>
        <div class="alert alert-success">
            <?= sprintf(_('%d fiches de salaire ont été générées avec succès.'), (int)$_GET['generated']) ?>
        </div>
    <?php endif; ?>

    <!-- Section de Génération des Salaires -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= _('Générer les Salaires du Mois') ?></h6>
        </div>
        <div class="card-body">
            <p><?= _('Cette action calculera et créera les fiches de salaire pour tous les employés éligibles pour la période sélectionnée. Les salaires déjà existants pour cette période ne seront pas affectés.') ?></p>
            <form action="/salaires/generer" method="POST" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="mois" class="form-label"><?= _('Mois') ?></label>
                    <select name="mois" id="mois" class="form-select">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= (date('m') == $m) ? 'selected' : '' ?>><?= strftime('%B', mktime(0, 0, 0, $m, 1)) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="annee" class="form-label"><?= _('Année') ?></label>
                    <select name="annee" id="annee" class="form-select">
                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?= $y ?>"><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100"><?= _('Lancer la Génération') ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des Salaires -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><?= _('Historique des Salaires') ?></h6>
             <a href="/salaires/create" class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i> <?= _('Ajouter un paiement manuel') ?>
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><?= _('Période') ?></th>
                            <th><?= _('Membre du personnel') ?></th>
                            <th><?= _('Montant') ?></th>
                            <th><?= _('Heures Trav.') ?></th>
                            <th><?= _('État') ?></th>
                            <th><?= _('Date de Paiement') ?></th>
                            <th><?= _('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salaires as $salaire): ?>
                            <tr>
                                <td><?= htmlspecialchars($salaire['periode_mois'] . '/' . $salaire['periode_annee']) ?></td>
                                <td><?= htmlspecialchars($salaire['prenom'] . ' ' . $salaire['nom']) ?></td>
                                <td><?= number_format($salaire['montant'], 2) ?> XAF</td>
                                <td><?= $salaire['nb_heures_travaillees'] ? number_format($salaire['nb_heures_travaillees'], 2) : 'N/A' ?></td>
                                <td>
                                    <?php if ($salaire['etat_paiement'] == 'paye'): ?>
                                        <span class="badge bg-success"><?= _('Payé') ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><?= _('Non Payé') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $salaire['date_paiement'] ? htmlspecialchars(date('d/m/Y', strtotime($salaire['date_paiement']))) : '---' ?></td>
                                <td class="text-center">
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