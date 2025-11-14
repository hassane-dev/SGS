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
                            <h2 class="mb-0"><?= _('Gestion des Cycles') ?></h2>
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
                            <a href="/cycles/create" class="btn btn-primary">
                                <?= _('Ajouter un Cycle') ?>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error) && $error === 'delete_failed'): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= _('Impossible de supprimer ce cycle car il est utilisé par une ou plusieurs classes.') ?>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?= _('Nom du Cycle') ?></th>
                                        <th><?= _('Niveau de Début') ?></th>
                                        <th><?= _('Niveau de Fin') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($cycles)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center"><?= _('Aucun cycle trouvé.') ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($cycles as $cycle): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($cycle['nom_cycle']) ?></td>
                                                <td><?= htmlspecialchars($cycle['niveau_debut']) ?></td>
                                                <td><?= htmlspecialchars($cycle['niveau_fin']) ?></td>
                                                <td class="text-end">
                                                    <a href="/cycles/edit?id=<?= $cycle['id_cycle'] ?>" class="btn btn-sm btn-primary"><?= _('Modifier') ?></a>
                                                    <form action="/cycles/destroy" method="POST" class="d-inline ms-2" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir supprimer ce cycle ?') ?>');">
                                                        <input type="hidden" name="id" value="<?= $cycle['id_cycle'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger"><?= _('Supprimer') ?></button>
                                                    </form>
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
