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
                            <h2 class="mb-0"><?= _('Gestion des Années Académiques') ?></h2>
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
                             <a href="/annees-academiques/create" class="btn btn-primary">
                                <i class="ti ti-plus"></i> <?= _('Ajouter une année') ?>
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
                                        <th><?= _('Libellé') ?></th>
                                        <th><?= _('Date de Début') ?></th>
                                        <th><?= _('Date de Fin') ?></th>
                                        <th><?= _('Statut') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($annees)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center"><?= _('Aucune année académique trouvée.') ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($annees as $annee): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($annee['libelle']) ?></td>
                                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($annee['date_debut']))) ?></td>
                                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($annee['date_fin']))) ?></td>
                                                <td>
                                                    <?php if ($annee['est_active']): ?>
                                                        <span class="badge bg-light-success"><?= _('Active') ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-secondary"><?= _('Inactive') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php if (!$annee['est_active']): ?>
                                                        <form action="/annees-academiques/activate" method="POST" class="d-inline">
                                                            <input type="hidden" name="id" value="<?= $annee['id'] ?>">
                                                            <button type-="submit" class="btn btn-sm btn-success" title="<?= _('Activer cette année') ?>">
                                                                <i class="ti ti-check"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <a href="/annees-academiques/edit?id=<?= $annee['id'] ?>" class="btn btn-sm btn-primary" title="<?= _('Modifier') ?>">
                                                        <i class="ti ti-pencil"></i>
                                                    </a>
                                                    <form action="/annees-academiques/destroy" method="POST" class="d-inline" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir supprimer cette année ?') ?>');">
                                                        <input type="hidden" name="id" value="<?= $annee['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" title="<?= _('Supprimer') ?>">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
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