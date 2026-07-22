<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _($title) ?></h2>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/paiements"><?= _('Finances') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _($title) ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts Display -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <div class="d-flex align-items-center">
                    <i class="ph-duotone ph-check-circle me-2 fs-4"></i>
                    <div><?= htmlspecialchars($_SESSION['success_message'] ?? '') ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <div class="d-flex align-items-center">
                    <i class="ph-duotone ph-warning-circle me-2 fs-4"></i>
                    <div><?= htmlspecialchars($_SESSION['error_message'] ?? '') ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _("File d'attente des validations financières") ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?= _('Élève') ?></th>
                                        <th><?= _('Classe') ?></th>
                                        <th><?= _('Statut') ?></th>
                                        <th class="text-end"><?= _('Déjà Versé') ?></th>
                                        <th class="text-end text-danger"><?= _('Reste à Payer') ?></th>
                                        <th class="text-center"><?= _('Action') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($eleves as $e): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0">
                                                        <img src="<?= $e['photo'] ?: '/assets/img/default-avatar.png' ?>" alt="user image" class="img-radius wid-40">
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <h6 class="mb-0"><?= htmlspecialchars($e['prenom'] . ' ' . $e['nom']) ?></h6>
                                                        <p class="mb-0 text-muted small"><?= _('Né(e) le ') ?><?= date('d/m/Y', strtotime($e['date_naissance'])) ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light-primary text-primary">
                                                    <?= htmlspecialchars($e['nom_classe']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($e['etat_finance'] === 'Partiel'): ?>
                                                    <span class="badge bg-light-warning text-warning"><?= _('Paiement Partiel') ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-light-danger text-danger"><?= _('En attente') ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end"><?= number_format($e['verse'], 0, ',', ' ') ?> FCFA</td>
                                            <td class="text-end fw-bold text-danger"><?= number_format($e['reste'], 0, ',', ' ') ?> FCFA</td>
                                            <td class="text-center">
                                                <a href="/paiements/show/<?= $e['id_eleve'] ?>" class="btn btn-icon btn-light-success" title="<?= _('Procéder au paiement') ?>">
                                                    <i class="ph-duotone ph-currency-circle-dollar"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($eleves)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4"><?= _('Aucun élève en attente de validation financière.') ?></td>
                                        </tr>
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
