<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Gestion des Matières</h1>
        <?php if (Auth::can('create', 'matiere')): ?>
            <a href="/matieres/create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouvelle Matière
            </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'delete_failed'): ?>
        <div class="alert alert-danger">
            La suppression a échoué. La matière est probablement utilisée dans une ou plusieurs classes ou par un enseignant.
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Liste des Matières</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nom de la matière</th>
                            <th>Type</th>
                            <th>Cycle Concerné</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matieres as $matiere): ?>
                            <tr>
                                <td><?= htmlspecialchars($matiere['nom_matiere']) ?></td>
                                <td><?= htmlspecialchars($matiere['type'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($matiere['cycle_concerne'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge badge-<?= $matiere['statut'] === 'principale' ? 'success' : 'warning' ?>">
                                        <?= ucfirst(htmlspecialchars($matiere['statut'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (Auth::can('edit', 'matiere')): ?>
                                        <a href="/matieres/edit?id=<?= $matiere['id_matiere'] ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (Auth::can('delete', 'matiere')): ?>
                                        <form action="/matieres/delete" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette matière ?');">
                                            <input type="hidden" name="id" value="<?= $matiere['id_matiere'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>