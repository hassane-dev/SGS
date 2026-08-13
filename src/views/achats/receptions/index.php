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
                            <h5 class="m-b-10"><?= _("Bons de Réception") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="#!"><?= _("Achats & Fournisseurs") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Bons de réception") ?></li>
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
                        <h5 class="mb-0"><i class="ph-duotone ph-download-simple me-2 text-primary"></i><?= _("Suivi des Réceptions Physiques (BR)") ?></h5>
                    </div>
                    <div class="card-body">
                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _("N° Réception") ?></th>
                                        <th><?= _("N° Commande (BC)") ?></th>
                                        <th><?= _("Fournisseur") ?></th>
                                        <th><?= _("Date réception") ?></th>
                                        <th><?= _("Réceptionnaire") ?></th>
                                        <th><?= _("Statut") ?></th>
                                        <th class="text-center d-print-none"><?= _("Actions") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($receptions)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <i class="ph-duotone ph-folder-open fs-1 d-block mb-2"></i>
                                                <?= _("Aucun bon de réception enregistré.") ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($receptions as $rec): ?>
                                            <tr>
                                                <td><span class="badge bg-light-primary text-primary"><?= htmlspecialchars($rec['numero_reception']) ?></span></td>
                                                <td><code class="text-dark font-weight-bold"><?= htmlspecialchars($rec['numero_commande']) ?></code></td>
                                                <td><span class="font-weight-bold text-dark"><?= htmlspecialchars($rec['fournisseur_nom']) ?></span></td>
                                                <td><?= date('d/m/Y', strtotime($rec['date_reception'])) ?></td>
                                                <td><?= htmlspecialchars($rec['receptionne_par_prenom'] . ' ' . $rec['receptionne_par_nom']) ?></td>
                                                <td>
                                                    <?php if ($rec['statut'] === 'valide'): ?>
                                                        <span class="badge bg-light-success text-success"><i class="ph-duotone ph-check-circle me-1"></i><?= _("Validé") ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-secondary text-secondary"><?= htmlspecialchars($rec['statut']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center d-print-none">
                                                    <div class="dropdown d-inline-block">
                                                        <button class="btn btn-sm btn-link-secondary dropdown-toggle no-caret" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ph-duotone ph-dots-three-vertical fs-5"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item" href="/achats/receptions/show?id=<?= $rec['id'] ?>">
                                                                    <i class="ph-duotone ph-eye me-2 text-primary"></i><?= _("Consulter les lignes") ?>
                                                                </a>
                                                            </li>
                                                            <?php if (Auth::can('create', 'achat_facture')): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="/achats/factures/rapprochement?reception_id=<?= $rec['id'] ?>">
                                                                        <i class="ph-duotone ph-receipt me-2 text-success"></i><?= _("Facturer (3-Way)") ?>
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
