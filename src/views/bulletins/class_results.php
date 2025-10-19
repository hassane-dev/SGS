<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Résultats pour la classe : <?= htmlspecialchars($classe['nom_classe']) ?></h1>
    <p class="mb-4">
        Séquence : <strong><?= htmlspecialchars($sequence['nom']) ?></strong>
    </p>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Moyennes des Élèves</h6>
            <a href="/bulletins" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Retour à la sélection
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nom de l'élève</th>
                            <th>Moyenne Générale</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($results)): ?>
                            <tr>
                                <td colspan="3" class="text-center">Aucun résultat trouvé pour cette sélection. Assurez-vous que des notes ont été saisies.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($results as $result): ?>
                                <tr>
                                    <td><?= htmlspecialchars($result['prenom'] . ' ' . $result['nom']) ?></td>
                                    <td><strong><?= number_format($result['moyenne_generale'], 2) ?> / 20</strong></td>
                                    <td>
                                        <a href="/bulletins/student?eleve_id=<?= $result['id_eleve'] ?>&sequence_id=<?= $sequence['id'] ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Voir le bulletin
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>