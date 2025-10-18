<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-group">
    <label for="nom_matiere">Nom de la matière <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="nom_matiere" name="nom_matiere" value="<?= htmlspecialchars($matiere['nom_matiere'] ?? '') ?>" required>
</div>

<div class="form-group">
    <label for="description">Description</label>
    <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($matiere['description'] ?? '') ?></textarea>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="type">Type</label>
            <input type="text" class="form-control" id="type" name="type" placeholder="Ex: Scientifique, Littéraire" value="<?= htmlspecialchars($matiere['type'] ?? '') ?>">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="cycle_concerne">Cycle Concerné</label>
            <input type="text" class="form-control" id="cycle_concerne" name="cycle_concerne" placeholder="Ex: Lycée, CEG" value="<?= htmlspecialchars($matiere['cycle_concerne'] ?? '') ?>">
        </div>
    </div>
</div>

<div class="form-group">
    <label for="statut">Statut <span class="text-danger">*</span></label>
    <select class="form-control" id="statut" name="statut" required>
        <option value="principale" <?= (isset($matiere['statut']) && $matiere['statut'] === 'principale') ? 'selected' : '' ?>>
            Principale
        </option>
        <option value="optionnelle" <?= (isset($matiere['statut']) && $matiere['statut'] === 'optionnelle') ? 'selected' : '' ?>>
            Optionnelle
        </option>
    </select>
</div>

<button type="submit" class="btn btn-primary">Enregistrer</button>
<a href="/matieres" class="btn btn-secondary">Annuler</a>