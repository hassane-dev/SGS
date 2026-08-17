<?php
$title = _("Créer un Compte Comptable");
ob_start();

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
                            <h5 class="m-b-10"><?= htmlspecialchars($title) ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/comptes-comptables"><?= _('Plan de Comptes') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Créer") ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- Alerts -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-warning-circle me-1"></i>
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="row">
            <div class="col-sm-12 col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _("Informations du Compte Comptable OHADA") ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="/comptes-comptables/store" method="POST">
                            <div class="row">
                                <!-- Numéro de compte -->
                                <div class="col-md-6 mb-3">
                                    <label for="numero" class="form-label font-weight-bold"><?= _("Numéro de Compte *") ?></label>
                                    <input type="text" class="form-control" id="numero" name="numero" placeholder="Ex: 571300, 601300" required>
                                    <div class="form-text text-muted"><?= _("Numéro unique OHADA (ex: 571100, 601100, 401100).") ?></div>
                                </div>

                                <!-- Classe comptable -->
                                <div class="col-md-6 mb-3">
                                    <label for="classe" class="form-label font-weight-bold"><?= _("Classe Comptable *") ?></label>
                                    <select class="form-select" id="classe" name="classe" required>
                                        <option value=""><?= _("-- Sélectionner la classe --") ?></option>
                                        <?php foreach ($classes as $cNum => $cName): ?>
                                            <option value="<?= $cNum ?>"><?= htmlspecialchars($cName) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Libellé -->
                            <div class="mb-3">
                                <label for="libelle" class="form-label font-weight-bold"><?= _("Libellé du Compte *") ?></label>
                                <input type="text" class="form-control" id="libelle" name="libelle" placeholder="Ex: Caisse Annexe Scolarité, Fournitures pédagogiques" required>
                            </div>

                            <div class="row">
                                <!-- Nature -->
                                <div class="col-md-6 mb-3">
                                    <label for="nature" class="form-label font-weight-bold"><?= _("Nature du Compte *") ?></label>
                                    <select class="form-select" id="nature" name="nature" required>
                                        <option value="actif"><?= _("Actif (Débit habituel)") ?></option>
                                        <option value="passif"><?= _("Passif (Crédit habituel)") ?></option>
                                        <option value="charge"><?= _("Charge (Classe 6)") ?></option>
                                        <option value="produit"><?= _("Produit (Classe 7)") ?></option>
                                    </select>
                                </div>

                                <!-- Compte parent -->
                                <div class="col-md-6 mb-3">
                                    <label for="compte_parent_id" class="form-label font-weight-bold"><?= _("Compte Parent (Arborescence)") ?></label>
                                    <select class="form-select" id="compte_parent_id" name="compte_parent_id">
                                        <option value=""><?= _("-- Aucun (Compte Racine) --") ?></option>
                                        <?php foreach ($allComptes as $p): ?>
                                            <option value="<?= $p['id'] ?>">
                                                <?= htmlspecialchars($p['numero'] . ' — ' . $p['libelle']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Autoriser écriture -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="autoriser_ecriture" name="autoriser_ecriture" value="1" checked>
                                        <label class="form-check-label fw-bold" for="autoriser_ecriture"><?= _("Autoriser la saisie d'écritures directes") ?></label>
                                        <div class="form-text text-muted"><?= _("Décocher pour un compte de regroupement/totaux.") ?></div>
                                    </div>
                                </div>

                                <!-- Statut Actif -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="actif" name="actif" value="1" checked>
                                        <label class="form-check-label fw-bold" for="actif"><?= _("Actif dans le référentiel") ?></label>
                                        <div class="form-text text-muted"><?= _("Un compte actif est disponible dans les sélecteurs des formulaires.") ?></div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-end">
                                <a href="/comptes-comptables" class="btn btn-outline-secondary me-2"><?= _("Annuler") ?></a>
                                <button type="submit" class="btn btn-primary d-inline-flex align-items-center">
                                    <i class="ph-duotone ph-check-square-offset me-1"></i> <?= _("Enregistrer le Compte") ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<?php
require_once __DIR__ . '/../../layouts/footer_able.php';
$content = ob_get_clean();
echo $content;
?>
