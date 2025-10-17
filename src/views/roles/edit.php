<h1 class="mb-4">Modifier le Rôle : <?= htmlspecialchars($role['nom_role']) ?></h1>

<form action="/roles/update" method="POST">
    <input type="hidden" name="id_role" value="<?= htmlspecialchars($role['id_role']) ?>">

    <div class="row">
        <!-- Colonne des détails du rôle -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    Détails du Rôle
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="nom_role" class="form-label">Nom du rôle</label>
                        <input type="text" name="nom_role" id="nom_role" class="form-control" value="<?= htmlspecialchars($role['nom_role']) ?>" required>
                    </div>

                    <?php if (Auth::can('manage_all_lycees')): ?>
                    <div class="mb-3">
                        <label for="lycee_id" class="form-label">Portée</label>
                        <select name="lycee_id" id="lycee_id" class="form-select">
                            <option value="" <?= !$role['lycee_id'] ? 'selected' : '' ?>>Rôle Global</option>
                            <?php foreach ($lycees as $lycee): ?>
                                <option value="<?= $lycee['id_lycee'] ?>" <?= $role['lycee_id'] == $lycee['id_lycee'] ? 'selected' : '' ?>>
                                    Spécifique à : <?= htmlspecialchars($lycee['nom_lycee']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                        <input type="hidden" name="lycee_id" value="<?= htmlspecialchars($role['lycee_id']) ?>">
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Colonne des permissions -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Permissions
                </div>
                <div class="card-body">
                    <?php
                        // Grouper les permissions par ressource
                        $grouped_permissions = [];
                        foreach ($all_permissions as $p) {
                            $grouped_permissions[$p['resource']][] = $p;
                        }
                        ksort($grouped_permissions);
                    ?>

                    <?php foreach ($grouped_permissions as $resource => $permissions): ?>
                        <div class="mb-3">
                            <h5 class="text-capitalize border-bottom pb-2 mb-2"><?= htmlspecialchars($resource) ?></h5>
                            <div class="row">
                                <?php foreach ($permissions as $p): ?>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $p['id_permission'] ?>" id="perm_<?= $p['id_permission'] ?>"
                                                <?= in_array($p['id_permission'], $role_permission_ids) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="perm_<?= $p['id_permission'] ?>">
                                                <?= htmlspecialchars($p['action']) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 text-end">
        <a href="/roles" class="btn btn-secondary">Annuler</a>
        <button type="submit" class="btn btn-primary">Mettre à jour</button>
    </div>
</form>