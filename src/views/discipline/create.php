<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Signaler un Incident Disciplinaire</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Nouveau Rapport</h6>
        </div>
        <div class="card-body">
            <form action="/discipline/store" method="POST">
                <div class="form-group">
                    <label for="eleve_id">Élève concerné</label>
                    <select name="eleve_id" id="eleve_id" class="form-control" required>
                        <option value="">-- Sélectionner un élève --</option>
                        <?php foreach ($eleves as $eleve): ?>
                            <option value="<?= $eleve['id_eleve'] ?>">
                                <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="type">Type d'incident</label>
                    <select name="type" id="type" class="form-control" required>
                        <option value="Avertissement">Avertissement</option>
                        <option value="Blâme">Blâme</option>
                        <option value="Exclusion temporaire">Exclusion temporaire</option>
                        <option value="Encouragement">Encouragement</option>
                        <option value="Félicitation">Félicitation</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description de l'incident</label>
                    <textarea name="description" id="description" rows="5" class="form-control" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Enregistrer le rapport</button>
            </form>
        </div>
    </div>
</div>
