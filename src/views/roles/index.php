<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Gestion des Rôles') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-end">
                            <a href="/roles/create" class="btn btn-primary">
                                <?= _('Ajouter un rôle') ?>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?= _('Nom du Rôle') ?></th>
                                        <th><?= _('Portée') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($roles)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center"><?= _('Aucun rôle trouvé.') ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($roles as $role): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($role['nom_role']) ?></td>
                                                <td>
                                                    <?= $role['lycee_id'] ? htmlspecialchars($role['nom_lycee']) : _('Global') ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php if ($role['id_role'] > 6): // Basic protection for default roles ?>
                                                        <a href="/roles/edit?id=<?= $role['id_role'] ?>" class="btn btn-sm btn-primary ms-2"><?= _('Modifier') ?></a>
                                                        <form action="/roles/destroy" method="POST" class="d-inline ms-2" onsubmit="return confirm('<?= _('Êtes-vous sûr ?') ?>');">
                                                            <input type="hidden" name="id" value="<?= $role['id_role'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger"><?= _('Supprimer') ?></button>
                                                        </form>
                                                    <?php else: ?>
                                                        <a href="/roles/edit?id=<?= $role['id_role'] ?>" class="btn btn-sm btn-info"><?= _('Voir les Permissions') ?></a>
                                                    <?php endif; ?>
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
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
