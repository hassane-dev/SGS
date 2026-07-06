<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Saisie des Notes</h1>

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
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Classe</th>
                                <th>Matière</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjects_taught as $subject): ?>
                                <tr>
                                    <td><?= htmlspecialchars($subject['nom_classe']) ?></td>
                                    <td><?= htmlspecialchars($subject['nom_matiere']) ?></td>
                                    <td>
                                        <a href="/notes/saisir/<?= $subject['classe_id'] ?>/<?= $subject['matiere_id'] ?>" class="btn btn-primary btn-sm">
                                            Saisir les notes
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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