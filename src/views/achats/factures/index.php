<?php
require_once __DIR__ . '/../../layouts/header_able.php';
require_once __DIR__ . '/../../layouts/sidebar_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Factures Fournisseurs") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="#!"><?= _("Achats & Fournisseurs") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Factures") ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- Alert messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-check-circle me-1"></i>
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-warning-circle me-1"></i>
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-sm-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="ph-duotone ph-receipt me-2 text-primary"></i><?= _("Factures d'Achats Enregistrées") ?></h5>
                    </div>
                    <div class="card-body">
                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _("Référence") ?></th>
                                        <th><?= _("Fournisseur") ?></th>
                                        <th><?= _("Dates") ?></th>
                                        <th><?= _("Documents Liés") ?></th>
                                        <th class="text-end"><?= _("Montant HT") ?></th>
                                        <th class="text-end"><?= _("Montant TTC") ?></th>
                                        <th class="text-end"><?= _("Reste à Payer") ?></th>
                                        <th class="text-center"><?= _("Statut") ?></th>
                                        <th class="text-center d-print-none"><?= _("Actions") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($factures)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4 text-muted">
                                                <i class="ph-duotone ph-folder-open fs-1 d-block mb-2"></i>
                                                <?= _("Aucune facture fournisseur enregistrée.") ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($factures as $fact):
                                            $reste = AchatFacture::getResteAPayer($fact['id']);
                                        ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($fact['reference_facture']) ?></strong></td>
                                                <td><span class="font-weight-bold text-dark"><?= htmlspecialchars($fact['fournisseur_nom']) ?></span></td>
                                                <td>
                                                    <span class="d-block small"><strong><?= _("Facture :") ?></strong> <?= date('d/m/Y', strtotime($fact['date_facture'])) ?></span>
                                                    <span class="d-block small text-muted"><strong><?= _("Échéance :") ?></strong> <?= date('d/m/Y', strtotime($fact['date_echeance'])) ?></span>
                                                </td>
                                                <td>
                                                    <span class="d-block small"><strong>BC:</strong> <?= htmlspecialchars($fact['numero_commande'] ?: '-') ?></span>
                                                    <span class="d-block small text-muted"><strong>BR:</strong> <?= htmlspecialchars($fact['numero_reception'] ?: '-') ?></span>
                                                </td>
                                                <td class="text-end"><?= number_format($fact['montant_ht'], 2, ',', ' ') ?> FCFA</td>
                                                <td class="text-end font-weight-bold text-dark"><?= number_format($fact['montant_ttc'], 2, ',', ' ') ?> FCFA</td>
                                                <td class="text-end font-weight-bold <?= $reste > 0 ? 'text-danger' : 'text-success' ?>">
                                                    <?= number_format($reste, 2, ',', ' ') ?> FCFA
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($fact['statut'] === 'payee'): ?>
                                                        <span class="badge bg-light-success text-success"><i class="ph-duotone ph-check-circle me-1"></i><?= _("Payée") ?></span>
                                                    <?php elseif ($fact['statut'] === 'payee_partiellement'): ?>
                                                        <span class="badge bg-light-warning text-warning"><i class="ph-duotone ph-hourglass-low me-1"></i><?= _("Partiellement payée") ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-danger text-danger"><i class="ph-duotone ph-warning me-1"></i><?= _("Enregistrée") ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center d-print-none">
                                                    <div class="dropdown d-inline-block">
                                                        <button class="btn btn-sm btn-link-secondary dropdown-toggle no-caret" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ph-duotone ph-dots-three-vertical fs-5"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <?php if ($reste > 0 && Auth::can('pay', 'achat_facture')): ?>
                                                                <li>
                                                                    <a class="dropdown-item text-success" href="/achats/factures/pay?id=<?= $fact['id'] ?>">
                                                                        <i class="ph-duotone ph-wallet me-2"></i><?= _("Régler la facture") ?>
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            <?php if (Auth::can('create', 'achat_avoir')): ?>
                                                                <li>
                                                                    <a class="dropdown-item text-danger" href="/achats/avoirs/create?facture_id=<?= $fact['id'] ?>">
                                                                        <i class="ph-duotone ph-arrow-counter-clockwise me-2"></i><?= _("Émettre un Avoir") ?>
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
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

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
