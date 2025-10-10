<?php
// Expects:
// $eleve (array, can be empty)
// $is_edit (boolean)
// $form_action (string)
?>
<form action="<?= $form_action ?>" method="POST" enctype="multipart/form-data">
    <div class="row g-3">
        <div class="col-md-6">
            <label for="prenom" class="form-label"><?= _('Prénom') ?></label>
            <input type="text" name="prenom" id="prenom" class="form-control" value="<?= htmlspecialchars($eleve['prenom'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label for="nom" class="form-label"><?= _('Nom') ?></label>
            <input type="text" name="nom" id="nom" class="form-control" value="<?= htmlspecialchars($eleve['nom'] ?? '') ?>" required>
        </div>
        <div class="col-12">
            <label for="email" class="form-label"><?= _('Email') ?></label>
            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($eleve['email'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label for="date_naissance" class="form-label"><?= _('Date de Naissance') ?></label>
            <input type="date" name="date_naissance" id="date_naissance" class="form-control" value="<?= htmlspecialchars($eleve['date_naissance'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label for="telephone" class="form-label"><?= _('Téléphone') ?></label>
            <input type="tel" name="telephone" id="telephone" class="form-control" value="<?= htmlspecialchars($eleve['telephone'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label for="photo" class="form-label"><?= _('Photo') ?></label>
            <input type="file" name="photo" id="photo" class="form-control">
            <?php if ($is_edit && !empty($eleve['photo'])): ?>
                <div class="mt-2">
                    <img src="<?= htmlspecialchars($eleve['photo']) ?>" alt="<?= _('Photo actuelle') ?>" style="max-height: 100px;">
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-end">
        <a href="/eleves" class="btn btn-secondary me-2"><?= _('Annuler') ?></a>
        <button type="submit" class="btn btn-primary">
            <?= $is_edit ? _('Mettre à jour') : _('Enregistrer') ?>
        </button>
    </div>
</form>