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
                            <h5 class="m-b-10"><?= _("Modifier l'Article / Service") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="/achats/articles"><?= _("Catalogue") ?></a></li>
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
                        <h5 class="mb-0"><i class="ph-duotone ph-pencil me-2 text-warning"></i><?= _("Modifier l'article") ?> : <?= htmlspecialchars($article['libelle']) ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="/achats/articles/edit?id=<?= $article['id'] ?>" method="POST">
                            <div class="row g-3">
                                <!-- Catégorie -->
                                <div class="col-md-12">
                                    <label class="form-label font-weight-bold" for="categorie_id"><?= _("Catégorie d'achat") ?> <span class="text-danger">*</span></label>
                                    <select name="categorie_id" id="categorie_id" class="form-select" required>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $article['categorie_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['libelle']) ?> (<?= htmlspecialchars($cat['compte_comptable_charge']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Désignation / Libellé -->
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold" for="libelle"><?= _("Désignation / Libellé") ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="libelle" id="libelle" class="form-control" value="<?= htmlspecialchars($article['libelle']) ?>" required>
                                </div>

                                <!-- Référence unique (immutable) -->
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold" for="reference"><?= _("Référence unique (Non modifiable)") ?></label>
                                    <input type="text" id="reference" class="form-control" value="<?= htmlspecialchars($article['reference']) ?>" disabled>
                                </div>

                                <!-- Unité de mesure -->
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold" for="unite_mesure"><?= _("Unité de mesure") ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="unite_mesure" id="unite_mesure" class="form-control" value="<?= htmlspecialchars($article['unite_mesure']) ?>" required>
                                </div>

                                <!-- Prix unitaire estimé -->
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold" for="prix_unitaire_estime"><?= _("Prix unitaire estimé (HT)") ?> <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.0001" name="prix_unitaire_estime" id="prix_unitaire_estime" class="form-control" value="<?= htmlspecialchars($article['prix_unitaire_estime']) ?>" required>
                                        <span class="input-group-text">FCFA</span>
                                    </div>
                                </div>

                                <!-- Is Service Prestation -->
                                <div class="col-md-6">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" name="is_service" id="is_service" value="1" <?= $article['is_service'] ? 'checked' : '' ?>>
                                        <label class="form-check-label font-weight-bold" for="is_service"><?= _("Il s'agit d'un service ou prestation") ?></label>
                                    </div>
                                </div>

                                <!-- Actif -->
                                <div class="col-md-6">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" name="actif" id="actif" value="1" <?= $article['actif'] ? 'checked' : '' ?>>
                                        <label class="form-check-label font-weight-bold" for="actif"><?= _("Article Actif") ?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-end gap-2">
                                <a href="/achats/articles" class="btn btn-light-secondary"><?= _("Annuler") ?></a>
                                <button type="submit" class="btn btn-primary"><?= _("Enregistrer les modifications") ?></button>
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
