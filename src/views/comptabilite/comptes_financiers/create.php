<?php
$title = _("Créer un Compte Financier");
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
                            <li class="breadcrumb-item"><a href="/comptes-financiers"><?= _('Comptes Financiers') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Créer") ?></li>
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
                <div class="card">
                    <div class="card-header">
                        <h5><?= _("Informations du Compte Financier") ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="/comptes-financiers/store" method="POST">
                            <!-- Nom du compte -->
                            <div class="mb-3">
                                <label for="nom_compte" class="form-label font-weight-bold"><?= _("Nom du Compte *") ?></label>
                                <input type="text" class="form-control" id="nom_compte" name="nom_compte" placeholder="Ex: Caisse Principale de Scolarité, Compte SGBGE, Orange Money Caisse" required>
                                <div class="form-text text-muted"><?= _("Un nom descriptif unique pour identifier le compte.") ?></div>
                            </div>

                            <div class="row">
                                <!-- Type de compte -->
                                <div class="col-md-6 mb-3">
                                    <label for="type_compte" class="form-label font-weight-bold"><?= _("Type de Compte *") ?></label>
                                    <select class="form-select" id="type_compte" name="type_compte" required>
                                        <option value="caisse"><?= _("Caisse physique (Espèces)") ?></option>
                                        <option value="banque"><?= _("Banque (Chèques, Virements)") ?></option>
                                        <option value="mobile_money"><?= _("Mobile Money (Orange, MTN, etc.)") ?></option>
                                        <option value="autre"><?= _("Autre type financier") ?></option>
                                    </select>
                                </div>

                                <!-- Solde Initial -->
                                <div class="col-md-6 mb-3">
                                    <label for="solde_courant" class="form-label font-weight-bold"><?= _("Solde d'Ouverture (FCFA)") ?></label>
                                    <input type="number" step="0.01" class="form-control" id="solde_courant" name="solde_courant" value="0.00">
                                    <div class="form-text text-muted"><?= _("Solde disponible actuel à la création.") ?></div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Devise -->
                                <div class="col-md-6 mb-3">
                                    <label for="devise" class="form-label font-weight-bold"><?= _("Devise") ?></label>
                                    <input type="text" class="form-control" id="devise" name="devise" value="FCFA" readonly>
                                </div>

                                <!-- Responsable -->
                                <div class="col-md-6 mb-3">
                                    <label for="responsable_id" class="form-label font-weight-bold"><?= _("Responsable du Compte") ?></label>
                                    <select class="form-select" id="responsable_id" name="responsable_id">
                                        <option value=""><?= _("-- Aucun --") ?></option>
                                        <?php foreach ($responsables as $r): ?>
                                            <option value="<?= $r['id_user'] ?>"><?= htmlspecialchars($r['prenom'] . ' ' . $r['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text text-muted"><?= _("La personne affectée au maniement ou à l'audit du compte.") ?></div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Compte Comptable Général (OHADA) -->
                                <div class="col-md-6 mb-3">
                                    <label for="compte_comptable_id" class="form-label font-weight-bold"><?= _("Compte Comptable Général (OHADA) *") ?></label>
                                    <select class="form-select" id="compte_comptable_id" name="compte_comptable_id" required>
                                        <option value=""><?= _("-- Sélectionner un compte comptable --") ?></option>
                                        <?php if (!empty($comptesComptables)): ?>
                                            <?php foreach ($comptesComptables as $cc): ?>
                                                <option value="<?= $cc['id'] ?>">
                                                    <?= htmlspecialchars($cc['numero'] . ' — ' . $cc['libelle']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <div class="form-text text-muted"><?= _("Compte comptable lié du référentiel (ex: 571100 — Coffre-fort, 571200 — Caisse guichet).") ?></div>
                                </div>

                                <!-- Coffre Principal unique -->
                                <div class="col-md-6 mb-3 d-flex align-items-center">
                                    <div class="form-check form-switch mt-3">
                                        <input class="form-check-input" type="checkbox" id="est_coffre" name="est_coffre" value="1">
                                        <label class="form-check-label fw-bold" for="est_coffre"><?= _("Marquer comme le Coffre Principal unique") ?></label>
                                        <div class="form-text text-muted"><?= _("Le coffre fort récepteur de toutes les remises de caisse de l'établissement.") ?></div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <!-- Buttons -->
                            <div class="d-flex justify-content-end">
                                <a href="/comptes-financiers" class="btn btn-outline-secondary me-2"><?= _("Annuler") ?></a>
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