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
                            <h5 class="m-b-10"><?= _("Créer une Catégorie d'Achats") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="/achats/categories"><?= _("Catégories d'achats") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Nouvelle") ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- Alert messages -->
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
            <div class="col-sm-12 col-md-8 offset-md-2">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="ph-duotone ph-plus-circle me-2 text-primary"></i><?= _("Détails de la catégorie") ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="/achats/categories/create" method="POST">
                            <!-- Libellé -->
                            <div class="mb-3">
                                <label class="form-label font-weight-bold" for="libelle"><?= _("Nom / Libellé de la catégorie d'achats") ?> <span class="text-danger">*</span></label>
                                <input type="text" name="libelle" id="libelle" class="form-control" placeholder="Ex: Fournitures scolaires" required>
                            </div>

                            <!-- Compte de Charge -->
                            <div class="mb-3">
                                <label class="form-label font-weight-bold" for="compte_comptable_charge"><?= _("Compte comptable de charge OHADA") ?> <span class="text-danger">*</span></label>
                                <select name="compte_comptable_charge" id="compte_comptable_charge" class="form-select" required>
                                    <option value=""><?= _("-- Sélectionner un compte de charge --") ?></option>
                                    <?php if (!empty($comptesCharges)): ?>
                                        <?php foreach ($comptesCharges as $cc): ?>
                                            <option value="<?= htmlspecialchars($cc['numero']) ?>">
                                                <?= htmlspecialchars($cc['numero'] . ' — ' . $cc['libelle']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="text-muted"><?= _("Compte de la classe 6 ou de charge où seront rattachées les factures de cette catégorie.") ?></small>
                            </div>

                            <div class="mt-4 d-flex justify-content-end gap-2">
                                <a href="/achats/categories" class="btn btn-light-secondary"><?= _("Annuler") ?></a>
                                <button type="submit" class="btn btn-primary"><?= _("Créer la catégorie") ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
