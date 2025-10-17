<h1 class="mb-4">Gestion des Permissions</h1>

<a href="/permissions/create" class="btn btn-primary mb-3">
    <i class="fas fa-plus"></i> Créer une nouvelle permission
</a>

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Ressource</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($permissions as $permission): ?>
                    <tr>
                        <td><?= htmlspecialchars($permission['resource']) ?></td>
                        <td><?= htmlspecialchars($permission['action']) ?></td>
                        <td><?= htmlspecialchars($permission['description'] ?? '') ?></td>
                        <td class="text-end">
                            <a href="/permissions/edit?id=<?= $permission['id_permission'] ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            <form action="/permissions/destroy" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette permission ?');">
                                <input type="hidden" name="id" value="<?= $permission['id_permission'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>