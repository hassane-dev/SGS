<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= htmlspecialchars($title) ?></h1>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Les paramètres ont été mis à jour avec succès.</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger">Une erreur est survenue lors de la mise à jour.</div>
    <?php endif; ?>

    <form action="/param-lycee/update" method="POST" enctype="multipart/form-data">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Informations Administratives</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="nom_lycee">Nom officiel du lycée</label>
                        <input type="text" id="nom_lycee" name="nom_lycee" class="form-control" value="<?= htmlspecialchars($params['nom_lycee'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="sigle">Sigle ou abréviation</label>
                        <input type="text" id="sigle" name="sigle" class="form-control" value="<?= htmlspecialchars($params['sigle'] ?? '') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="tel">Téléphone principal</label>
                        <input type="text" id="tel" name="tel" class="form-control" value="<?= htmlspecialchars($params['tel'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="email">Adresse e-mail officielle</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($params['email'] ?? '') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="ville">Ville</label>
                        <input type="text" id="ville" name="ville" class="form-control" value="<?= htmlspecialchars($params['ville'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="quartier">Quartier</label>
                        <input type="text" id="quartier" name="quartier" class="form-control" value="<?= htmlspecialchars($params['quartier'] ?? '') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="ruelle">Ruelle</label>
                        <input type="text" id="ruelle" name="ruelle" class="form-control" value="<?= htmlspecialchars($params['ruelle'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="boite_postale">Boîte Postale</label>
                        <input type="text" id="boite_postale" name="boite_postale" class="form-control" value="<?= htmlspecialchars($params['boite_postale'] ?? '') ?>">
                    </div>
                </div>
                 <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="arrete">Arrêté d’ouverture</label>
                        <input type="text" id="arrete" name="arrete" class="form-control" value="<?= htmlspecialchars($params['arrete'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="arrondissement">Arrondissement</label>
                        <input type="text" id="arrondissement" name="arrondissement" class="form-control" value="<?= htmlspecialchars($params['arrondissement'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="devise">Devise ou slogan du lycée</label>
                    <input type="text" id="devise" name="devise" class="form-control" value="<?= htmlspecialchars($params['devise'] ?? '') ?>">
                </div>
                 <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="logo">Logo officiel du lycée</label>
                        <input type="file" id="logo" name="logo" class="form-control">
                        <input type="hidden" name="current_logo" value="<?= htmlspecialchars($params['logo'] ?? '') ?>">
                        <?php if (!empty($params['logo'])): ?>
                            <img src="<?= htmlspecialchars($params['logo']) ?>" alt="Logo actuel" class="img-thumbnail mt-2" style="max-height: 100px;">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="type_lycee">Type de lycée</label>
                        <select id="type_lycee" name="type_lycee" class="form-control">
                            <option value="public" <?= ($params['type_lycee'] ?? '') == 'public' ? 'selected' : '' ?>>Public</option>
                            <option value="prive" <?= ($params['type_lycee'] ?? '') == 'prive' ? 'selected' : '' ?>>Privé</option>
                            <option value="semi-public" <?= ($params['type_lycee'] ?? '') == 'semi-public' ? 'selected' : '' ?>>Semi-public</option>
                        </select>
                    </div>
                </div>
                <div class="form-group form-check">
                    <input type="checkbox" id="boutique" name="boutique" class="form-check-input" value="1" <?= !empty($params['boutique']) ? 'checked' : '' ?>>
                    <label for="boutique" class="form-check-label">Activer la gestion de la boutique</label>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            </div>
        </div>
    </form>
</div>