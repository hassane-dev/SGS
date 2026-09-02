<?php include __DIR__ . '/../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Types d'Évaluation Configuration") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/dashboard"><?= _("Accueil") ?></a></li>
                            <li class="breadcrumb-item"><?= _("Évaluations") ?></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Types d'Évaluation") ?></li>
                        </ul>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="/evaluations/types/create" class="btn btn-primary d-inline-flex align-items-center">
                            <i class="ph-duotone ph-plus me-2"></i><?= _("Nouveau Type") ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= _("Opération effectuée avec succès.") ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _("Liste des Types d'Évaluation de l'Établissement") ?></h5>
                    </div>
                    <div class="card-body text-end p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 text-start">
                                <thead>
                                    <tr>
                                        <th><?= _("Ordre") ?></th>
                                        <th><?= _("Code") ?></th>
                                        <th><?= _("Libellé") ?></th>
                                        <th><?= _("Barème par Défaut") ?></th>
                                        <th><?= _("Statut") ?></th>
                                        <th class="text-end"><?= _("Actions") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($types)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4"><?= _("Aucun type d'évaluation configuré.") ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($types as $t): ?>
                                            <tr>
                                                <td><?= (int)$t['ordre_affichage'] ?></td>
                                                <td><code><?= htmlspecialchars($t['code']) ?></code></td>
                                                <td><strong><?= htmlspecialchars($t['libelle']) ?></strong></td>
                                                <td>/<?= number_format((float)$t['bareme_defaut'], 0) ?></td>
                                                <td>
                                                    <?php if ($t['actif']): ?>
                                                        <span class="badge bg-light-success text-success"><?= _("Actif") ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-danger text-danger"><?= _("Inactif") ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <a href="/evaluations/types/edit?id=<?= $t['id'] ?>" class="btn btn-sm btn-icon btn-light-secondary" title="<?= _("Modifier") ?>">
                                                        <i class="ph-duotone ph-pencil"></i>
                                                    </a>
                                                    <a href="/evaluations/types/toggle?id=<?= $t['id'] ?>" class="btn btn-sm btn-icon <?= $t['actif'] ? 'btn-light-danger' : 'btn-light-success' ?>" title="<?= $t['actif'] ? _("Désactiver") : _("Activer") ?>">
                                                        <i class="ph-duotone <?= $t['actif'] ? 'ph-prohibit' : 'ph-check-circle' ?>"></i>
                                                    </a>
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

<?php include __DIR__ . '/../layouts/footer_able.php'; ?>
