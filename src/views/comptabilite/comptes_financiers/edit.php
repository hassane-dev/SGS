<?php
$title = _("Modifier le Compte Financier");
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
                <div class="card">
                    <div class="card-header">
                        <h5><?= _("Éditer les paramètres du compte") ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="/comptes-financiers/update" method="POST">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($compte['id']) ?>">

                            <!-- Nom du compte -->
                            <div class="mb-3">
                                <label for="nom_compte" class="form-label font-weight-bold"><?= _("Nom du Compte *") ?></label>
                                <input type="text" class="form-control" id="nom_compte" name="nom_compte" value="<?= htmlspecialchars($compte['nom_compte']) ?>" required>
                            </div>

                            <div class="row">
                                <!-- Type de compte -->
                                <div class="col-md-6 mb-3">
                                    <label for="type_compte" class="form-label font-weight-bold"><?= _("Type de Compte *") ?></label>
                                    <select class="form-select" id="type_compte" name="type_compte" required>
                                        <option value="caisse" <?= $compte['type_compte'] === 'caisse' ? 'selected' : '' ?>><?= _("Caisse physique (Espèces)") ?></option>
                                        <option value="banque" <?= $compte['type_compte'] === 'banque' ? 'selected' : '' ?>><?= _("Banque (Chèques, Virements)") ?></option>
                                        <option value="mobile_money" <?= $compte['type_compte'] === 'mobile_money' ? 'selected' : '' ?>><?= _("Mobile Money (Orange, MTN, etc.)") ?></option>
                                        <option value="autre" <?= $compte['type_compte'] === 'autre' ? 'selected' : '' ?>><?= _("Autre type financier") ?></option>
                                    </select>
                                </div>

                                <!-- Solde Courant (Lecture seule pour préserver les audits) -->
                                <div class="col-md-6 mb-3">
                                    <label for="solde_courant_readonly" class="form-label font-weight-bold"><?= _("Solde Courant Actuel") ?></label>
                                    <input type="text" class="form-control" id="solde_courant_readonly" value="<?= number_format($compte['solde_courant'], 2, ',', ' ') ?> <?= htmlspecialchars($compte['devise']) ?>" readonly>
                                    <div class="form-text text-muted"><?= _("Pour modifier le solde, veuillez passer par des flux d'ajustement ou des sessions de caisse.") ?></div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Devise -->
                                <div class="col-md-6 mb-3">
                                    <label for="devise" class="form-label font-weight-bold"><?= _("Devise") ?></label>
                                    <input type="text" class="form-control" id="devise" name="devise" value="<?= htmlspecialchars($compte['devise']) ?>" readonly>
                                </div>

                                <!-- Responsable -->
                                <div class="col-md-6 mb-3">
                                    <label for="responsable_id" class="form-label font-weight-bold"><?= _("Responsable du Compte") ?></label>
                                    <select class="form-select" id="responsable_id" name="responsable_id">
                                        <option value=""><?= _("-- Aucun --") ?></option>
                                        <?php foreach ($responsables as $r): ?>
                                            <option value="<?= $r['id_user'] ?>" <?= $compte['responsable_id'] == $r['id_user'] ? 'selected' : '' ?>><?= htmlspecialchars($r['prenom'] . ' ' . $r['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="statut" class="form-label font-weight-bold"><?= _("Statut du Compte") ?></label>
                                <select class="form-select" id="statut" name="statut" required>
                                    <option value="actif" <?= $compte['statut'] === 'actif' ? 'selected' : '' ?>><?= _("Actif") ?></option>
                                    <option value="suspendu" <?= $compte['statut'] === 'suspendu' ? 'selected' : '' ?>><?= _("Suspendu") ?></option>
                                </select>
                            </div>

                            <hr>

                            <!-- Buttons -->
                            <div class="d-flex justify-content-end">
                                <a href="/comptes-financiers" class="btn btn-outline-secondary me-2"><?= _("Annuler") ?></a>
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