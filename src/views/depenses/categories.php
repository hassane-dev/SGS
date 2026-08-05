<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Configuration des Catégories de Dépenses') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Ajouter une Catégorie') ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/depenses/categories/store">
                            <div class="mb-3">
                                <label class="form-label"><?= _('Nom de la catégorie') ?></label>
                                <input type="text" name="nom_categorie" class="form-control" required />
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?= _('Description') ?></label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?= _('Statut') ?></label>
                                <select name="statut" class="form-select">
                                    <option value="actif"><?= _('Actif') ?></option>
                                    <option value="inactif"><?= _('Inactif') ?></option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><?= _('Enregistrer') ?></button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Catégories de Dépenses') ?></h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th><?= _('ID') ?></th>
                                        <th><?= _('Nom') ?></th>
                                        <th><?= _('Description') ?></th>
                                        <th><?= _('Statut') ?></th>
                                        <th><?= _('Modifiable') ?></th>
                                        <th class="text-center"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categories)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4"><?= _('Aucune catégorie configurée.') ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($categories as $c): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($c['id']) ?></td>
                                                <td><strong><?= htmlspecialchars($c['nom_categorie']) ?></strong></td>
                                                <td><?= htmlspecialchars($c['description'] ?? 'N/A') ?></td>
                                                <td>
                                                    <span class="badge <?= $c['statut'] === 'actif' ? 'bg-light-success' : 'bg-light-danger' ?>">
                                                        <?= htmlspecialchars($c['statut']) ?>
                                                    </span>
                                                </td>
                                                <td><?= $c['modifiable'] ? _('Oui') : _('Immuable') ?></td>
                                                <td class="text-center">
                                                    <?php if ($c['modifiable']): ?>
                                                        <form method="POST" action="/depenses/categories/delete" class="d-inline">
                                                            <input type="hidden" name="id" value="<?= $c['id'] ?>" />
                                                            <button type="submit" class="btn btn-sm btn-light-danger" onclick="return confirm('<?= _('Voulez-vous vraiment désactiver ou supprimer cette catégorie ?') ?>')">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        -
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
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
