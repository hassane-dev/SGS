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
                            <h2 class="mb-0"><?= _('Gestion du personnel') ?></h2>
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
                            <a href="/users/create" class="btn btn-primary">
                                <?= _('Ajouter un membre') ?>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
                            <?php unset($_SESSION['error_message']); ?>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?= _('Nom') ?></th>
                                        <th><?= _('Fonction') ?></th>
                                        <th><?= _('Statut') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
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
                                                        <span class="badge bg-light-success"><?= _('Actif') ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-danger"><?= _('Inactif') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <a href="/users/view?id=<?= $user['id_user'] ?>" class="btn btn-sm btn-info"><?= _('Voir') ?></a>
                                                    <?php if (Auth::get('id_user') != $user['id_user']): ?>
                                                        <a href="/users/edit?id=<?= $user['id_user'] ?>" class="btn btn-sm btn-primary ms-2"><?= _('Modifier') ?></a>
                                                        <form action="/users/destroy" method="POST" class="d-inline ms-2" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir supprimer ce membre ?') ?>');">
                                                            <input type="hidden" name="id" value="<?= $user['id_user'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger"><?= _('Supprimer') ?></button>
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
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
