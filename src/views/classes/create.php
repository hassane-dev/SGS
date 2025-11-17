<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Ajouter une nouvelle classe') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form action="/classes/store" method="POST">
                            <div class="row g-3">
                                <!-- Cycle -->
                                <div class="col-md-6">
                                    <label for="cycle_id" class="form-label"><?= _('Cycle') ?></label>
                                    <select name="cycle_id" id="cycle_id" class="form-select" required>
                                        <option value=""><?= _('-- Choisir un cycle --') ?></option>
                                        <?php foreach ($cycles as $cycle): ?>
                                            <option value="<?= $cycle['id_cycle'] ?>" data-nom-cycle="<?= htmlspecialchars($cycle['nom_cycle']) ?>"><?= htmlspecialchars($cycle['nom_cycle']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Niveau -->
                                <div class="col-md-6">
                                    <label for="niveau" class="form-label"><?= _('Niveau') ?></label>
                                    <select name="niveau" id="niveau" class="form-select" required disabled>
                                        <option value=""><?= _('-- Choisir un niveau --') ?></option>
                                    </select>
                                </div>

                                <!-- Serie -->
                                <div class="col-md-6">
                                    <label for="serie" class="form-label"><?= _('Série') ?></label>
                                    <input type="text" name="serie" id="serie" class="form-control" placeholder="<?= _('Ex: A4') ?>">
                                </div>

                                <!-- Categorie -->
                                <div class="col-md-6">
                                    <label for="categorie" class="form-label"><?= _('Catégorie') ?></label>
                                    <select name="categorie" id="categorie" class="form-select">
                                        <option value=""><?= _('-- Choisir une catégorie --') ?></option>
                                        <option value="Scientifique"><?= _('Scientifique') ?></option>
                                        <option value="Littéraire"><?= _('Littéraire') ?></option>
                                    </select>
                                </div>

                                <!-- Numero -->
                                <div class="col-md-6">
                                    <label for="numero" class="form-label"><?= _('Numéro') ?></label>
                                    <input type="number" name="numero" id="numero" class="form-control" placeholder="<?= _('Ex: 1') ?>">
                                </div>

                                <!-- Lycee -->
                                <?php if (Auth::can('view_all_lycees', 'lycee')): ?>
                                <div class="col-12">
                                    <label for="lycee_id" class="form-label"><?= _('Lycée') ?></label>
                                    <select name="lycee_id" id="lycee_id" class="form-select" required>
                                        <option value=""><?= _('-- Choisir un lycée --') ?></option>
                                        <?php foreach ($lycees as $lycee): ?>
                                            <option value="<?= $lycee['id_lycee'] ?>"><?= htmlspecialchars($lycee['nom_lycee']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="mt-4 d-flex justify-content-end">
                                <a href="/classes" class="btn btn-secondary me-2"><?= _('Annuler') ?></a>
                                <button type="submit" class="btn btn-primary">
                                    <?= _('Enregistrer') ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const cycleSelect = document.getElementById('cycle_id');
        const niveauSelect = document.getElementById('niveau');

        const niveauxParCycle = {
            'CEG': ['6e', '5e', '4e', '3e'],
            'Lycée': ['2nd', '1ère', 'Terminale']
        };

        cycleSelect.addEventListener('change', function () {
            // Get the selected cycle's name from the data attribute
            const selectedOption = this.options[this.selectedIndex];
            const nomCycle = selectedOption.getAttribute('data-nom-cycle');

            // Clear previous options
            niveauSelect.innerHTML = '<option value=""><?= _('-- Choisir un niveau --') ?></option>';

            if (nomCycle && niveauxParCycle[nomCycle]) {
                // Enable the niveau select
                niveauSelect.disabled = false;

                // Populate with new options
                niveauxParCycle[nomCycle].forEach(function (niveau) {
                    const option = document.createElement('option');
                    option.value = niveau;
                    option.textContent = niveau;
                    niveauSelect.appendChild(option);
                });
            } else {
                // If no cycle is selected, disable the niveau select
                niveauSelect.disabled = true;
            }
        });
    });
</script>
