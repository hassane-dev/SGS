<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= _('Gestion des Années Académiques') ?></h1>
        <a href="/annees-academiques/create" class="btn btn-primary">
            <i class="fas fa-plus"></i> <?= _('Ajouter une année') ?>
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary"><?= _('Liste des Années Académiques') ?></h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
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
                        <?php foreach ($annees as $annee): ?>
                            <tr>
                                <td><?= htmlspecialchars($annee['libelle']) ?></td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($annee['date_debut']))) ?></td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($annee['date_fin']))) ?></td>
                                <td>
                                    <?php if ($annee['est_active']): ?>
                                        <span class="badge bg-success"><?= _('Active') ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= _('Inactive') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if (!$annee['est_active']): ?>
                                        <form action="/annees-academiques/activate" method="POST" class="d-inline">
                                            <input type="hidden" name="id" value="<?= $annee['id'] ?>">
                                            <button type="submit" class="btn btn-success btn-sm" title="<?= _('Activer cette année') ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="/annees-academiques/edit?id=<?= $annee['id'] ?>" class="btn btn-secondary btn-sm" title="<?= _('Modifier') ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="/annees-academiques/destroy" method="POST" class="d-inline" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir supprimer cette année ?') ?>');">
                                        <input type="hidden" name="id" value="<?= $annee['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="<?= _('Supprimer') ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
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