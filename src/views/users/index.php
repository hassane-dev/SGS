<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= _('Gestion du Personnel') ?></h1>
        <a href="/users/create" class="btn btn-primary">
            <i class="fas fa-plus"></i> <?= _('Ajouter un membre') ?>
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary"><?= _('Liste du Personnel') ?></h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><?= _('Nom Complet') ?></th>
                            <th><?= _('Fonction') ?></th>
                            <th><?= _('Rôle') ?></th>
                            <th><?= _('Statut') ?></th>
                            <th class="text-end"><?= _('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></td>
                                <td><?= htmlspecialchars($user['fonction'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['nom_role'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if ($user['actif']): ?>
                                        <span class="badge bg-success"><?= _('Actif') ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><?= _('Inactif') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="/users/view?id=<?= $user['id_user'] ?>" class="btn btn-info btn-sm" title="<?= _('Voir le profil') ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (Auth::get('id') != $user['id_user']): ?>
                                        <?php if ($user['nom_role'] === 'enseignant'): ?>
                                            <a href="/users/assign?id=<?= $user['id_user'] ?>" class="btn btn-warning btn-sm" title="<?= _('Assigner des cours') ?>">
                                                <i class="fas fa-chalkboard-teacher"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="/users/edit?id=<?= $user['id_user'] ?>" class="btn btn-secondary btn-sm" title="<?= _('Modifier') ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="/users/destroy" method="POST" class="d-inline" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir supprimer ce membre ?') ?>');">
                                            <input type="hidden" name="id" value="<?= $user['id_user'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="<?= _('Supprimer') ?>">
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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>