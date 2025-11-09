<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Faire l'appel pour la classe de <?= htmlspecialchars($classe['nom_classe']) ?></h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Liste des élèves</h6>
        </div>
        <div class="card-body">
            <form action="/assiduite/store" method="POST">
                <input type="hidden" name="classe_id" value="<?= $classe['id_classe'] ?>">
                <!-- These should probably be more dynamic -->
                <input type="hidden" name="date_cours" value="<?= date('Y-m-d') ?>">
                <input type="hidden" name="heure_debut" value="08:00"> <!-- Placeholder -->
                <input type="hidden" name="heure_fin" value="09:00">   <!-- Placeholder -->

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Élève</th>
                                <th class="text-center">Présent</th>
                                <th class="text-center">Absent</th>
                                <th class="text-center">En retard</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eleves as $eleve): ?>
                                <tr>
                                    <td><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></td>
                                    <td class="text-center">
                                        <input type="radio" name="presences[<?= $eleve['id_eleve'] ?>]" value="Présent" checked>
                                    </td>
                                    <td class="text-center">
                                        <input type="radio" name="presences[<?= $eleve['id_eleve'] ?>]" value="Absent">
                                    </td>
                                    <td class="text-center">
                                        <input type="radio" name="presences[<?= $eleve['id_eleve'] ?>]" value="Retard">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-success">Enregistrer l'appel</button>
            </form>
        </div>
    </div>
</div>
