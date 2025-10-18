<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Paramètres des Évaluations</h1>
    <p class="mb-4">
        Classe: <strong><?= htmlspecialchars($classe['nom_classe']) ?></strong> |
        Matière: <strong><?= htmlspecialchars($matiere['nom_matiere']) ?></strong>
    </p>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Définir les périodes de saisie des notes</h6>
        </div>
        <div class="card-body">
            <form action="/evaluations/settings/save" method="POST">
                <input type="hidden" name="classe_id" value="<?= $classe['id_classe'] ?>">
                <input type="hidden" name="matiere_id" value="<?= $matiere['id_matiere'] ?>">
                <input type="hidden" name="enseignant_id" value="<?= $enseignant_id ?>">

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Séquence</th>
                                <th>Date d'Ouverture de la Saisie</th>
                                <th>Date de Fermeture de la Saisie</th>
                                <th>Commentaire</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sequences as $sequence):
                                $setting = $existing_settings[$sequence['id']] ?? null;
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($sequence['nom']) ?></td>
                                    <td>
                                        <input type="datetime-local"
                                               class="form-control"
                                               name="settings[<?= $sequence['id'] ?>][date_ouverture]"
                                               value="<?= !empty($setting['date_ouverture_saisie']) ? (new DateTime($setting['date_ouverture_saisie']))->format('Y-m-d\TH:i') : '' ?>">
                                    </td>
                                    <td>
                                        <input type="datetime-local"
                                               class="form-control"
                                               name="settings[<?= $sequence['id'] ?>][date_fermeture]"
                                               value="<?= !empty($setting['date_fermeture_saisie']) ? (new DateTime($setting['date_fermeture_saisie']))->format('Y-m-d\TH:i') : '' ?>">
                                    </td>
                                    <td>
                                        <input type="text"
                                               class="form-control"
                                               name="settings[<?= $sequence['id'] ?>][commentaire]"
                                               placeholder="Ex: Devoir 1, Composition"
                                               value="<?= htmlspecialchars($setting['commentaire'] ?? '') ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-primary">Enregistrer les Paramètres</button>
                <a href="/classes/show?id=<?= $classe['id_classe'] ?>" class="btn btn-secondary">Retour à la classe</a>
            </form>
        </div>
    </div>
</div>