<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Faire l'appel</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Étape 1: Choisir une classe</h6>
        </div>
        <div class="card-body">
            <p>Veuillez sélectionner la classe pour laquelle vous souhaitez faire l'appel.</p>
            <form action="/assiduite/faire-appel" method="GET">
                <div class="form-group">
                    <label for="classe_id">Classe</label>
                    <select name="classe_id" id="classe_id" class="form-control" required>
                        <option value="">-- Sélectionnez une classe --</option>
                        <?php
                        // To avoid duplicates in dropdown
                        $displayed_classes = [];
                        foreach ($classes as $classe):
                            if (!in_array($classe['classe_id'], $displayed_classes)):
                                $displayed_classes[] = $classe['classe_id'];
                        ?>
                            <option value="<?= $classe['classe_id'] ?>">
                                <?= htmlspecialchars($classe['nom_classe']) ?>
                            </option>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Continuer</button>
            </form>
        </div>
    </div>
</div>
