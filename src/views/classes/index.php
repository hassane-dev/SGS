<?php
$title = "Gestion des Classes";
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Gestion des Classes</h1>
    <?php if (Auth::can('create_classes')): ?>
    <a href="/classes/create" class="btn btn-primary">
        <i class="fas fa-plus"></i> Ajouter une Classe
    </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Nom de la Classe</th>
                    <th>Cycle</th>
                    <?php if (Auth::get('role_name') === 'super_admin_national'): ?>
                        <th>Lycée</th>
                    <?php endif; ?>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($classes)): ?>
                    <tr>
                        <td colspan="<?= Auth::get('role_name') === 'super_admin_national' ? '4' : '3' ?>" class="text-center">Aucune classe trouvée.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($classes as $classe): ?>
                        <tr>
                            <td><?= htmlspecialchars($classe['nom_classe'] . ' (' . $classe['serie'] . ')') ?></td>
                            <td><?= htmlspecialchars($classe['nom_cycle']) ?></td>
                            <?php if (Auth::get('role_name') === 'super_admin_national'): ?>
                                <td><?= htmlspecialchars($classe['nom_lycee']) ?></td>
                            <?php endif; ?>
                            <td class="text-end">
                                <?php if (Auth::can('edit_classes')): ?>
                                    <a href="/classes/edit?id=<?= $classe['id_classe'] ?>" class="btn btn-sm btn-warning" title="Modifier"><i class="fas fa-edit"></i></a>
                                <?php endif; ?>
                                <?php if (Auth::can('delete_classes')): ?>
                                <form action="/classes/destroy" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr ?');">
                                    <input type="hidden" name="id" value="<?= $classe['id_classe'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Supprimer"><i class="fas fa-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>