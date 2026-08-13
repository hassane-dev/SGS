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
                            <h5 class="m-b-10"><?= _("Bons de Commande") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="#!"><?= _("Achats & Fournisseurs") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Bons de commande") ?></li>
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
                    <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                        <h5 class="mb-0"><i class="ph-duotone ph-shopping-cart me-2 text-primary"></i><?= _("Bons de Commande Émis (BC)") ?></h5>
                        <?php if (Auth::can('create', 'achat_commande')): ?>
                            <a href="/achats/commandes/create" class="btn btn-primary d-inline-flex align-items-center">
                                <i class="ph-duotone ph-plus-circle me-2"></i><?= _("Émettre un BC") ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _("N° Commande") ?></th>
                                        <th><?= _("Fournisseur") ?></th>
                                        <th><?= _("Date d'émission") ?></th>
                                        <th><?= _("Créé par") ?></th>
                                        <th><?= _("Statut") ?></th>
                                        <th class="text-center d-print-none"><?= _("Actions") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($commandes)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                <i class="ph-duotone ph-folder-open fs-1 d-block mb-2"></i>
                                                <?= _("Aucun bon de commande émis.") ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($commandes as $cmd): ?>
                                            <tr>
                                                <td><span class="badge bg-light-primary text-primary"><?= htmlspecialchars($cmd['numero_commande']) ?></span></td>
                                                <td>
                                                    <span class="d-block font-weight-bold text-dark"><?= htmlspecialchars($cmd['fournisseur_nom']) ?></span>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($cmd['date_commande'])) ?></td>
                                                <td>
                                                    <span class="small"><?= htmlspecialchars($cmd['cree_par_prenom'] . ' ' . $cmd['cree_par_nom']) ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($cmd['statut'] === 'emis'): ?>
                                                        <span class="badge bg-light-info text-info"><i class="ph-duotone ph-paper-plane-tilt me-1"></i><?= _("Émis") ?></span>
                                                    <?php elseif ($cmd['statut'] === 'reception_partielle'): ?>
                                                        <span class="badge bg-light-warning text-warning"><i class="ph-duotone ph-hourglass me-1"></i><?= _("Réception partielle") ?></span>
                                                    <?php elseif ($cmd['statut'] === 'executee'): ?>
                                                        <span class="badge bg-light-success text-success"><i class="ph-duotone ph-check-circle me-1"></i><?= _("Exécuté") ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-secondary text-secondary"><?= htmlspecialchars($cmd['statut']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center d-print-none">
                                                    <div class="dropdown d-inline-block">
                                                        <button class="btn btn-sm btn-link-secondary dropdown-toggle no-caret" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ph-duotone ph-dots-three-vertical fs-5"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item" href="/achats/commandes/show?id=<?= $cmd['id'] ?>">
                                                                    <i class="ph-duotone ph-eye me-2 text-primary"></i><?= _("Consulter / Imprimer") ?>
                                                                </a>
                                                            </li>
                                                            <?php if (in_array($cmd['statut'], ['emis', 'reception_partielle']) && Auth::can('create', 'achat_reception')): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="/achats/receptions/create?commande_id=<?= $cmd['id'] ?>">
                                                                        <i class="ph-duotone ph-download-simple me-2 text-success"></i><?= _("Réceptionner") ?>
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
