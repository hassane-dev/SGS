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
                            <h5 class="m-b-10"><?= _("Catégories d'Achats") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="#!"><?= _("Achats & Fournisseurs") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Catégories d'achats") ?></li>
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
                        <h5 class="mb-0"><i class="ph-duotone ph-folders me-2 text-primary"></i><?= _("Catégories d'achats et imputation") ?></h5>
                        <?php if (Auth::can('manage', 'achat_categorie')): ?>
                            <a href="/achats/categories/create" class="btn btn-primary d-inline-flex align-items-center">
                                <i class="ph-duotone ph-plus-circle me-2"></i><?= _("Nouvelle catégorie") ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _("Désignation / Libellé") ?></th>
                                        <th><?= _("Compte de Charge OHADA") ?></th>
                                        <th class="text-center"><?= _("Statut") ?></th>
                                        <th class="text-center d-print-none"><?= _("Actions") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categories)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                <i class="ph-duotone ph-folder-open fs-1 d-block mb-2"></i>
                                                <?= _("Aucune catégorie configurée.") ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($categories as $cat): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avtar avtar-s bg-light-primary text-primary me-2">
                                                            <i class="ph-duotone ph-folder fs-5"></i>
                                                        </div>
                                                        <span class="h6 mb-0"><?= htmlspecialchars($cat['libelle']) ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <code class="text-dark font-weight-bold"><?= htmlspecialchars($cat['compte_comptable_charge']) ?></code>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($cat['actif']): ?>
                                                        <span class="badge bg-light-success text-success"><i class="ph-duotone ph-check-circle me-1"></i><?= _("Actif") ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-danger text-danger"><i class="ph-duotone ph-x-circle me-1"></i><?= _("Inactif") ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center d-print-none">
                                                    <?php if (Auth::can('manage', 'achat_categorie')): ?>
                                                        <a href="/achats/categories/edit?id=<?= $cat['id'] ?>" class="btn btn-sm btn-light-warning me-1">
                                                            <i class="ph-duotone ph-pencil me-1"></i><?= _("Modifier") ?>
                                                        </a>
                                                        <a href="/achats/categories/delete?id=<?= $cat['id'] ?>" class="btn btn-sm btn-light-danger" onclick="return confirm('<?= _("Voulez-vous vraiment supprimer ou désactiver cette catégorie ?") ?>');">
                                                            <i class="ph-duotone ph-trash me-1"></i><?= _("Supprimer") ?>
                                                        </a>
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
        <!-- [ Main Content ] end -->
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
