<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mt-4">
    <h1>Confirmer la Réinscription</h1>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'enrolled'): ?>
        <div class="alert alert-danger">Cet élève est déjà inscrit pour l'année académique en cours.</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            Élève : <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?> (Matricule: <?= htmlspecialchars($eleve['matricule']) ?>)
        </div>
        <div class="card-body">
            <form action="/reinscription/process" method="POST">
                <input type="hidden" name="eleve_id" value="<?= $eleve['id_eleve'] ?>">
                <input type="hidden" name="annee_academique_id" value="<?= $active_year['id'] ?>">

                <p>Année académique pour la réinscription : <strong><?= htmlspecialchars($active_year['libelle']) ?></strong></p>

                <div class="mb-3">
                    <label for="classe_id" class="form-label">Sélectionner la nouvelle classe</label>
                    <select class="form-select" id="classe_id" name="classe_id" required>
                        <option value="">-- Choisir une classe --</option>
                        <?php foreach ($classes as $classe): ?>
                            <option value="<?= $classe['id_classe'] ?>"><?= htmlspecialchars($classe['nom_classe']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mt-4">
                    <a href="/reinscription" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">Confirmer la Réinscription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>