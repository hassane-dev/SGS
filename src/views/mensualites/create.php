<?php
$title = "Enregistrer un Paiement Mensuel";
ob_start();
?>

<div class="container">
    <h1>Enregistrer un Paiement pour : <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h1>
    <p>Année Académique : <strong><?= htmlspecialchars($activeYear['libelle']) ?></strong></p>

    <form action="/mensualites/store" method="POST">
        <input type="hidden" name="eleve_id" value="<?= $eleve['id_eleve'] ?>">

        <div class="form-group">
            <label for="mois_ou_sequence">Mois ou Séquence</label>
            <input type="text" class="form-control" id="mois_ou_sequence" name="mois_ou_sequence" placeholder="Ex: Octobre, 1er Trimestre" required>
        </div>

        <div class="form-group mt-3">
            <label for="montant_verse">Montant Versé</label>
            <input type="number" step="0.01" class="form-control" id="montant_verse" name="montant_verse" required>
        </div>

        <button type="submit" class="btn btn-primary mt-4">Enregistrer le Paiement</button>
        <a href="/eleves/details?id=<?= $eleve['id_eleve'] ?>" class="btn btn-secondary mt-4">Annuler</a>
    </form>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/header.php';
?>