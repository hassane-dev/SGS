<?php
$title = "Gestion des Élèves";
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Gestion des Élèves</h1>
    <?php if (Auth::can('create_eleves')): // Assuming this permission exists ?>
    <a href="/eleves/create" class="btn btn-primary">
        <i class="fas fa-plus"></i> Ajouter un Élève
    </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Photo</th>
                    <th>Nom Complet</th>
                    <th>Email</th>
                    <th>Statut</th>
                    <th>Classes</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($eleves)): ?>
                    <tr>
                        <td colspan="6" class="text-center">Aucun élève trouvé.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($eleves as $eleve): ?>
                        <tr>
                            <td>
                                <?php if (!empty($eleve['photo'])): ?>
                                    <img src="<?= htmlspecialchars($eleve['photo']) ?>" alt="Photo" class="rounded-circle" width="40" height="40">
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></td>
                            <td><?= htmlspecialchars($eleve['email']) ?></td>
                            <td>
                                <?php if ($eleve['statut'] == 'actif'): ?>
                                    <span class="badge bg-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><?= htmlspecialchars($eleve['statut']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($eleve['classes']) ?></td>
                            <td class="text-end">
                                <a href="/eleves/details?id=<?= $eleve['id_eleve'] ?>" class="btn btn-sm btn-info" title="Détails"><i class="fas fa-eye"></i></a>
                                <?php if (Auth::can('edit_eleves')): ?>
                                    <a href="/eleves/edit?id=<?= $eleve['id_eleve'] ?>" class="btn btn-sm btn-warning" title="Modifier"><i class="fas fa-edit"></i></a>
                                <?php endif; ?>
                                <?php if (Auth::can('delete_eleves')): ?>
                                    <form action="/eleves/destroy" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr ?');">
                                        <input type="hidden" name="id" value="<?= $eleve['id_eleve'] ?>">
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