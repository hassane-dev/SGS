<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-group">
    <label for="nom">Nom de la séquence <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="nom" name="nom" placeholder="Ex: Trimestre 1, Séquence 2" value="<?= htmlspecialchars($sequence['nom'] ?? '') ?>" required>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="type">Type <span class="text-danger">*</span></label>
            <select class="form-control" id="type" name="type" required>
                <option value="trimestrielle" <?= (isset($sequence['type']) && $sequence['type'] === 'trimestrielle') ? 'selected' : '' ?>>
                    Trimestrielle
                </option>
                <option value="semestrielle" <?= (isset($sequence['type']) && $sequence['type'] === 'semestrielle') ? 'selected' : '' ?>>
                    Semestrielle
                </option>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="statut">Statut <span class="text-danger">*</span></label>
            <select class="form-control" id="statut" name="statut" required>
                <option value="ouverte" <?= (isset($sequence['statut']) && $sequence['statut'] === 'ouverte') ? 'selected' : '' ?>>
                    Ouverte
                </option>
                <option value="fermee" <?= (isset($sequence['statut']) && $sequence['statut'] === 'fermee') ? 'selected' : '' ?>>
                    Fermée
                </option>
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="date_debut">Date de début <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?= htmlspecialchars($sequence['date_debut'] ?? '') ?>" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="date_fin">Date de fin <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?= htmlspecialchars($sequence['date_fin'] ?? '') ?>" required>
        </div>
    </div>
</div>


<button type="submit" class="btn btn-primary mt-3">Enregistrer</button>
<a href="/sequences" class="btn btn-secondary mt-3">Annuler</a>