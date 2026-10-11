<?php
/**
 * SGS Reusable Academic Filters Component
 *
 * Renders cascading dropdown filters:
 * Lycée -> Cycle -> Niveau -> Série (if applicable) -> Numéro -> Classe
 *
 * Expects $cascadeOptions array computed via AcademicFilterService::getCascadeOptions()
 * Configuration options via $config array:
 *   - show_lycee: bool (default: true if >1 lycee available)
 *   - show_cycle: bool (default: true)
 *   - show_niveau: bool (default: true)
 *   - show_serie: bool (default: true)
 *   - show_numero: bool (default: true)
 *   - show_classe: bool (default: false)
 *   - form_id: string (default: 'academic-filter-form')
 *   - submit_label: string|null (if null, auto-submits on change)
 */

$config = $config ?? [];
$selected = $selected ?? $_GET;
$cascadeOptions = $cascadeOptions ?? AcademicFilterService::getCascadeOptions($selected);

$showLycee = ($config['show_lycee'] ?? true) && (count($cascadeOptions['lycees']) > 1 || !empty($config['force_lycee']));
$showCycle = $config['show_cycle'] ?? true;
$showNiveau = $config['show_niveau'] ?? true;
$showSerie = $config['show_serie'] ?? true;
$showNumero = $config['show_numero'] ?? true;
$showClasse = $config['show_classe'] ?? false;
$autoSubmit = $config['auto_submit'] ?? true;
$formId = $config['form_id'] ?? 'academic-filter-form';
$submitLabel = $config['submit_label'] ?? null;

$selectedLyceeId = (string)($selected['lycee_id'] ?? '');
$selectedCycleId = (string)($selected['cycle_id'] ?? '');
$selectedNiveau = (string)($selected['niveau'] ?? '');
$selectedSerie = (string)($selected['serie'] ?? '');
$selectedNumero = (string)($selected['numero'] ?? '');
$selectedClasseId = (string)($selected['classe_id'] ?? '');
?>

<form id="<?= htmlspecialchars($formId) ?>" method="GET" class="academic-filters-wrapper">
    <div class="row g-2 align-items-center">

        <?php if ($showLycee): ?>
            <div class="col-md-3 col-sm-6 filter-group-lycee">
                <label for="filter_lycee_id" class="form-label text-muted small fw-bold mb-1"><?= _("Établissement / Lycée") ?></label>
                <select name="lycee_id" id="filter_lycee_id" class="form-select form-select-sm" <?= $autoSubmit ? 'onchange="this.form.submit()"' : '' ?>>
                    <option value=""><?= _("-- Tous les établissements --") ?></option>
                    <?php foreach ($cascadeOptions['lycees'] as $l): ?>
                        <option value="<?= $l['id'] ?>" <?= $selectedLyceeId === (string)$l['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($l['nom_lycee']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php else: ?>
            <?php if (!empty($cascadeOptions['lycees'][0]['id'])): ?>
                <input type="hidden" name="lycee_id" id="filter_lycee_id" value="<?= $cascadeOptions['lycees'][0]['id'] ?>">
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($showCycle): ?>
            <div class="col-md-2 col-sm-6 filter-group-cycle">
                <label for="filter_cycle_id" class="form-label text-muted small fw-bold mb-1"><?= _("Cycle") ?></label>
                <select name="cycle_id" id="filter_cycle_id" class="form-select form-select-sm" <?= $autoSubmit ? 'onchange="this.form.submit()"' : '' ?>>
                    <option value=""><?= _("-- Tous les cycles --") ?></option>
                    <?php foreach ($cascadeOptions['cycles'] as $c): ?>
                        <option value="<?= $c['id_cycle'] ?>" <?= $selectedCycleId === (string)$c['id_cycle'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nom_cycle']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <?php if ($showNiveau): ?>
            <div class="col-md-2 col-sm-6 filter-group-niveau">
                <label for="filter_niveau" class="form-label text-muted small fw-bold mb-1"><?= _("Niveau") ?></label>
                <select name="niveau" id="filter_niveau" class="form-select form-select-sm" <?= $autoSubmit ? 'onchange="this.form.submit()"' : '' ?>>
                    <option value=""><?= _("-- Tous les niveaux --") ?></option>
                    <?php foreach ($cascadeOptions['niveaux'] as $n): ?>
                        <option value="<?= htmlspecialchars($n) ?>" <?= $selectedNiveau === (string)$n ? 'selected' : '' ?>>
                            <?= htmlspecialchars($n) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <?php if ($showSerie): ?>
            <div class="col-md-2 col-sm-6 filter-group-serie" id="group_filter_serie" style="display: <?= $cascadeOptions['has_series'] ? 'block' : 'none' ?>;">
                <label for="filter_serie" class="form-label text-muted small fw-bold mb-1"><?= _("Série") ?></label>
                <select name="serie" id="filter_serie" class="form-select form-select-sm" <?= $autoSubmit ? 'onchange="this.form.submit()"' : '' ?>>
                    <option value=""><?= _("-- Toutes les séries --") ?></option>
                    <?php foreach ($cascadeOptions['series'] as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= $selectedSerie === (string)$s ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <?php if ($showNumero): ?>
            <div class="col-md-2 col-sm-6 filter-group-numero">
                <label for="filter_numero" class="form-label text-muted small fw-bold mb-1"><?= _("Numéro") ?></label>
                <select name="numero" id="filter_numero" class="form-select form-select-sm" <?= $autoSubmit ? 'onchange="this.form.submit()"' : '' ?>>
                    <option value=""><?= _("-- Tous numéros --") ?></option>
                    <?php foreach ($cascadeOptions['numeros'] as $num): ?>
                        <option value="<?= $num ?>" <?= $selectedNumero === (string)$num ? 'selected' : '' ?>>
                            <?= $num ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <?php if ($showClasse): ?>
            <div class="col-md-3 col-sm-6 filter-group-classe">
                <label for="filter_classe_id" class="form-label text-muted small fw-bold mb-1"><?= _("Classe") ?></label>
                <select name="classe_id" id="filter_classe_id" class="form-select form-select-sm" <?= $autoSubmit ? 'onchange="this.form.submit()"' : '' ?>>
                    <option value=""><?= _("-- Toutes les classes --") ?></option>
                    <?php foreach ($cascadeOptions['classes'] as $cls): ?>
                        <option value="<?= $cls['id_classe'] ?>" <?= $selectedClasseId === (string)$cls['id_classe'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cls['niveau'] . ' ' . ($cls['serie'] ? $cls['serie'] . ' ' : '') . $cls['numero']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <?php if ($submitLabel): ?>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary mt-3">
                    <i class="ph-duotone ph-funnel me-1"></i><?= htmlspecialchars($submitLabel) ?>
                </button>
            </div>
        <?php endif; ?>

    </div>
</form>
