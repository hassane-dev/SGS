<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Dépenses Approuvées en attente de Règlement') ?></h2>
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

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th><?= _('Réf/Pièce') ?></th>
                                <th><?= _('Objet') ?></th>
                                <th><?= _('Catégorie') ?></th>
                                <th><?= _('Bénéficiaire') ?></th>
                                <th class="text-end"><?= _('Montant') ?></th>
                                <th class="text-center"><?= _('Actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($depenses)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4"><?= _('Aucune dépense approuvée en attente de règlement.') ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($depenses as $d): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($d['numero_piece']) ?></strong></td>
                                        <td><?= htmlspecialchars($d['objet']) ?></td>
                                        <td><?= htmlspecialchars($d['nom_categorie']) ?></td>
                                        <td><?= htmlspecialchars($d['nom_beneficiaire']) ?></td>
                                        <td class="text-end fw-bold"><?= number_format($d['montant'], 2, ',', ' ') ?> FCFA</td>
                                        <td class="text-center">
                                            <a href="/depenses/pay/<?= $d['id'] ?>" class="btn btn-sm btn-success">
                                                <i class="ti ti-cash"></i> <?= _('Régler / Payer') ?>
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

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
