<?php
$title = "Détails de l'Élève";
ob_start();
?>

<div class="container">
    <div class="row">
        <div class="col-md-8">
            <h1>Détails de l'Élève</h1>
        </div>
        <div class="col-md-4 text-right">
            <a href="/inscriptions/show-form?eleve_id=<?= $eleve['id_eleve'] ?>" class="btn btn-primary">Inscrire à une Classe</a>
            <a href="/mensualites/show-form?eleve_id=<?= $eleve['id_eleve'] ?>" class="btn btn-success">Payer Mensualité</a>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h4>Informations Personnelles</h4>
        </div>
        <div class="card-body">
            <p><strong>Nom & Prénom:</strong> <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></p>
            <p><strong>Date de Naissance:</strong> <?= htmlspecialchars($eleve['date_naissance']) ?></p>
            <p><strong>Lieu de Naissance:</strong> <?= htmlspecialchars($eleve['lieu_naissance']) ?></p>
            <p><strong>Sexe:</strong> <?= htmlspecialchars($eleve['sexe']) ?></p>
            <p><strong>Nationalité:</strong> <?= htmlspecialchars($eleve['nationalite']) ?></p>
            <p><strong>Adresse:</strong> <?= htmlspecialchars($eleve['quartier']) ?></p>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h4>Historique des Inscriptions</h4>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Année Académique</th>
                        <th>Classe</th>
                        <th>Montant Total</th>
                        <th>Montant Versé</th>
                        <th>Reste à Payer</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inscriptions as $inscription): ?>
                        <tr>
                            <td><?= htmlspecialchars($inscription['annee_academique']) ?></td>
                            <td><?= htmlspecialchars($inscription['nom_classe']) ?></td>
                            <td><?= htmlspecialchars(number_format($inscription['montant_total'], 2)) ?></td>
                            <td><?= htmlspecialchars(number_format($inscription['montant_verse'], 2)) ?></td>
                            <td class="<?= $inscription['reste_a_payer'] > 0 ? 'text-danger' : 'text-success' ?>">
                                <?= htmlspecialchars(number_format($inscription['reste_a_payer'], 2)) ?>
                            </td>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($inscription['date_inscription']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h4>Historique des Paiements Mensuels</h4>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Année Académique</th>
                        <th>Mois/Séquence</th>
                        <th>Montant Versé</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mensualites as $mensualite): ?>
                        <tr>
                            <td><?= htmlspecialchars($mensualite['annee_academique']) ?></td>
                            <td><?= htmlspecialchars($mensualite['mois_ou_sequence']) ?></td>
                            <td><?= htmlspecialchars(number_format($mensualite['montant_verse'], 2)) ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($mensualite['date_paiement']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <a href="/eleves" class="btn btn-secondary mt-4">Retour à la liste</a>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/header.php';
?>