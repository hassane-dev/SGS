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
                                <!-- Lycee selection (Super Admin) or Hidden field (Local Admin) -->
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
                                <?php else: ?>
                                <input type="hidden" name="lycee_id" id="lycee_id" value="<?= (int)Auth::getLyceeId() ?>">
                                <?php endif; ?>

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

                                <div class="col-12" id="missing_lycee_alert" style="display: none;">
                                    <div class="alert alert-info py-2 mb-0 d-flex align-items-center">
                                        <i class="ph-duotone ph-info me-2 fs-5"></i>
                                        <span><strong><?= _('Sélection requise :') ?></strong> <?= _('Veuillez d\'abord sélectionner un établissement pour charger les niveaux du cycle.') ?></span>
                                    </div>
                                </div>

                                <div class="col-12" id="unconfigured_cycle_alert" style="display: none;">
                                    <div class="alert alert-warning py-2 mb-0 d-flex align-items-center">
                                        <i class="ph-duotone ph-warning me-2 fs-5"></i>
                                        <span><strong><?= _('Configuration requise :') ?></strong> <?= _('Ce cycle n\'a pas encore été configuré concernant l\'utilisation des séries. Veuillez d\'abord définir cette option dans Administration > Cycles.') ?></span>
                                    </div>
                                </div>

                                <div class="col-12" id="no_niveaux_cycle_alert" style="display: none;">
                                    <div class="alert alert-warning py-2 mb-0 d-flex align-items-center">
                                        <i class="ph-duotone ph-warning me-2 fs-5"></i>
                                        <span><strong><?= _('Attention :') ?></strong> <?= _('Aucun niveau n\'est configuré pour ce cycle. Veuillez vérifier les bornes (niveau début / niveau fin) dans Administration > Cycles.') ?></span>
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
        const lyceeIdEl = document.getElementById('lycee_id');

        let currentNiveauxRequestId = 0;
        let currentSeriesRequestId = 0;

        function resetNiveauxAndAlerts() {
            niveauSelect.innerHTML = '<option value=""><?= _('-- Choisir un niveau --') ?></option>';
            niveauSelect.disabled = true;
            serieContainer.style.display = 'none';
            categorieContainer.style.display = 'none';
            serieSelect.value = '';
            categorieSelect.value = '';

            const unconfiguredAlert = document.getElementById('unconfigured_cycle_alert');
            const noNiveauxAlert = document.getElementById('no_niveaux_cycle_alert');
            const missingLyceeAlert = document.getElementById('missing_lycee_alert');

            if (unconfiguredAlert) unconfiguredAlert.style.display = 'none';
            if (noNiveauxAlert) noNiveauxAlert.style.display = 'none';
            if (missingLyceeAlert) missingLyceeAlert.style.display = 'none';
        }

        function loadNiveauxForCycle() {
            resetNiveauxAndAlerts();

            const cycleId = cycleSelect ? cycleSelect.value : '';
            if (!cycleId) return;

            const selectedOpt = cycleSelect.options[cycleSelect.selectedIndex];
            const utiliseSeriesAttr = selectedOpt ? selectedOpt.getAttribute('data-utilise-series') : '';

            const unconfiguredAlert = document.getElementById('unconfigured_cycle_alert');
            const noNiveauxAlert = document.getElementById('no_niveaux_cycle_alert');
            const missingLyceeAlert = document.getElementById('missing_lycee_alert');

            if (utiliseSeriesAttr === 'unconfigured') {
                if (unconfiguredAlert) unconfiguredAlert.style.display = 'block';
                return;
            }

            const lid = lyceeIdEl ? lyceeIdEl.value : '';
            if (lyceeIdEl && !lid) {
                if (missingLyceeAlert) missingLyceeAlert.style.display = 'block';
                return;
            }

            const requestId = ++currentNiveauxRequestId;

            fetch(`/classes/get-niveaux?cycle_id=${cycleId}&lycee_id=${lid}`)
                .then(async r => {
                    const data = await r.json().catch(() => null);
                    if (!r.ok) {
                        const errMsg = (data && data.error) ? data.error : ('<?= _("Erreur lors de la récupération des niveaux") ?> (' + r.status + ')');
                        throw new Error(errMsg);
                    }
                    return data;
                })
                .then(niveaux => {
                    if (requestId !== currentNiveauxRequestId) return;

                    if (Array.isArray(niveaux) && niveaux.length > 0) {
                        niveauSelect.disabled = false;
                        niveaux.forEach(function (niveau) {
                            const option = document.createElement('option');
                            option.value = niveau;
                            option.textContent = niveau;
                            niveauSelect.appendChild(option);
                        });
                    } else {
                        if (noNiveauxAlert) {
                            const textSpan = noNiveauxAlert.querySelector('span');
                            if (textSpan) {
                                textSpan.innerHTML = '<strong>' + '<?= _("Attention :") ?>' + '</strong> ' + '<?= _("Aucun niveau n\'a pu être généré pour ce cycle.") ?>';
                            }
                            noNiveauxAlert.style.display = 'block';
                        }
                    }
                })
                .catch(err => {
                    if (requestId !== currentNiveauxRequestId) return;
                    console.error('Error fetching levels:', err);
                    if (noNiveauxAlert) {
                        const textSpan = noNiveauxAlert.querySelector('span');
                        if (textSpan) {
                            textSpan.innerHTML = '<strong>' + '<?= _("Attention :") ?>' + '</strong> ' + (err.message || '<?= _("Erreur lors de la récupération des niveaux.") ?>');
                        }
                        noNiveauxAlert.style.display = 'block';
                    }
                });
        }

        if (cycleSelect) {
            cycleSelect.addEventListener('change', loadNiveauxForCycle);
        }

        if (lyceeIdEl) {
            lyceeIdEl.addEventListener('change', loadNiveauxForCycle);
        }

        niveauSelect.addEventListener('change', function () {
            const niveau = this.value;
            const cycleId = cycleSelect.value;
            const lid = lyceeIdEl ? lyceeIdEl.value : '';

            if (!niveau || !cycleId) {
                serieContainer.style.display = 'none';
                categorieContainer.style.display = 'none';
                return;
            }

            const requestId = ++currentSeriesRequestId;

            fetch(`/classes/get-series?niveau=${encodeURIComponent(niveau)}&cycle_id=${cycleId}&lycee_id=${lid}`)
                .then(r => {
                    if (!r.ok) throw new Error('HTTP Error ' + r.status);
                    return r.json();
                })
                .then(series => {
                    if (requestId !== currentSeriesRequestId) return;

                    if (Array.isArray(series) && series.length > 0) {
                        serieContainer.style.display = 'block';
                        categorieContainer.style.display = 'block';
                    } else {
                        serieContainer.style.display = 'none';
                        categorieContainer.style.display = 'none';
                        serieSelect.value = '';
                        categorieSelect.value = '';
                    }
                })
                .catch(err => {
                    if (requestId !== currentSeriesRequestId) return;
                    console.error('Error fetching series:', err);
                    serieContainer.style.display = 'none';
                    categorieContainer.style.display = 'none';
                });
        });

        serieSelect.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const categorie = selectedOption ? selectedOption.getAttribute('data-categorie') : null;
            if (categorie) {
                categorieSelect.value = categorie;
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
