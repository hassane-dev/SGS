<?php
$title = "Valider l'Inscription";
ob_start();
?>

<div class="container">
    <h1>Valider l'Inscription et le Paiement</h1>

    <div class="card mt-4">
        <div class="card-header">
            <h4>Récapitulatif</h4>
        </div>
        <div class="card-body">
            <p><strong>Élève:</strong> <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></p>
            <p><strong>Classe:</strong> <?= htmlspecialchars($etude['nom_classe'] . ' (' . $etude['niveau'] . ' ' . $etude['serie'] . ')') ?></p>
            <p><strong>Année Académique:</strong> <?= htmlspecialchars($activeYear['libelle']) ?></p>
            <hr>
            <p class="font-weight-bold">Montant Total de l'Inscription: <?= htmlspecialchars(number_format($frais['frais_inscription'], 2)) ?> XOF</p>
        </div>
    </div>

    <form action="/comptable/validate" method="POST" class="mt-4">
        <input type="hidden" name="eleve_id" value="<?= $eleve['id_eleve'] ?>">
        <input type="hidden" name="etude_id" value="<?= $etude['id_etude'] ?>">
        <input type="hidden" name="classe_id" value="<?= $etude['classe_id'] ?>">

        <div class="form-group">
            <label for="montant_verse">Montant Versé</label>
            <input type="number" step="0.01" class="form-control" id="montant_verse" name="montant_verse" value="<?= $frais['frais_inscription'] ?>" required>
            <small class="form-text text-muted">Le montant total des frais d'inscription est pré-rempli.</small>
        </div>

        <button type="submit" class="btn btn-success mt-4">Valider l'Inscription et Activer l'Élève</button>
        <a href="/comptable/pending" class="btn btn-secondary mt-4">Annuler</a>
    </form>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/header.php';
?>