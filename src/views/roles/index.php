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
                                                    <?php
                                                    $user_role_name = Auth::get('role_name');
                                                    $is_local_admin = ($user_role_name === 'admin_local');
                                                    $is_super_admin = ($user_role_name === 'super_admin_createur' || $user_role_name === 'super_admin_national');

                                                    $target_role_id = $role['id_role'];
                                                    $is_target_super_admin = in_array($target_role_id, [1, 2]);
                                                    $is_target_default = $target_role_id <= 8;

                                                    // Determine if current user can edit this role
                                                    $can_edit = false;
                                                    if ($is_super_admin) {
                                                        $can_edit = true; // Super admin can edit everything
                                                    } elseif ($is_local_admin) {
                                                        // Local admin can edit non-super-admin roles
                                                        if (!$is_target_super_admin) {
                                                            $can_edit = true;
                                                        }
                                                    }

                                                    if ($can_edit): ?>
                                                        <a href="/roles/edit?id=<?= $target_role_id ?>" class="btn btn-sm btn-primary ms-2">
                                                            <?= $is_target_default ? _('Modifier Permissions') : _('Modifier') ?>
                                                        </a>
                                                        <?php if (!$is_target_default): ?>
                                                            <form action="/roles/destroy" method="POST" class="d-inline ms-2" onsubmit="return confirm('<?= _('Êtes-vous sûr ?') ?>');">
                                                                <input type="hidden" name="id" value="<?= $target_role_id ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger"><?= _('Supprimer') ?></button>
                                                            </form>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-secondary"><?= _('Protégé') ?></span>
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
