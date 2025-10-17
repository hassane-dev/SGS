<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mt-4">
    <h1>Résultats de la recherche pour "<?= htmlspecialchars($term) ?>"</h1>

    <a href="/reinscription" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Nouvelle recherche</a>

    <div class="card">
        <div class="card-body">
            <?php if (empty($eleves)): ?>
                <div class="alert alert-warning">Aucun élève trouvé.</div>
            <?php else: ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Date de Naissance</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eleves as $eleve): ?>
                            <tr>
                                <td><?= htmlspecialchars($eleve['matricule']) ?></td>
                                <td><?= htmlspecialchars($eleve['nom']) ?></td>
                                <td><?= htmlspecialchars($eleve['prenom']) ?></td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($eleve['date_naissance']))) ?></td>
                                <td class="text-end">
                                    <a href="/reinscription/confirm?eleve_id=<?= $eleve['id_eleve'] ?>" class="btn btn-primary">
                                        Réinscrire <i class="fas fa-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>