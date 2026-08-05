<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Configuration des Bénéficiaires') ?></h2>
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
                        <h5><?= _('Ajouter un Bénéficiaire') ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/depenses/beneficiaires/store">
                            <div class="mb-3">
                                <label class="form-label"><?= _('Nom ou Raison Sociale') ?></label>
                                <input type="text" name="nom_beneficiaire" class="form-control" required />
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?= _('Type') ?></label>
                                <select name="type" class="form-select">
                                    <option value="externe"><?= _('Externe / Fournisseur') ?></option>
                                    <option value="interne"><?= _('Interne / Personnel') ?></option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?= _('Téléphone') ?></label>
                                <input type="text" name="telephone" class="form-control" />
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?= _('Email') ?></label>
                                <input type="email" name="email" class="form-control" />
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
                        <h5><?= _('Bénéficiaires de Dépenses') ?></h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th><?= _('ID') ?></th>
                                        <th><?= _('Nom / Raison Sociale') ?></th>
                                        <th><?= _('Type') ?></th>
                                        <th><?= _('Téléphone') ?></th>
                                        <th><?= _('Email') ?></th>
                                        <th><?= _('Statut') ?></th>
                                        <th class="text-center"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($beneficiaires)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4"><?= _('Aucun bénéficiaire configuré.') ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($beneficiaires as $b): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($b['id']) ?></td>
                                                <td><strong><?= htmlspecialchars($b['nom_beneficiaire']) ?></strong></td>
                                                <td><span class="badge bg-light-secondary"><?= htmlspecialchars($b['type']) ?></span></td>
                                                <td><?= htmlspecialchars($b['telephone'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($b['email'] ?? '-') ?></td>
                                                <td>
                                                    <span class="badge <?= $b['statut'] === 'actif' ? 'bg-light-success' : 'bg-light-danger' ?>">
                                                        <?= htmlspecialchars($b['statut']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <form method="POST" action="/depenses/beneficiaires/delete" class="d-inline">
                                                        <input type="hidden" name="id" value="<?= $b['id'] ?>" />
                                                        <button type="submit" class="btn btn-sm btn-light-danger" onclick="return confirm('<?= _('Voulez-vous vraiment désactiver ou supprimer ce bénéficiaire ?') ?>')">
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
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
