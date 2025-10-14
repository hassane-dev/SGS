<div class="form-group mb-3">
    <label for="nom_classe" class="form-label">Nom de la Classe</label>
    <input type="text" class="form-control" id="nom_classe" name="nom_classe" value="<?= $classe['nom_classe'] ?? '' ?>" required>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <div class="form-group">
            <label for="niveau" class="form-label">Niveau</label>
            <input type="text" class="form-control" id="niveau" name="niveau" value="<?= $classe['niveau'] ?? '' ?>" placeholder="Ex: 6ème, Seconde">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="serie" class="form-label">Série</label>
            <input type="text" class="form-control" id="serie" name="serie" value="<?= $classe['serie'] ?? '' ?>" placeholder="Ex: C, D, A4">
        </div>
    </div>
</div>

<div class="form-group mb-3">
    <label for="cycle_id" class="form-label">Cycle Académique</label>
    <select class="form-select" id="cycle_id" name="cycle_id" required>
        <?php foreach ($cycles as $cycle): ?>
            <option value="<?= $cycle['id_cycle'] ?>" <?= (isset($classe['cycle_id']) && $classe['cycle_id'] == $cycle['id_cycle']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cycle['nom_cycle']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<?php if (Auth::can('view_all_lycees')): ?>
<div class="form-group mb-3">
    <label for="lycee_id" class="form-label">Lycée</label>
    <select class="form-select" id="lycee_id" name="lycee_id" required>
        <?php foreach ($lycees as $lycee): ?>
            <option value="<?= $lycee['id_lycee'] ?>" <?= (isset($classe['lycee_id']) && $classe['lycee_id'] == $lycee['id_lycee']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($lycee['nom_lycee']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<?php endif; ?>