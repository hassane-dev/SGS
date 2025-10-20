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
                    <div class="col-md-6 form-group">
                        <label for="nombreCompositionParSequence">Nombre de compositions autorisées par séquence</label>
                        <input type="number" id="nombreCompositionParSequence" name="nombreCompositionParSequence" class="form-control" value="<?= htmlspecialchars($params['nombreCompositionParSequence']) ?>">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="noteMaximale">Note maximale pour une composition</label>
                        <input type="number" step="0.25" id="noteMaximale" name="noteMaximale" class="form-control" value="<?= htmlspecialchars($params['noteMaximale']) ?>">
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="dateDebutInsertion">Date d'ouverture globale de la saisie</label>
                        <input type="datetime-local" id="dateDebutInsertion" name="dateDebutInsertion" class="form-control" value="<?= !empty($params['dateDebutInsertion']) ? (new DateTime($params['dateDebutInsertion']))->format('Y-m-d\TH:i') : '' ?>">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="dateFinInsertion">Date de fermeture globale de la saisie</label>
                        <input type="datetime-local" id="dateFinInsertion" name="dateFinInsertion" class="form-control" value="<?= !empty($params['dateFinInsertion']) ? (new DateTime($params['dateFinInsertion']))->format('Y-m-d\TH:i') : '' ?>">
                    </div>
                </div>
                <div class="form-group form-check">
                    <input type="checkbox" id="deblocageUrgence" name="deblocageUrgence" class="form-check-input" value="1" <?= $params['deblocageUrgence'] ? 'checked' : '' ?>>
                    <label for="deblocageUrgence" class="form-check-label">Débloquer la saisie en urgence</label>
                    <small class="form-text text-muted">Cocher cette case ignore les dates de restriction pour tous.</small>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            </div>
        </div>
    </form>
</div>