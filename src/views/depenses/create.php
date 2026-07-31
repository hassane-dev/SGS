<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Engagement d\'une Nouvelle Dépense') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <pre style="white-space: pre-wrap; margin-bottom: 0; font-family: inherit;"><?= htmlspecialchars($_SESSION['error_message']) ?></pre>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <form action="/depenses/store" method="POST" enctype="multipart/form-data" class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><?= _('Numéro de pièce (Optionnel)') ?></label>
                                <input type="text" name="numero_piece" class="form-control" placeholder="DEP-<?= date('Ymd') ?>-XXXX">
                                <small class="text-muted"><?= _('Sera généré automatiquement si laissé vide.') ?></small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required"><?= _('Bénéficiaire') ?></label>
                                <select name="beneficiaire_id" class="form-select" required>
                                    <option value=""><?= _('Sélectionner un bénéficiaire') ?></option>
                                    <?php foreach ($beneficiaires as $b): ?>
                                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nom_beneficiaire']) ?> (<?= ucfirst($b['type']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required"><?= _('Catégorie de nature de dépense') ?></label>
                                <select name="categorie_id" class="form-select" required>
                                    <option value=""><?= _('Sélectionner une catégorie') ?></option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom_categorie']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?= _('Centre de coûts (Optionnel)') ?></label>
                                <select name="centre_cout_id" class="form-select">
                                    <option value=""><?= _('Aucun centre analytique') ?></option>
                                    <?php foreach ($centres as $cc): ?>
                                        <option value="<?= $cc['id'] ?>"><?= htmlspecialchars($cc['nom_centre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label required"><?= _('Montant total (FCFA)') ?></label>
                                <input type="number" name="montant" step="0.01" min="0.01" class="form-control" required placeholder="0.00">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label required"><?= _('Motif/Désignation de la dépense') ?></label>
                                <textarea name="motif" class="form-control" rows="4" required placeholder="<?= _('Saisissez la description de la dépense...') ?>"></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label"><?= _('Justificatifs / Pièces jointes') ?></label>
                                <input type="file" name="pieces_jointes[]" class="form-control" multiple>
                                <small class="text-muted"><?= _('Formats autorisés: PDF, PNG, JPG, WEBP, DOC, Excel. Max 5Mo par fichier.') ?></small>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-between">
                            <a href="/depenses" class="btn btn-outline-secondary"><?= _('Annuler') ?></a>
                            <div>
                                <button type="submit" name="save_draft" value="1" class="btn btn-secondary me-2">
                                    <i class="ti ti-device-floppy"></i> <?= _('Enregistrer comme Brouillon') ?>
                                </button>
                                <button type="submit" name="submit_direct" value="1" class="btn btn-primary">
                                    <i class="ti ti-send"></i> <?= _('Soumettre pour Approbation') ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>