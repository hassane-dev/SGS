<div class="mb-3">
    <label for="resource" class="form-label">Ressource</label>
    <input type="text" class="form-control" id="resource" name="resource" value="<?= htmlspecialchars($permission['resource'] ?? '') ?>" required>
    <div class="form-text">Exemple : `articles`, `users`, `invoices`</div>
</div>
<div class="mb-3">
    <label for="action" class="form-label">Action</label>
    <input type="text" class="form-control" id="action" name="action" value="<?= htmlspecialchars($permission['action'] ?? '') ?>" required>
    <div class="form-text">Exemple : `view`, `create`, `edit`, `delete`</div>
</div>
<div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($permission['description'] ?? '') ?></textarea>
</div>

<a href="/permissions" class="btn btn-secondary">Annuler</a>
<button type="submit" class="btn btn-primary"><?= $is_edit ? 'Mettre à jour' : 'Créer' ?></button>