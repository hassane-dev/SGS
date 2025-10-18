<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Saisie des Notes</h1>
    <p class="mb-4">
        Classe: <strong><?= htmlspecialchars($classe['nom_classe']) ?></strong> |
        Matière: <strong><?= htmlspecialchars($matiere['nom_matiere']) ?></strong> |
        Coefficient: <strong><?= htmlspecialchars($coefficient) ?></strong>
    </p>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Liste des Élèves</h6>
        </div>
        <div class="card-body">
            <form action="/evaluations/save" method="POST">
                <input type="hidden" name="classe_id" value="<?= $classe['id_classe'] ?>">
                <input type="hidden" name="matiere_id" value="<?= $matiere['id_matiere'] ?>">
                <input type="hidden" name="sequence_id" value="<?= $sequence_id ?>">
                <input type="hidden" name="coefficient" value="<?= $coefficient ?>">

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Nom de l'élève</th>
                                <th>Note / 20</th>
                                <th>Appréciation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eleves as $eleve):
                                $grade = $grades[$eleve['id_eleve']] ?? null;
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></td>
                                    <td>
                                        <input type="number"
                                               step="0.25"
                                               min="0"
                                               max="20"
                                               class="form-control"
                                               name="grades[<?= $eleve['id_eleve'] ?>][note]"
                                               value="<?= htmlspecialchars($grade['note'] ?? '') ?>">
                                    </td>
                                    <td>
                                        <input type="text"
                                               class="form-control"
                                               name="grades[<?= $eleve['id_eleve'] ?>][appreciation]"
                                               value="<?= htmlspecialchars($grade['appreciation'] ?? '') ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-primary">Enregistrer les Notes</button>
                <a href="/evaluations/select_class" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</div>