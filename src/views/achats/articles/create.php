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
                            <h5 class="m-b-10"><?= _("Ajouter un Article ou Service") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="/achats/articles"><?= _("Catalogue") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Ajouter") ?></li>
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
                        <h5 class="mb-0"><i class="ph-duotone ph-plus-circle me-2 text-primary"></i><?= _("Fiche Article / Prestation") ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="/achats/articles/create" method="POST">
    <?= csrf_field() ?>
                            <div class="row g-3">
                                <!-- Catégorie -->
                                <div class="col-md-12">
                                    <label class="form-label font-weight-bold" for="categorie_id"><?= _("Catégorie d'achat") ?> <span class="text-danger">*</span></label>
                                    <select name="categorie_id" id="categorie_id" class="form-select" required>
                                        <option value=""><?= _("-- Choisir une catégorie --") ?></option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['libelle']) ?> (<?= htmlspecialchars($cat['compte_comptable_charge']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Désignation / Libellé -->
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold" for="libelle"><?= _("Désignation / Libellé") ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="libelle" id="libelle" class="form-control" placeholder="Ex: Ramette de papier A4 Double A" required>
                                </div>

                                <!-- Référence unique -->
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold" for="reference"><?= _("Référence unique / Code") ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="reference" id="reference" class="form-control" placeholder="Ex: REF-PAP-A4" required>
                                </div>

                                <!-- Unité de mesure -->
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold" for="unite_mesure"><?= _("Unité de mesure") ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="unite_mesure" id="unite_mesure" class="form-control" placeholder="Ex: Pièce, Carton, Heure, Litre" required>
                                </div>

                                <!-- Prix unitaire estimé -->
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold" for="prix_unitaire_estime"><?= _("Prix unitaire estimé (HT)") ?> <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.0001" name="prix_unitaire_estime" id="prix_unitaire_estime" class="form-control" placeholder="Ex: 3500" required>
                                        <span class="input-group-text">FCFA</span>
                                    </div>
                                </div>

                                <!-- Is Service Prestation -->
                                <div class="col-md-12">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="is_service" id="is_service" value="1">
                                        <label class="form-check-label font-weight-bold" for="is_service"><?= _("Il s'agit d'un service ou d'une prestation de service (pas de stock physique)") ?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-end gap-2">
                                <a href="/achats/articles" class="btn btn-light-secondary"><?= _("Annuler") ?></a>
                                <button type="submit" class="btn btn-primary"><?= _("Ajouter au catalogue") ?></button>
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
