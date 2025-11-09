<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Tableau de Bord de l'Assiduité</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtrer les Données</h6>
        </div>
        <div class="card-body">
            <form action="/assiduite" method="GET" class="form-inline">
                <div class="form-group mr-3">
                    <label for="date_cours" class="mr-2">Date:</label>
                    <input type="date" id="date_cours" name="date_cours" class="form-control" value="<?= htmlspecialchars($filters['date_cours']) ?>">
                </div>
                <div class="form-group mr-3">
                    <label for="classe_id" class="mr-2">Classe:</label>
                    <select id="classe_id" name="classe_id" class="form-control">
                        <option value="">Toutes les classes</option>
                        <?php foreach($classes as $classe): ?>
                            <option value="<?= $classe['id_classe'] ?>" <?= ($filters['classe_id'] == $classe['id_classe']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($classe['nom_classe']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div class="form-group mr-3">
                    <label for="statut" class="mr-2">Statut:</label>
                    <select id="statut" name="statut" class="form-control">
                        <option value="Absent" <?= ($filters['statut'] == 'Absent') ? 'selected' : '' ?>>Absents</option>
                        <option value="Retard" <?= ($filters['statut'] == 'Retard') ? 'selected' : '' ?>>En retard</option>
                        <option value="Présent" <?= ($filters['statut'] == 'Présent') ? 'selected' : '' ?>>Présents</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filtrer</button>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Résultats</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Élève</th>
                            <th>Classe</th>
                            <th>Statut</th>
                            <th>Heure</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assiduite_records)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Aucune donnée à afficher pour les filtres sélectionnés.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assiduite_records as $record): ?>
                                <tr>
                                    <td><?= htmlspecialchars($record['date_cours']) ?></td>
                                    <td><?= htmlspecialchars($record['eleve_prenom'] . ' ' . $record['eleve_nom']) ?></td>
                                    <td><?= htmlspecialchars($record['nom_classe']) ?></td>
                                    <td>
                                        <?php
                                            $badge_class = 'badge-secondary';
                                            if ($record['statut'] == 'Présent') $badge_class = 'badge-success';
                                            if ($record['statut'] == 'Absent') $badge_class = 'badge-danger';
                                            if ($record['statut'] == 'Retard') $badge_class = 'badge-warning';
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($record['statut']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars(substr($record['heure_debut'], 0, 5)) ?> - <?= htmlspecialchars(substr($record['heure_fin'], 0, 5)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
