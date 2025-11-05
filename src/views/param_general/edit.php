<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= htmlspecialchars($title) ?></h1>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Les paramètres ont été mis à jour avec succès.</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger">Une erreur est survenue lors de la mise à jour.</div>
    <?php endif; ?>

    <form action="/param-general/update" method="POST">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Configuration Globale</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="devise_pays">Nom complet de la devise</label>
                        <input type="text" id="devise_pays" name="devise_pays" class="form-control" placeholder="Ex: Franc CFA" value="<?= htmlspecialchars($params['devise_pays'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="monnaie">Symbole monétaire</label>
                        <input type="text" id="monnaie" name="monnaie" class="form-control" placeholder="Ex: FCFA" value="<?= htmlspecialchars($params['monnaie'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="modalite_paiement">Modalités de paiement autorisées</label>
                    <input type="text" id="modalite_paiement" name="modalite_paiement" class="form-control" placeholder="Ex: Espèces, Versement, Mobile Money" value="<?= htmlspecialchars($params['modalite_paiement'] ?? '') ?>">
                    <small class="form-text text-muted">Séparez les différentes modalités par une virgule.</small>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="nb_langue">Nombre de langues sur les documents</label>
                        <select id="nb_langue" name="nb_langue" class="form-control">
                            <option value="1" <?= ($params['nb_langue'] ?? 1) == 1 ? 'selected' : '' ?>>1</option>
                            <option value="2" <?= ($params['nb_langue'] ?? 1) == 2 ? 'selected' : '' ?>>2</option>
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="langue_1">Première langue</label>
                        <input type="text" id="langue_1" name="langue_1" class="form-control" placeholder="Ex: Français" value="<?= htmlspecialchars($params['langue_1'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="langue_2">Deuxième langue (si applicable)</label>
                        <input type="text" id="langue_2" name="langue_2" class="form-control" placeholder="Ex: Anglais" value="<?= htmlspecialchars($params['langue_2'] ?? '') ?>">
                    </div>
                </div>
                <hr>
                <div class="form-group">
                    <label for="sequence_annuelle">Type de séquence annuelle</label>
                     <select id="sequence_annuelle" name="sequence_annuelle" class="form-control">
                        <option value="Trimestrielle" <?= ($params['sequence_annuelle'] ?? '') == 'Trimestrielle' ? 'selected' : '' ?>>Trimestrielle</option>
                        <option value="Semestrielle" <?= ($params['sequence_annuelle'] ?? '') == 'Semestrielle' ? 'selected' : '' ?>>Semestrielle</option>
                    </select>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            </div>
        </div>
    </form>
</div>