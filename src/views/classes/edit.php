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
                            <h2 class="mb-0"><?= _('Modifier la Classe') ?></h2>
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
                        <form action="/classes/update" method="POST">
    <?= csrf_field() ?>
                            <input type="hidden" name="id_classe" value="<?= htmlspecialchars($classe['id_classe']) ?>">
                            <div class="row g-3">

                                <div class="col-12" id="unconfigured_cycle_alert" style="display: none;">
                                    <div class="alert alert-warning py-2 mb-0 d-flex align-items-center">
                                        <i class="ph-duotone ph-warning me-2 fs-5"></i>
                                        <span><strong><?= _('Configuration requise :') ?></strong> <?= _('Ce cycle n\'a pas encore été configuré concernant l\'utilisation des séries. Veuillez d\'abord définir cette option dans Administration > Cycles.') ?></span>
                                    </div>
                                </div>

                                <?php
                                $isOrphanedSerie = !empty($classe['serie']) && !in_array($classe['serie'], array_column($series, 'nom_serie'), true);
                                if ($isOrphanedSerie):
                                ?>
                                <div class="col-12" id="orphaned_serie_alert">
                                    <div class="alert alert-warning py-2 mb-0 d-flex align-items-center">
                                        <i class="ph-duotone ph-warning me-2 fs-5"></i>
                                        <span><strong><?= _('Attention :') ?></strong> <?= sprintf(_('La série \'%s\' enregistrée pour cette classe ne figure plus dans le référentiel des séries de l\'établissement.'), htmlspecialchars($classe['serie'])) ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Niveau -->
                                <div class="col-md-6">
                                    <label for="niveau" class="form-label"><?= _('Niveau') ?></label>
                                    <input type="text" name="niveau" id="niveau" class="form-control" value="<?= htmlspecialchars($classe['niveau']) ?>">
                                </div>

                                <!-- Serie -->
                                <div class="col-md-6" id="serie_container">
                                    <label for="serie" class="form-label"><?= _('Série') ?></label>
                                    <select name="serie" id="serie" class="form-select">
                                        <option value=""><?= _('-- Choisir une série --') ?></option>
                                        <?php if ($isOrphanedSerie): ?>
                                            <option value="<?= htmlspecialchars($classe['serie']) ?>" selected><?= htmlspecialchars($classe['serie']) ?> (<?= _('Non répertoriée') ?>)</option>
                                        <?php endif; ?>
                                        <?php foreach ($series as $serie): ?>
                                            <option value="<?= htmlspecialchars($serie['nom_serie']) ?>" data-categorie="<?= htmlspecialchars($serie['categorie']) ?>" <?= ($classe['serie'] ?? '') == $serie['nom_serie'] ? 'selected' : '' ?>><?= htmlspecialchars($serie['nom_serie']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Categorie -->
                                <div class="col-md-6" id="categorie_container">
                                    <label for="categorie" class="form-label"><?= _('Catégorie') ?></label>
                                    <select name="categorie" id="categorie" class="form-select">
                                        <option value=""><?= _('-- Choisir une catégorie --') ?></option>
                                        <option value="Scientifique" <?= ($classe['categorie'] ?? '') == 'Scientifique' ? 'selected' : '' ?>><?= _('Scientifique') ?></option>
                                        <option value="Littéraire" <?= ($classe['categorie'] ?? '') == 'Littéraire' ? 'selected' : '' ?>><?= _('Littéraire') ?></option>
                                    </select>
                                </div>

                                <!-- Numero -->
                                <div class="col-md-6">
                                    <label for="numero" class="form-label"><?= _('Numéro') ?></label>
                                    <input type="number" name="numero" id="numero" class="form-control" value="<?= htmlspecialchars($classe['numero']) ?>">
                                </div>

                                <!-- Cycle -->
                                <div class="col-md-6">
                                    <label for="cycle_id" class="form-label"><?= _('Cycle') ?></label>
                                    <select name="cycle_id" id="cycle_id" class="form-select" required>
                                        <?php foreach ($cycles as $cycle): ?>
                                            <option value="<?= $cycle['id_cycle'] ?>"
                                                    data-nom-cycle="<?= htmlspecialchars($cycle['nom_cycle']) ?>"
                                                    data-utilise-series="<?= ($cycle['utilise_series'] === null || $cycle['utilise_series'] === '') ? 'unconfigured' : (string)$cycle['utilise_series'] ?>"
                                                    <?= $classe['cycle_id'] == $cycle['id_cycle'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cycle['nom_cycle']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Lycee -->
                                <?php if (Auth::can('view_all_lycees', 'lycee')): ?>
                                <div class="col-md-6">
                                    <label for="lycee_id" class="form-label"><?= _('Lycée') ?></label>
                                    <select name="lycee_id" id="lycee_id" class="form-select" required>
                                        <?php foreach ($lycees as $lycee): ?>
                                             <option value="<?= $lycee['id'] ?>" <?= $classe['lycee_id'] == $lycee['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($lycee['nom_lycee']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php else: ?>
                                    <input type="hidden" name="lycee_id" value="<?= $classe['lycee_id'] ?>">
                                <?php endif; ?>
                            </div>

                            <div class="mt-4 d-flex justify-content-end">
                                <a href="/classes" class="btn btn-secondary me-2"><?= _('Annuler') ?></a>
                                <button type="submit" class="btn btn-primary">
                                    <?= _('Mettre à jour') ?>
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
        const serieSelect = document.getElementById('serie');
        const serieContainer = document.getElementById('serie_container');
        const categorieSelect = document.getElementById('categorie');
        const categorieContainer = document.getElementById('categorie_container');
        const unconfiguredAlert = document.getElementById('unconfigured_cycle_alert');

        function updateSerieAndCategorieVisibility(isUserAction) {
            const selectedOpt = cycleSelect && cycleSelect.selectedIndex >= 0 ? cycleSelect.options[cycleSelect.selectedIndex] : null;
            const utiliseSeriesAttr = selectedOpt ? selectedOpt.getAttribute('data-utilise-series') : '';

            if (unconfiguredAlert) {
                unconfiguredAlert.style.display = (utiliseSeriesAttr === 'unconfigured') ? 'block' : 'none';
            }

            if (utiliseSeriesAttr === '1') {
                serieContainer.style.display = 'block';
                categorieContainer.style.display = 'block';
            } else {
                serieContainer.style.display = 'none';
                categorieContainer.style.display = 'none';
                if (isUserAction) {
                    serieSelect.value = '';
                    categorieSelect.value = '';
                }
            }
        }

        if (cycleSelect) {
            cycleSelect.addEventListener('change', function () {
                updateSerieAndCategorieVisibility(true);
            });
        }

        if (serieSelect) {
            serieSelect.addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];
                const categorie = selectedOption ? selectedOption.getAttribute('data-categorie') : '';
                if (categorie) {
                    categorieSelect.value = categorie;
                }
            });
        }

        // Initialize state on load without wiping values
        updateSerieAndCategorieVisibility(false);
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
