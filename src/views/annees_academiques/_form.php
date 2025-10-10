<?php
// Expects:
// $annee (array, can be empty)
// $is_edit (boolean)
// $form_action (string)
?>
<form action="<?= $form_action ?>" method="POST">
    <?php if ($is_edit): ?>
        <input type="hidden" name="id" value="<?= htmlspecialchars($annee['id'] ?? '') ?>">
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-12">
            <label for="libelle" class="form-label"><?= _('Libellé') ?></label>
            <input type="text" name="libelle" id="libelle" class="form-control" placeholder="Ex: 2024-2025" value="<?= htmlspecialchars($annee['libelle'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label for="date_debut" class="form-label"><?= _('Date de Début') ?></label>
            <input type="date" name="date_debut" id="date_debut" class="form-control" value="<?= htmlspecialchars($annee['date_debut'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label for="date_fin" class="form-label"><?= _('Date de Fin') ?></label>
            <input type="date" name="date_fin" id="date_fin" class="form-control" value="<?= htmlspecialchars($annee['date_fin'] ?? '') ?>" required>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-end">
        <a href="/annees-academiques" class="btn btn-secondary me-2"><?= _('Annuler') ?></a>
        <button type="submit" class="btn btn-primary">
            <?= $is_edit ? _('Mettre à jour') : _('Enregistrer') ?>
        </button>
    </div>
</form>