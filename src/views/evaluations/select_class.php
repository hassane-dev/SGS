<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Saisie des Notes (Étape 1/2)</h1>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'grades_saved'): ?>
        <div class="alert alert-success">
            Les notes ont été enregistrées avec succès.
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Veuillez choisir une classe et une matière</h6>
        </div>
        <div class="card-body">
            <?php if (empty($subjects_taught)): ?>
                <div class="alert alert-warning">
                    Aucune matière ne vous est actuellement assignée. Veuillez contacter l'administration.
                </div>
            <?php else: ?>
                <form action="/evaluations/select_evaluation" method="POST">
                    <div class="form-group">
                        <label for="class_subject">Classe et Matière</label>
                        <select id="class_subject" class="form-control" required onchange="updateSelection(this)">
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($subjects_taught as $subject): ?>
                                <option value="<?= $subject['classe_id'] ?>|<?= $subject['matiere_id'] ?>">
                                    <?= htmlspecialchars($subject['nom_classe']) ?> - <?= htmlspecialchars($subject['nom_matiere']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <input type="hidden" name="classe_id" id="classe_id">
                    <input type="hidden" name="matiere_id" id="matiere_id">

                    <button type="submit" class="btn btn-primary mt-3">Continuer</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function updateSelection(select) {
    const [classeId, matiereId] = select.value.split('|');
    document.getElementById('classe_id').value = classeId || '';
    document.getElementById('matiere_id').value = matiereId || '';
}
</script>