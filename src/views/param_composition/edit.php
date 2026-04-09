<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= htmlspecialchars($title) ?></h1>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Les paramètres ont été mis à jour avec succès.</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger">Une erreur est survenue lors de la mise à jour.</div>
    <?php endif; ?>

    <form action="/param-composition/update" method="POST">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Configuration des Compositions pour l'Année en Cours</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    Ces paramètres s'appliquent à toute l'école pour l'année académique active.
                </div>
                <div class="row">
                    <div class="col-md-6 form-group mb-3">
                        <label for="nombre_composition_par_sequence">Nombre de compositions autorisées par séquence</label>
                        <input type="number" id="nombre_composition_par_sequence" name="nombre_composition_par_sequence" class="form-control" value="<?= htmlspecialchars($params['nombre_composition_par_sequence'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 form-group mb-3">
                        <label for="note_maximale">Note maximale pour une composition</label>
                        <input type="number" step="0.25" id="note_maximale" name="note_maximale" class="form-control" value="<?= htmlspecialchars($params['note_maximale'] ?? '') ?>">
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6 form-group mb-3">
                        <label for="date_debut_insertion">Date d'ouverture globale de la saisie</label>
                        <input type="datetime-local" id="date_debut_insertion" name="date_debut_insertion" class="form-control" value="<?= !empty($params['date_debut_insertion']) ? (new DateTime($params['date_debut_insertion']))->format('Y-m-d\TH:i') : '' ?>">
                    </div>
                    <div class="col-md-6 form-group mb-3">
                        <label for="date_fin_insertion">Date de fermeture globale de la saisie</label>
                        <input type="datetime-local" id="date_fin_insertion" name="date_fin_insertion" class="form-control" value="<?= !empty($params['date_fin_insertion']) ? (new DateTime($params['date_fin_insertion']))->format('Y-m-d\TH:i') : '' ?>">
                    </div>
                </div>
                <div class="form-group form-check mb-3">
                    <input type="checkbox" id="deblocage_urgence" name="deblocage_urgence" class="form-check-input" value="1" <?= ($params['deblocage_urgence'] ?? 0) ? 'checked' : '' ?>>
                    <label for="deblocage_urgence" class="form-check-label">Débloquer la saisie en urgence</label>
                    <small class="form-text text-muted">Cocher cette case ignore les dates de restriction pour tous.</small>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            </div>
        </div>
    </form>
</div>