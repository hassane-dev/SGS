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
                            <h2 class="mb-0"><?= _('Gestion des Classes') ?></h2>
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
                            <?php if (Auth::can('create', 'class')): ?>
                                <a href="/classes/create" class="btn btn-primary">
                                    <?= _('Nouvelle Classe') ?>
                                </a>
                            <?php endif; ?>
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
                                        <th><?= _('Nom de la classe') ?></th>
                                        <th><?= _('Niveau') ?></th>
                                        <th><?= _('Série') ?></th>
                                        <th><?= _('Cycle') ?></th>
                                        <?php if (Auth::can('view_all_lycees', 'lycee')): ?>
                                            <th><?= _('Lycée') ?></th>
                                        <?php endif; ?>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($classes)): ?>
                                        <tr>
                                            <td colspan="<?= Auth::can('view_all_lycees', 'lycee') ? '6' : '5' ?>" class="text-center"><?= _('Aucune classe trouvée.') ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($classes as $classe): ?>
                                            <tr>
                                                <td><?= htmlspecialchars(Classe::getFormattedName($classe)) ?></td>
                                                <td><?= htmlspecialchars($classe['niveau']) ?></td>
                                                <td><?= htmlspecialchars($classe['serie'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($classe['nom_cycle']) ?></td>
                                                <?php if (Auth::can('view_all_lycees', 'lycee')): ?>
                                                    <td><?= htmlspecialchars($classe['nom_lycee']) ?></td>
                                                <?php endif; ?>
                                                <td class="text-end">
                                                    <?php if (Auth::can('view', 'class')): ?>
                                                        <a href="/classes/show?id=<?= $classe['id_classe'] ?>" class="btn btn-sm btn-info" title="<?= _('Détails et matières') ?>">
                                                            <?= _('Voir') ?>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (Auth::can('edit', 'class')): ?>
                                                        <a href="/classes/edit?id=<?= $classe['id_classe'] ?>" class="btn btn-sm btn-primary ms-2" title="<?= _('Modifier') ?>">
                                                            <?= _('Modifier') ?>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (Auth::can('delete', 'class')): ?>
                                                        <form action="/classes/destroy" method="POST" class="d-inline ms-2" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir supprimer cette classe ?') ?>');">
                                                            <input type="hidden" name="id" value="<?= $classe['id_classe'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" title="<?= _('Supprimer') ?>">
                                                                <?= _('Supprimer') ?>
                                                            </button>
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
