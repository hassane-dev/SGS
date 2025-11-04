<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Gestion des Classes</h1>
        <?php if (Auth::can('create', 'class')): ?>
            <a href="/classes/create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouvelle Classe
            </a>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Liste des Classes</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nom de la classe</th>
                            <th>Niveau</th>
                            <th>Série</th>
                            <th>Cycle</th>
                            <?php if (Auth::can('view_all_lycees', 'system')): ?>
                                <th>Lycée</th>
                            <?php endif; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $classe): ?>
                            <tr>
                                <td><?= htmlspecialchars($classe['nom_classe']) ?></td>
                                <td><?= htmlspecialchars($classe['niveau']) ?></td>
                                <td><?= htmlspecialchars($classe['serie'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($classe['nom_cycle']) ?></td>
                                <?php if (Auth::can('view_all_lycees', 'system')): ?>
                                    <td><?= htmlspecialchars($classe['nom_lycee']) ?></td>
                                <?php endif; ?>
                                <td>
                                    <?php if (Auth::can('view', 'class')): ?>
                                        <a href="/classes/show?id=<?= $classe['id_classe'] ?>" class="btn btn-sm btn-info" title="Détails et matières">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (Auth::can('edit', 'class')): ?>
                                        <a href="/classes/edit?id=<?= $classe['id_classe'] ?>" class="btn btn-sm btn-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (Auth::can('delete', 'class')): ?>
                                        <form action="/classes/destroy" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette classe ?');">
                                            <input type="hidden" name="id" value="<?= $classe['id_classe'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
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