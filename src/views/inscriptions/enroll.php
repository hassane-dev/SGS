<?php
$title = "Inscrire l'Élève";
ob_start();
?>

<div class="container">
    <h1>Inscrire l'Élève : <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h1>
    <p>Année Académique : <strong><?= htmlspecialchars($activeYear['libelle']) ?></strong></p>

    <form action="/inscriptions/enroll" method="POST">
        <input type="hidden" name="eleve_id" value="<?= $eleve['id_eleve'] ?>">

        <div class="form-group">
            <label for="classe_id">Classe</label>
            <select class="form-control" id="classe_id" name="classe_id" required>
                <option value="">-- Sélectionner une classe --</option>
                <?php foreach ($classes as $classe): ?>
                    <option value="<?= $classe['id_classe'] ?>">
                        <?= htmlspecialchars($classe['nom_classe'] . ' (' . $classe['niveau'] . ' ' . $classe['serie'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group mt-3">
            <label for="montant_verse">Montant Versé (Première Tranche)</label>
            <input type="number" step="0.01" class="form-control" id="montant_verse" name="montant_verse" required>
        </div>

        <p class="mt-3 text-muted">
            Note: Les frais exacts seront calculés en fonction de la grille tarifaire définie pour la classe sélectionnée.
        </p>

        <button type="submit" class="btn btn-primary mt-4">Inscrire</button>
        <a href="/eleves/details?id=<?= $eleve['id_eleve'] ?>" class="btn btn-secondary mt-4">Annuler</a>
    </form>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/header.php';
?>