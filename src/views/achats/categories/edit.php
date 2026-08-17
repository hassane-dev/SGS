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
                            <h5 class="m-b-10"><?= _("Modifier la Catégorie d'Achats") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="/achats/categories"><?= _("Catégories d'achats") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Modifier") ?></li>
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
                        <h5 class="mb-0"><i class="ph-duotone ph-pencil me-2 text-warning"></i><?= _("Modifier la catégorie") ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="/achats/categories/edit?id=<?= $category['id'] ?>" method="POST">
                            <!-- Libellé -->
                            <div class="mb-3">
                                <label class="form-label font-weight-bold" for="libelle"><?= _("Nom / Libellé de la catégorie d'achats") ?> <span class="text-danger">*</span></label>
                                <input type="text" name="libelle" id="libelle" class="form-control" value="<?= htmlspecialchars($category['libelle']) ?>" required>
                            </div>

                            <!-- Compte de Charge -->
                            <div class="mb-3">
                                <label class="form-label font-weight-bold" for="compte_comptable_charge"><?= _("Compte comptable de charge OHADA") ?> <span class="text-danger">*</span></label>
                                <select name="compte_comptable_charge" id="compte_comptable_charge" class="form-select" required>
                                    <option value=""><?= _("-- Sélectionner un compte de charge --") ?></option>
                                    <?php if (!empty($comptesCharges)): ?>
                                        <?php foreach ($comptesCharges as $cc): ?>
                                            <option value="<?= htmlspecialchars($cc['numero']) ?>" <?= $category['compte_comptable_charge'] === $cc['numero'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cc['numero'] . ' — ' . $cc['libelle']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Statut Actif -->
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="actif" id="actifSwitch" value="1" <?= $category['actif'] ? 'checked' : '' ?>>
                                    <label class="form-check-label font-weight-bold" for="actifSwitch"><?= _("Catégorie Active") ?></label>
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-end gap-2">
                                <a href="/achats/categories" class="btn btn-light-secondary"><?= _("Annuler") ?></a>
                                <button type="submit" class="btn btn-primary"><?= _("Enregistrer") ?></button>
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
