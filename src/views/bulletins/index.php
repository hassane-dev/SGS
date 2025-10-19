<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Génération des Bulletins</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Sélectionner une classe et une séquence</h6>
        </div>
        <div class="card-body">
            <form action="/bulletins/class_results" method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="classe_id">Classe</label>
                            <select name="classe_id" id="classe_id" class="form-control" required>
                                <option value="">-- Choisir une classe --</option>
                                <?php foreach ($classes as $classe): ?>
                                    <option value="<?= $classe['id_classe'] ?>"><?= htmlspecialchars($classe['nom_classe']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="sequence_id">Séquence</label>
                            <select name="sequence_id" id="sequence_id" class="form-control" required>
                                <option value="">-- Choisir une séquence --</option>
                                <?php foreach ($sequences as $sequence): ?>
                                    <option value="<?= $sequence['id'] ?>"><?= htmlspecialchars($sequence['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Afficher les Résultats</button>
            </form>
        </div>
    </div>
</div>