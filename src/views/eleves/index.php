<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Gestion des Élèves Actifs</h1>
        <div>
            <a href="/eleves/archives" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-archive fa-sm text-white-50"></i> Archives
            </a>
            <a href="/eleves/create" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-user-plus fa-sm text-white-50"></i> Ajouter un Élève
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Liste des élèves actifs et en attente</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Nom Complet</th>
                            <th>Email</th>
                            <th>Classes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($eleves)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Aucun élève actif ou en attente trouvé.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($eleves as $eleve): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($eleve['photo'])): ?>
                                            <img src="<?= htmlspecialchars($eleve['photo']) ?>" alt="Photo de l'élève" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></td>
                                    <td><?= htmlspecialchars($eleve['email']) ?></td>
                                    <td><?= htmlspecialchars($eleve['classes']) ?></td>
                                    <td>
                                        <a href="/eleves/details?id=<?= $eleve['id_eleve'] ?>" class="btn btn-info btn-sm" title="Dossier complet"><i class="fas fa-info-circle"></i></a>
                                        <a href="/eleves/edit?id=<?= $eleve['id_eleve'] ?>" class="btn btn-warning btn-sm" title="Modifier"><i class="fas fa-edit"></i></a>
                                        <form action="/eleves/destroy" method="POST" class="d-inline ml-1" onsubmit="return confirm('Êtes-vous sûr de vouloir radier cet élève ? Cette action est réversible.');">
                                            <input type="hidden" name="id" value="<?= $eleve['id_eleve'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Radier l'élève"><i class="fas fa-user-slash"></i></button>
                                        </form>
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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
