<?php
// This is a partial view, expects:
// $annee (array, optional) -> The academic year data to pre-fill the form.
// $is_edit (bool) -> Flag to indicate if this is for editing an existing entry.
// $form_action (string) -> The URL where the form should be submitted.
// $errors (array, optional) -> Validation errors.
// $old_post (array, optional) -> Previously submitted form data to repopulate on error.

// Use old post data if available, otherwise use model data
$libelle = $old_post['libelle'] ?? $annee['libelle'] ?? '';
$date_debut = $old_post['date_debut'] ?? $annee['date_debut'] ?? '';
$date_fin = $old_post['date_fin'] ?? $annee['date_fin'] ?? '';

?>
<form action="<?= htmlspecialchars($form_action) ?>" method="POST">
    <?php if ($is_edit): ?>
        <input type="hidden" name="id" value="<?= htmlspecialchars($annee['id'] ?? '') ?>">
    <?php endif; ?>

    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="libelle" class="form-label"><?= _('Libellé') ?> <span class="text-danger">*</span></label>
            <input type="text" name="libelle" id="libelle" class="form-control" placeholder="<?= _('Ex: 2024-2025') ?>" value="<?= htmlspecialchars($libelle) ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="date_debut" class="form-label"><?= _('Date de Début') ?> <span class="text-danger">*</span></label>
            <input type="date" name="date_debut" id="date_debut" class="form-control" value="<?= htmlspecialchars($date_debut) ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="date_fin" class="form-label"><?= _('Date de Fin') ?> <span class="text-danger">*</span></label>
            <input type="date" name="date_fin" id="date_fin" class="form-control" value="<?= htmlspecialchars($date_fin) ?>" required>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <a href="/annees-academiques" class="btn btn-secondary me-2"><?= _('Annuler') ?></a>
        <button type="submit" class="btn btn-primary">
            <?= $is_edit ? _('Mettre à jour') : _('Enregistrer') ?>
        </button>
    </div>
</form>