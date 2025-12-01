<form action="/frais/store" method="POST">

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="row mb-3">
        <label class="col-sm-3 col-form-label">Type de configuration</label>
        <div class="col-sm-9">
             <select class="form-select" name="type_config" id="type_config" required>
                <option value="">Sélectionnez un type</option>
                <option value="cycle" <?= ($old_input['type_config'] ?? '') === 'cycle' ? 'selected' : '' ?>>Par Cycle</option>
                <option value="plage" <?= ($old_input['type_config'] ?? '') === 'plage' ? 'selected' : '' ?>>Par Plage de Niveaux</option>
            </select>
        </div>
    </div>

    <!-- Configuration par Cycle -->
    <div id="config_cycle" class="config-fields" style="display:none;">
        <div class="row mb-3">
            <label for="cycle" class="col-sm-3 col-form-label">Cycle</label>
            <div class="col-sm-9">
                <select class="form-select" id="cycle" name="cycle">
                    <option value="CEG">CEG</option>
                    <option value="Lycée">Lycée</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Configuration par Plage de Niveaux -->
    <div id="config_plage" class="config-fields" style="display:none;">
        <div class="row mb-3">
            <label for="niveau_debut" class="col-sm-3 col-form-label">Niveau Début</label>
            <div class="col-sm-9">
                <select class="form-select" id="niveau_debut" name="niveau_debut"></select>
            </div>
        </div>
        <div class="row mb-3">
            <label for="niveau_fin" class="col-sm-3 col-form-label">Niveau Fin</label>
            <div class="col-sm-9">
                <select class="form-select" id="niveau_fin" name="niveau_fin"></select>
            </div>
        </div>
        <div class="row mb-3">
            <label for="serie" class="col-sm-3 col-form-label">Série (Optionnel)</label>
            <div class="col-sm-9">
                <select class="form-select" id="serie" name="serie">
                    <option value="">Toutes les séries</option>
                </select>
            </div>
        </div>
    </div>

    <hr>

    <!-- Champs communs -->
    <div class="row mb-3">
        <label for="frais_inscription" class="col-sm-3 col-form-label">Frais d'Inscription</label>
        <div class="col-sm-9">
            <input type="number" step="0.01" class="form-control" id="frais_inscription" name="frais_inscription" value="<?= $old_input['frais_inscription'] ?? '' ?>" required>
        </div>
    </div>

    <div class="row mb-3">
        <label for="frais_mensuel" class="col-sm-3 col-form-label">Mensualité</label>
        <div class="col-sm-9">
            <input type="number" step="0.01" class="form-control" id="frais_mensuel" name="frais_mensuel" value="<?= $old_input['frais_mensuel'] ?? '' ?>" required>
        </div>
    </div>

    <!-- Frais optionnels -->
    <hr>
    <div class="row mb-3">
        <label class="col-sm-3 col-form-label">Frais Optionnels</label>
        <div class="col-sm-9">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="autres_frais[logo_scolaire]" id="logo_scolaire" value="1">
                <label class="form-check-label" for="logo_scolaire">
                    Logo scolaire
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="autres_frais[carte_identite]" id="carte_identite" value="1">
                <label class="form-check-label" for="carte_identite">
                    Carte d’identité scolaire
                </label>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-sm-9 offset-sm-3">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="/frais" class="btn btn-light">Annuler</a>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeConfigSelect = document.getElementById('type_config');
    const configCycleDiv = document.getElementById('config_cycle');
    const configPlageDiv = document.getElementById('config_plage');

    const niveauDebutSelect = document.getElementById('niveau_debut');
    const niveauFinSelect = document.getElementById('niveau_fin');
    const serieSelect = document.getElementById('serie');

    let allNiveaux = [];

    function toggleFields() {
        const selectedType = typeConfigSelect.value;
        configCycleDiv.style.display = selectedType === 'cycle' ? 'block' : 'none';
        configPlageDiv.style.display = selectedType === 'plage' ? 'block' : 'none';
    }

    function populateSelect(selectElement, items) {
        selectElement.innerHTML = '<option value="">Sélectionnez...</option>';
        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item;
            option.textContent = item;
            selectElement.appendChild(option);
        });
    }

    function updateNiveauFinOptions() {
        const selectedNiveauDebut = niveauDebutSelect.value;
        const selectedIndex = allNiveaux.indexOf(selectedNiveauDebut);

        const filteredNiveaux = selectedIndex !== -1
            ? allNiveaux.slice(selectedIndex)
            : [];

        populateSelect(niveauFinSelect, filteredNiveaux);
    }

    async function fetchNiveauxAndSeries() {
        try {
            const [niveauxRes, seriesRes] = await Promise.all([
                fetch('/frais/get-niveaux'),
                fetch('/frais/get-series')
            ]);
            allNiveaux = await niveauxRes.json();
            const series = await seriesRes.json();

            populateSelect(niveauDebutSelect, allNiveaux);

            // Populate series with an initial "Toutes" option
            serieSelect.innerHTML = '<option value="">Toutes les séries</option>';
            series.forEach(item => {
                 const option = document.createElement('option');
                 option.value = item;
                 option.textContent = item;
                 serieSelect.appendChild(option);
            });

        } catch (error) {
            console.error('Erreur lors de la récupération des données:', error);
        }
    }

    typeConfigSelect.addEventListener('change', toggleFields);
    niveauDebutSelect.addEventListener('change', updateNiveauFinOptions);

    // Initial setup
    toggleFields();
    if (typeConfigSelect.value === 'plage') {
        fetchNiveauxAndSeries();
    }
     typeConfigSelect.addEventListener('change', function() {
        if (this.value === 'plage') {
            fetchNiveauxAndSeries();
        }
    });
});
</script>