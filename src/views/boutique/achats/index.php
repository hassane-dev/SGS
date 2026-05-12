<?php require_once __DIR__ . '/../../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/eleves"><?= _('Élèves') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Historique des Achats') ?></li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Historique des Achats') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><?= _('Achats de') ?> <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h5>
                        <a href="/boutique/achats/create?eleve_id=<?= $eleve['id_eleve'] ?>" class="btn btn-primary d-inline-flex align-items-center">
                            <i class="ph-duotone ph-plus-circle me-2"></i>
                            <?= _('Nouvel Achat') ?>
                        </a>
                    </div>
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?= _('Date') ?></th>
                                        <th><?= _('Article') ?></th>
                                        <th><?= _('Quantité') ?></th>
                                        <th><?= _('Prix Unitaire') ?></th>
                                        <th><?= _('Total') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($achats as $achat): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($achat['date_achat']))) ?></td>
                                            <td><?= htmlspecialchars($achat['nom_article']) ?></td>
                                            <td><?= htmlspecialchars($achat['quantite']) ?></td>
                                            <td><?= htmlspecialchars($achat['prix']) ?></td>
                                            <td><?= htmlspecialchars($achat['prix'] * $achat['quantite']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($achats)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center"><?= _('Aucun achat trouvé') ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="/eleves/details?id=<?= $eleve['id_eleve'] ?>" class="btn btn-link-secondary">
                            <i class="ph-duotone ph-arrow-left me-2"></i>
                            <?= _('Retour au dossier élève') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
