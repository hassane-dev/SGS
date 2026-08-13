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
                            <h5 class="m-b-10"><?= _("Modifier le Fournisseur") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="/achats/fournisseurs"><?= _("Fournisseurs") ?></a></li>
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
            <div class="col-sm-12 col-md-10 offset-md-1">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="ph-duotone ph-pencil-circle me-2 text-warning"></i><?= _("Modifier la fiche fournisseur") ?> : <?= htmlspecialchars($fournisseur['raison_sociale']) ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="/achats/fournisseurs/edit?id=<?= $fournisseur['id'] ?>" method="POST">
                            <div class="row g-3">
                                <!-- Raison Sociale -->
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold" for="raison_sociale"><?= _("Raison Sociale") ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="raison_sociale" id="raison_sociale" class="form-control" value="<?= htmlspecialchars($fournisseur['raison_sociale']) ?>" required>
                                </div>

                                <!-- Code Fournisseur (Immutable) -->
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold" for="code_fournisseur"><?= _("Code Fournisseur (Non modifiable)") ?></label>
                                    <input type="text" id="code_fournisseur" class="form-control" value="<?= htmlspecialchars($fournisseur['code_fournisseur']) ?>" disabled>
                                </div>

                                <!-- Compte Comptable Tiers -->
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold" for="compte_comptable_tiers"><?= _("Compte Tiers OHADA") ?></label>
                                    <input type="text" name="compte_comptable_tiers" id="compte_comptable_tiers" class="form-control" value="<?= htmlspecialchars($fournisseur['compte_comptable_tiers'] ?: '401100') ?>">
                                </div>

                                <!-- Statut -->
                                <div class="col-md-6 d-flex align-items-center">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" name="actif" id="actifSwitch" value="1" <?= $fournisseur['actif'] ? 'checked' : '' ?>>
                                        <label class="form-check-label font-weight-bold" for="actifSwitch"><?= _("Fournisseur Actif") ?></label>
                                    </div>
                                </div>

                                <!-- NIF -->
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold" for="nif"><?= _("NIF (Numéro d'Identification Fiscale)") ?></label>
                                    <input type="text" name="nif" id="nif" class="form-control" value="<?= htmlspecialchars($fournisseur['nif'] ?? '') ?>">
                                </div>

                                <!-- RCCM -->
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold" for="rccm"><?= _("RCCM") ?></label>
                                    <input type="text" name="rccm" id="rccm" class="form-control" value="<?= htmlspecialchars($fournisseur['rccm'] ?? '') ?>">
                                </div>

                                <hr class="my-4">
                                <h5><i class="ph-duotone ph-phone me-2 text-primary"></i><?= _("Contacts & Coordonnées") ?></h5>

                                <!-- Nom du Contact -->
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold" for="contact_nom"><?= _("Nom du contact principal") ?></label>
                                    <input type="text" name="contact_nom" id="contact_nom" class="form-control" value="<?= htmlspecialchars($fournisseur['contact_nom'] ?? '') ?>">
                                </div>

                                <!-- Téléphone -->
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold" for="telephone"><?= _("Téléphone") ?></label>
                                    <input type="text" name="telephone" id="telephone" class="form-control" value="<?= htmlspecialchars($fournisseur['telephone'] ?? '') ?>">
                                </div>

                                <!-- Email -->
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold" for="email"><?= _("Adresse email") ?></label>
                                    <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($fournisseur['email'] ?? '') ?>">
                                </div>

                                <!-- Adresse physique -->
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold" for="adresse"><?= _("Adresse physique") ?></label>
                                    <input type="text" name="adresse" id="adresse" class="form-control" value="<?= htmlspecialchars($fournisseur['adresse'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-end gap-2">
                                <a href="/achats/fournisseurs" class="btn btn-light-secondary"><?= _("Annuler") ?></a>
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
