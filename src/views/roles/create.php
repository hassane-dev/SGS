<h1 class="mb-4">Créer un nouveau Rôle</h1>

<div class="card">
    <div class="card-body">
        <form action="/roles/store" method="POST">
            <div class="mb-3">
                <label for="nom_role" class="form-label">Nom du rôle</label>
                <input type="text" name="nom_role" id="nom_role" class="form-control" required>
            </div>

            <?php if (Auth::can('manage_all_lycees')): ?>
            <div class="mb-3">
                <label for="lycee_id" class="form-label">Portée</label>
                <select name="lycee_id" id="lycee_id" class="form-select">
                    <option value="">Rôle Global</option>
                    <?php foreach ($lycees as $lycee): ?>
                        <option value="<?= $lycee['id_lycee'] ?>">
                            Spécifique à : <?= htmlspecialchars($lycee['nom_lycee']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">
                    Laissez sur "Global" pour les rôles comme Enseignant, ou assignez à une école pour un rôle local spécifique.
                </div>
            </div>
            <?php endif; ?>

            <div class="mt-4 text-end">
                <a href="/roles" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">Créer</button>
            </div>
        </form>
    </div>
</div>