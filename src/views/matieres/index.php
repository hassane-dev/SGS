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
                            <h2 class="mb-0"><?= _('Gestion des Matières') ?></h2>
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
                            <?php if (Auth::can('create', 'matiere')): ?>
                                <a href="/matieres/create" class="btn btn-primary">
                                    <?= _('Nouvelle Matière') ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php endif; ?>
                        <?php if (isset($_GET['error']) && $_GET['error'] === 'delete_failed'): ?>
                            <div class="alert alert-danger">
                                <?= _('La suppression a échoué. La matière est probablement utilisée dans une ou plusieurs classes ou par un enseignant.') ?>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?= _('Nom de la matière') ?></th>
                                        <th><?= _('Type') ?></th>
                                        <th><?= _('Cycle Concerné') ?></th>
                                        <th><?= _('Statut') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($matieres)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center"><?= _('Aucune matière trouvée.') ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($matieres as $matiere): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($matiere['nom_matiere']) ?></td>
                                                <td><?= htmlspecialchars($matiere['type'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($matiere['cycle_concerne'] ?? 'N/A') ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $matiere['statut'] === 'principale' ? 'success' : 'warning' ?>">
                                                        <?= ucfirst(htmlspecialchars($matiere['statut'])) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <?php if (Auth::can('edit', 'matiere')): ?>
                                                        <a href="/matieres/edit?id=<?= $matiere['id_matiere'] ?>" class="btn btn-sm btn-primary"><?= _('Modifier') ?></a>
                                                    <?php endif; ?>
                                                    <?php if (Auth::can('delete', 'matiere')): ?>
                                                        <form action="/matieres/delete" method="POST" class="d-inline ms-2" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir supprimer cette matière ?') ?>');">
                                                            <input type="hidden" name="id" value="<?= $matiere['id_matiere'] ?>">
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
