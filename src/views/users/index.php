<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fs-2 fw-bold"><?= _('Gestion du personnel') ?></h2>
    <a href="/users/create" class="btn btn-primary">
        <?= _('Ajouter un membre') ?>
    </a>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] == 'delete'): ?>
    <div class="alert alert-success"><?= _('Le membre du personnel a été supprimé avec succès.') ?></div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="alert alert-danger"><?= _('Une erreur est survenue lors de la suppression.') ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th scope="col"><?= _('Nom') ?></th>
                    <th scope="col"><?= _('Fonction') ?></th>
                    <th scope="col"><?= _('Statut') ?></th>
                    <th scope="col" class="text-end"><?= _('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="4" class="text-center"><?= _('Aucun membre du personnel trouvé.') ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></td>
                            <td><?= htmlspecialchars($user['nom_role'] ?? 'N/A') ?></td>
                            <td>
                                <?php if ($user['actif']): ?>
                                    <span class="badge bg-success"><?= _('Actif') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><?= _('Inactif') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="/users/view?id=<?= $user['id_user'] ?>" class="btn btn-sm btn-outline-info"><?= _('Voir') ?></a>
                                <?php if (Auth::get('id_user') != $user['id_user']): // Prevent self-action links ?>
                                    <a href="/users/edit?id=<?= $user['id_user'] ?>" class="btn btn-sm btn-outline-primary ms-2"><?= _('Modifier') ?></a>
                                    <form action="/users/destroy" method="POST" class="d-inline ms-2" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir supprimer ce membre ?') ?>');">
                                        <input type="hidden" name="id" value="<?= $user['id_user'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><?= _('Supprimer') ?></button>
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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>