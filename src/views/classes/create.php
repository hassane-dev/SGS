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
    <?= csrf_field() ?>
                            <div class="row g-3">
                                <!-- Cycle -->
                                <div class="col-md-6">
                                    <label for="cycle_id" class="form-label"><?= _('Cycle') ?></label>
                                    <select name="cycle_id" id="cycle_id" class="form-select" required>
                                        <option value=""><?= _('-- Choisir un cycle --') ?></option>
                                        <?php foreach ($cycles as $cycle): ?>
                                            <option value="<?= $cycle['id_cycle'] ?>" data-nom-cycle="<?= htmlspecialchars($cycle['nom_cycle']) ?>" data-utilise-series="<?= ($cycle['utilise_series'] === null || $cycle['utilise_series'] === '') ? 'unconfigured' : (string)$cycle['utilise_series'] ?>"><?= htmlspecialchars($cycle['nom_cycle']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12" id="unconfigured_cycle_alert" style="display: none;">
                                    <div class="alert alert-warning py-2 mb-0 d-flex align-items-center">
                                        <i class="ph-duotone ph-warning me-2 fs-5"></i>
                                        <span><strong><?= _('Configuration requise :') ?></strong> <?= _('Ce cycle n\'a pas encore été configuré concernant l\'utilisation des séries. Veuillez d\'abord définir cette option dans Administration > Cycles.') ?></span>
                                    </div>
                                </div>

                                <!-- Niveau -->
                                <div class="col-md-6">
                                    <label for="niveau" class="form-label"><?= _('Niveau') ?></label>
                                    <select name="niveau" id="niveau" class="form-select" required disabled>
                                        <option value=""><?= _('-- Choisir un niveau --') ?></option>
                                    </select>
                                </div>

                                <!-- Serie -->
                                <div class="col-md-6" id="serie_container" style="display: none;">
                                    <label for="serie" class="form-label"><?= _('Série') ?></label>
                                    <select name="serie" id="serie" class="form-select">
                                        <option value=""><?= _('-- Choisir une série --') ?></option>
                                        <?php foreach ($series as $serie): ?>
                                            <option value="<?= htmlspecialchars($serie['nom_serie']) ?>" data-categorie="<?= htmlspecialchars($serie['categorie']) ?>"><?= htmlspecialchars($serie['nom_serie']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Categorie -->
                                <div class="col-md-6" id="categorie_container" style="display: none;">
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
                                            <option value="<?= $lycee['id'] ?>"><?= htmlspecialchars($lycee['nom_lycee']) ?></option>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const cycleSelect = document.getElementById('cycle_id');
        const niveauSelect = document.getElementById('niveau');
        const serieSelect = document.getElementById('serie');
        const serieContainer = document.getElementById('serie_container');
        const categorieSelect = document.getElementById('categorie');
        const categorieContainer = document.getElementById('categorie_container');

        const niveauxParCycle = {
            'CEG': ['6e', '5e', '4e', '3e'],
            'Lycée': ['2nd', '1ère', 'Terminale']
        };

        cycleSelect.addEventListener('change', function () {
            const cycleId = this.value;
            const selectedOpt = this.options[this.selectedIndex];
            const utiliseSeriesAttr = selectedOpt ? selectedOpt.getAttribute('data-utilise-series') : '';
            const unconfiguredAlert = document.getElementById('unconfigured_cycle_alert');

            // Clear previous options
            niveauSelect.innerHTML = '<option value=""><?= _('-- Choisir un niveau --') ?></option>';
            niveauSelect.disabled = true;
            serieContainer.style.display = 'none';
            categorieContainer.style.display = 'none';
            serieSelect.value = '';
            categorieSelect.value = '';

            if (unconfiguredAlert) {
                unconfiguredAlert.style.display = (utiliseSeriesAttr === 'unconfigured') ? 'block' : 'none';
            }

            if (!cycleId || utiliseSeriesAttr === 'unconfigured') return;

            const lyceeIdEl = document.getElementById('lycee_id');
            const lid = lyceeIdEl ? lyceeIdEl.value : '';

            fetch(`/classes/get-niveaux?cycle_id=${cycleId}&lycee_id=${lid}`)
                .then(r => r.json())
                .then(niveaux => {
                    if (niveaux && niveaux.length > 0) {
                        niveauSelect.disabled = false;
                        niveaux.forEach(function (niveau) {
                            const option = document.createElement('option');
                            option.value = niveau;
                            option.textContent = niveau;
                            niveauSelect.appendChild(option);
                        });
                    }
                });
        });

        niveauSelect.addEventListener('change', function () {
            const niveau = this.value;
            const cycleId = cycleSelect.value;
            const lyceeIdEl = document.getElementById('lycee_id');
            const lid = lyceeIdEl ? lyceeIdEl.value : '';

            if (!niveau || !cycleId) {
                serieContainer.style.display = 'none';
                categorieContainer.style.display = 'none';
                return;
            }

            fetch(`/classes/get-series?niveau=${encodeURIComponent(niveau)}&cycle_id=${cycleId}&lycee_id=${lid}`)
                .then(r => r.json())
                .then(series => {
                    if (series && series.length > 0) {
                        serieContainer.style.display = 'block';
                        categorieContainer.style.display = 'block';
                    } else {
                        serieContainer.style.display = 'none';
                        categorieContainer.style.display = 'none';
                        serieSelect.value = '';
                        categorieSelect.value = '';
                    }
                });
        });

        serieSelect.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const categorie = selectedOption.getAttribute('data-categorie');
            if (categorie) {
                categorieSelect.value = categorie;
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
