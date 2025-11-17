<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="mb-3">
    <label for="nom_matiere" class="form-label"><?= _('Nom de la matière') ?> <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="nom_matiere" name="nom_matiere" value="<?= htmlspecialchars($matiere['nom_matiere'] ?? '') ?>" required>
</div>

<div class="mb-3">
    <label for="description" class="form-label"><?= _('Description') ?></label>
    <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($matiere['description'] ?? '') ?></textarea>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="type" class="form-label"><?= _('Type') ?></label>
        <input type="text" class="form-control" id="type" name="type" placeholder="<?= _('Ex: Scientifique, Littéraire') ?>" value="<?= htmlspecialchars($matiere['type'] ?? '') ?>">
    </div>
    <div class="col-md-6 mb-3">
        <label for="cycle_concerne" class="form-label"><?= _('Cycle Concerné') ?></label>
        <input type="text" class="form-control" id="cycle_concerne" name="cycle_concerne" placeholder="<?= _('Ex: Lycée, CEG') ?>" value="<?= htmlspecialchars($matiere['cycle_concerne'] ?? '') ?>">
    </div>
</div>

<div class="mb-3">
    <label for="statut" class="form-label"><?= _('Statut') ?> <span class="text-danger">*</span></label>
    <select class="form-select" id="statut" name="statut" required>
        <option value="principale" <?= (isset($matiere['statut']) && $matiere['statut'] === 'principale') ? 'selected' : '' ?>>
            <?= _('Principale') ?>
        </option>
        <option value="optionnelle" <?= (isset($matiere['statut']) && $matiere['statut'] === 'optionnelle') ? 'selected' : '' ?>>
            <?= _('Optionnelle') ?>
        </option>
    </select>
</div>
