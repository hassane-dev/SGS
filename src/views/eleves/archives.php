<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Élèves Archivés</h1>
    <p>Cette page liste les élèves qui ne sont plus actifs dans l'établissement (transférés, radiés, diplômés, etc.).</p>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Liste des Élèves Archivés</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Sexe</th>
                            <th>Date de Naissance</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eleves as $eleve): ?>
                            <tr>
                                <td><?= htmlspecialchars($eleve['nom']) ?></td>
                                <td><?= htmlspecialchars($eleve['prenom']) ?></td>
                                <td><?= htmlspecialchars($eleve['sexe']) ?></td>
                                <td><?= htmlspecialchars($eleve['date_naissance']) ?></td>
                                <td>
                                    <span class="badge badge-secondary"><?= htmlspecialchars($eleve['statut']) ?></span>
                                </td>
                                <td>
                                    <a href="/eleves/details?id=<?= $eleve['id_eleve'] ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-info-circle"></i> Consulter le dossier
                                    </a>
                                    <!-- Option to restore student could be added here -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
