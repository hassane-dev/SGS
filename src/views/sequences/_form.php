<?php if (!empty($error)): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label class="form-label">Type de séquence <span class="text-danger">*</span></label>
            <?php
                $selected_type = isset($sequence['type']) ? $sequence['type'] : ($sequence_type ?? 'trimestrielle');
                $display_type = ($selected_type === 'semestrielle') ? 'Semestre' : 'Trimestre';
            ?>
            <input type="text" class="form-control" value="<?= htmlspecialchars($display_type) ?>" readonly disabled>
            <input type="hidden" id="type" name="type" value="<?= htmlspecialchars($selected_type) ?>">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="nom" class="form-label">Nom de la séquence <span class="text-danger">*</span></label>
            <select class="form-select" id="nom" name="nom" required data-selected-nom="<?= htmlspecialchars($sequence['nom'] ?? '') ?>">
                <option value="" selected disabled>-- Choisir une séquence --</option>
                <!-- Options populated by JavaScript -->
            </select>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <div class="form-group">
            <label for="date_debut" class="form-label">Date de début <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?= htmlspecialchars($sequence['date_debut'] ?? '') ?>" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="date_fin" class="form-label">Date de fin <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?= htmlspecialchars($sequence['date_fin'] ?? '') ?>" required>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <div class="form-group">
            <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
            <select class="form-select" id="statut" name="statut" required>
                <option value="ouverte" <?= (isset($sequence['statut']) && $sequence['statut'] === 'ouverte') ? 'selected' : '' ?>>
                    Ouverte
                </option>
                <option value="fermee" <?= (isset($sequence['statut']) && $sequence['statut'] === 'fermee') ? 'selected' : '' ?>>
                    Fermée
                </option>
            </select>
        </div>
    </div>
</div>

<button type="submit" class="btn btn-primary mt-4">Enregistrer</button>
<a href="/sequences" class="btn btn-light mt-4">Annuler</a>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const nomSelect = document.getElementById('nom');

    const sequenceNames = {
        semestrielle: ['Semestre 1', 'Semestre 2'],
        trimestrielle: ['Trimestre 1', 'Trimestre 2', 'Trimestre 3']
    };

    function populateNomSelect() {
        const selectedType = typeSelect.value;
        const previouslySelectedNom = nomSelect.getAttribute('data-selected-nom');

        // Clear current options
        nomSelect.innerHTML = '<option value="" selected disabled>-- Choisir une séquence --</option>';

        if (selectedType && sequenceNames[selectedType]) {
            sequenceNames[selectedType].forEach(name => {
                const option = document.createElement('option');
                option.value = name;
                option.textContent = name;
                if (name === previouslySelectedNom) {
                    option.selected = true;
                }
                nomSelect.appendChild(option);
            });
        }
    }

    typeSelect.addEventListener('change', populateNomSelect);

    // Initial population on page load for edit forms
    if (typeSelect.value) {
        populateNomSelect();
    }
});
</script>
