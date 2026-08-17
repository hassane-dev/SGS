<?php
$title = _("Modifier le Compte Comptable");
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
                            <li class="breadcrumb-item" aria-current="page"><?= _("Modifier") ?></li>
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
                        <h5><?= _("Modifier les Paramètres du Compte #") ?><?= htmlspecialchars($compte['numero']) ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="/comptes-comptables/update" method="POST">
                            <input type="hidden" name="id" value="<?= $compte['id'] ?>">

                            <div class="row">
                                <!-- Numéro de compte -->
                                <div class="col-md-6 mb-3">
                                    <label for="numero" class="form-label font-weight-bold"><?= _("Numéro de Compte *") ?></label>
                                    <input type="text" class="form-control" id="numero" name="numero" value="<?= htmlspecialchars($compte['numero']) ?>" <?= !empty($compte['est_systeme']) ? 'readonly' : '' ?> required>
                                    <?php if (!empty($compte['est_systeme'])): ?>
                                        <div class="form-text text-info"><i class="ph-duotone ph-info me-1"></i><?= _("Compte système : le numéro est fixe.") ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Classe comptable -->
                                <div class="col-md-6 mb-3">
                                    <label for="classe" class="form-label font-weight-bold"><?= _("Classe Comptable *") ?></label>
                                    <select class="form-select" id="classe" name="classe" required>
                                        <?php foreach ($classes as $cNum => $cName): ?>
                                            <option value="<?= $cNum ?>" <?= $compte['classe'] == $cNum ? 'selected' : '' ?>><?= htmlspecialchars($cName) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Libellé -->
                            <div class="mb-3">
                                <label for="libelle" class="form-label font-weight-bold"><?= _("Libellé du Compte *") ?></label>
                                <input type="text" class="form-control" id="libelle" name="libelle" value="<?= htmlspecialchars($compte['libelle']) ?>" required>
                            </div>

                            <div class="row">
                                <!-- Nature -->
                                <div class="col-md-6 mb-3">
                                    <label for="nature" class="form-label font-weight-bold"><?= _("Nature du Compte *") ?></label>
                                    <select class="form-select" id="nature" name="nature" required>
                                        <option value="actif" <?= $compte['nature'] === 'actif' ? 'selected' : '' ?>><?= _("Actif (Débit habituel)") ?></option>
                                        <option value="passif" <?= $compte['nature'] === 'passif' ? 'selected' : '' ?>><?= _("Passif (Crédit habituel)") ?></option>
                                        <option value="charge" <?= $compte['nature'] === 'charge' ? 'selected' : '' ?>><?= _("Charge (Classe 6)") ?></option>
                                        <option value="produit" <?= $compte['nature'] === 'produit' ? 'selected' : '' ?>><?= _("Produit (Classe 7)") ?></option>
                                    </select>
                                </div>

                                <!-- Compte parent -->
                                <div class="col-md-6 mb-3">
                                    <label for="compte_parent_id" class="form-label font-weight-bold"><?= _("Compte Parent (Arborescence)") ?></label>
                                    <select class="form-select" id="compte_parent_id" name="compte_parent_id">
                                        <option value=""><?= _("-- Aucun (Compte Racine) --") ?></option>
                                        <?php foreach ($allComptes as $p): ?>
                                            <?php if ($p['id'] != $compte['id']): ?>
                                                <option value="<?= $p['id'] ?>" <?= $compte['compte_parent_id'] == $p['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($p['numero'] . ' — ' . $p['libelle']) ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Autoriser écriture -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="autoriser_ecriture" name="autoriser_ecriture" value="1" <?= !empty($compte['autoriser_ecriture']) ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold" for="autoriser_ecriture"><?= _("Autoriser la saisie d'écritures directes") ?></label>
                                    </div>
                                </div>

                                <!-- Statut Actif -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="actif" name="actif" value="1" <?= !empty($compte['actif']) ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold" for="actif"><?= _("Actif dans le référentiel") ?></label>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-end">
                                <a href="/comptes-comptables" class="btn btn-outline-secondary me-2"><?= _("Annuler") ?></a>
                                <button type="submit" class="btn btn-primary d-inline-flex align-items-center">
                                    <i class="ph-duotone ph-check-square-offset me-1"></i> <?= _("Mettre à jour le Compte") ?>
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
