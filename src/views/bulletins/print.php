<?php
require_once __DIR__ . '/../layouts/header_able.php';
require_once __DIR__ . '/../layouts/sidebar_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ Breadcrumb ] start -->
        <div class="page-header mb-3">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Impression Institutionnelle des Bulletins') ?></h2>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/dashboard"><?= _('Accueil') ?></a></li>
                            <li class="breadcrumb-item"><a href="/bulletins"><?= _('Bulletins') ?></a></li>
                            <li class="breadcrumb-item active"><?= _('Impression') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Breadcrumb ] end -->

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-warning-circle me-1"></i> <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Main Selection Form Card -->
        <div class="row">
            <div class="col-lg-7 col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-primary">
                            <i class="ph-duotone ph-printer me-2 fs-5"></i><?= _('Périmètre d\'Impression des Bulletins') ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="printScopeForm" method="GET" action="/bulletins/print/execute" target="_blank">

                            <!-- 1. Sequence Selection -->
                            <div class="mb-4">
                                <label for="sequence_id" class="form-label fw-bold"><?= _('Séquence Évaluative') ?> <span class="text-danger">*</span></label>
                                <select name="sequence_id" id="sequence_id" class="form-select" required>
                                    <option value=""><?= _('-- Sélectionner une séquence --') ?></option>
                                    <?php foreach ($sequences as $s): ?>
                                        <option value="<?= $s['id'] ?>">
                                            <?= htmlspecialchars($s['nom']) ?> (<?= _(ucfirst($s['statut'])) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- 2. Scope Type Selection -->
                            <div class="mb-4">
                                <label class="form-label fw-bold d-block"><?= _('Périmètre Général') ?> <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="scope_type" id="scope_cycle" value="cycle" checked>
                                    <label class="btn btn-outline-primary" for="scope_cycle">
                                        <i class="ph-duotone ph-tree-structure me-1"></i><?= _('Cycle') ?>
                                    </label>

                                    <input type="radio" class="btn-check" name="scope_type" id="scope_niveau" value="niveau">
                                    <label class="btn btn-outline-primary" for="scope_niveau">
                                        <i class="ph-duotone ph-chart-bar me-1"></i><?= _('Niveau') ?>
                                    </label>

                                    <input type="radio" class="btn-check" name="scope_type" id="scope_classe" value="classe">
                                    <label class="btn btn-outline-primary" for="scope_classe">
                                        <i class="ph-duotone ph-chalkboard me-1"></i><?= _('Classe') ?>
                                    </label>
                                </div>
                            </div>

                            <!-- 3. Dynamic Cascade Filters -->
                            <!-- Cycle Select -->
                            <div class="mb-3" id="group_cycle">
                                <label for="select_cycle" class="form-label fw-bold"><?= _('Cycle') ?> <span class="text-danger">*</span></label>
                                <select id="select_cycle" class="form-select">
                                    <option value=""><?= _('-- Sélectionner un cycle --') ?></option>
                                    <?php foreach ($cycles as $cy): ?>
                                        <option value="<?= $cy['id_cycle'] ?>"><?= htmlspecialchars($cy['nom_cycle']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Niveau Select -->
                            <div class="mb-3 d-none" id="group_niveau">
                                <label for="select_niveau" class="form-label fw-bold"><?= _('Niveau') ?> <span class="text-danger">*</span></label>
                                <select id="select_niveau" class="form-select">
                                    <option value=""><?= _('-- Sélectionner un niveau --') ?></option>
                                </select>
                            </div>

                            <!-- Classe Select -->
                            <div class="mb-4 d-none" id="group_classe">
                                <label for="select_classe" class="form-label fw-bold"><?= _('Classe') ?> <span class="text-danger">*</span></label>
                                <select id="select_classe" class="form-select">
                                    <option value=""><?= _('-- Sélectionner une classe --') ?></option>
                                </select>
                            </div>

                            <!-- Hidden Scope ID field submitted with form -->
                            <input type="hidden" name="scope_id" id="scope_id" value="">

                            <!-- Provisoire Checkbox Option -->
                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" name="include_provisoire" id="include_provisoire" value="1" checked>
                                <label class="form-check-label" for="include_provisoire">
                                    <?= _('Inclure les bulletins provisoires (avec filigrane d\'avertissement)') ?>
                                </label>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-2">
                                <button type="button" id="btnPreview" class="btn btn-outline-info d-inline-flex align-items-center gap-1">
                                    <i class="ph-duotone ph-eye"></i> <?= _('Prévisualiser le résumé') ?>
                                </button>
                                <button type="submit" id="btnPrintSubmit" class="btn btn-primary d-inline-flex align-items-center gap-1" disabled>
                                    <i class="ph-duotone ph-printer"></i> <?= _('Imprimer les bulletins') ?>
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            <!-- Summary Preview Panel -->
            <div class="col-lg-5 col-md-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light py-3">
                        <h5 class="mb-0 text-dark">
                            <i class="ph-duotone ph-info me-2 text-info fs-5"></i><?= _('Résumé Avant Impression') ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="summaryInitialState" class="text-center text-muted py-5">
                            <i class="ph-duotone ph-funnel fs-1 d-block mb-3 text-secondary"></i>
                            <p class="mb-0"><?= _('Sélectionnez une séquence et un périmètre, puis cliquez sur « Prévisualiser le résumé » pour analyser les bulletins imprimables.') ?></p>
                        </div>

                        <div id="summaryLoadingState" class="text-center text-primary py-5 d-none">
                            <div class="spinner-border mb-3" role="status"></div>
                            <p class="mb-0"><?= _('Analyse des bulletins en cours...') ?></p>
                        </div>

                        <div id="summaryResultsState" class="d-none">
                            <div class="list-group list-group-flush mb-4">
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span><i class="ph-duotone ph-buildings me-2 text-primary"></i><?= _('Classes concernées') ?></span>
                                    <span class="badge bg-light-primary text-primary fs-6" id="statClasses">0</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span><i class="ph-duotone ph-users me-2 text-secondary"></i><?= _('Élèves identifiés') ?></span>
                                    <span class="badge bg-light-secondary text-dark fs-6" id="statStudents">0</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span><i class="ph-duotone ph-check-circle me-2 text-success"></i><?= _('Bulletins officiels (Validés/Publiés)') ?></span>
                                    <span class="badge bg-success fs-6" id="statOfficial">0</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span><i class="ph-duotone ph-warning-circle me-2 text-warning"></i><?= _('Bulletins provisoires') ?></span>
                                    <span class="badge bg-warning text-dark fs-6" id="statProvisional">0</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span><i class="ph-duotone ph-prohibit me-2 text-danger"></i><?= _('Bloqués (Finance / Incomplets)') ?></span>
                                    <span class="badge bg-danger fs-6" id="statBlocked">0</span>
                                </div>
                            </div>

                            <div class="alert alert-info py-2 small mb-0">
                                <i class="ph-duotone ph-info me-1"></i> <?= _('L\'impression est une opération de consultation stricte et ne modifie aucun statut ni aucune moyenne.') ?>
                            </div>
                        </div>

                        <div id="summaryErrorState" class="alert alert-danger d-none mt-3">
                            <i class="ph-duotone ph-warning me-1"></i> <span id="summaryErrorMessage"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classesData = <?= json_encode($classes) ?>;

    const radioCycle = document.getElementById('scope_cycle');
    const radioNiveau = document.getElementById('scope_niveau');
    const radioClasse = document.getElementById('scope_classe');

    const groupCycle = document.getElementById('group_cycle');
    const groupNiveau = document.getElementById('group_niveau');
    const groupClasse = document.getElementById('group_classe');

    const selectCycle = document.getElementById('select_cycle');
    const selectNiveau = document.getElementById('select_niveau');
    const selectClasse = document.getElementById('select_classe');
    const scopeIdInput = document.getElementById('scope_id');

    const btnPreview = document.getElementById('btnPreview');
    const btnPrintSubmit = document.getElementById('btnPrintSubmit');

    const stateInitial = document.getElementById('summaryInitialState');
    const stateLoading = document.getElementById('summaryLoadingState');
    const stateResults = document.getElementById('summaryResultsState');
    const stateError = document.getElementById('summaryErrorState');
    const msgError = document.getElementById('summaryErrorMessage');

    function updateScopeVisibility() {
        const scope = document.querySelector('input[name="scope_type"]:checked').value;

        if (scope === 'cycle') {
            groupCycle.classList.remove('d-none');
            groupNiveau.classList.add('d-none');
            groupClasse.classList.add('d-none');
            scopeIdInput.value = selectCycle.value;
        } else if (scope === 'niveau') {
            groupCycle.classList.remove('d-none');
            groupNiveau.classList.remove('d-none');
            groupClasse.classList.add('d-none');
            if (selectNiveau.options.length <= 1) {
                populateNiveaux();
            }
            scopeIdInput.value = selectNiveau.value;
        } else if (scope === 'classe') {
            groupCycle.classList.remove('d-none');
            groupNiveau.classList.remove('d-none');
            groupClasse.classList.remove('d-none');
            if (selectNiveau.options.length <= 1) {
                populateNiveaux();
            }
            if (selectClasse.options.length <= 1) {
                populateClasses();
            }
            scopeIdInput.value = selectClasse.value;
        }
    }

    function populateNiveaux() {
        const currentSelected = selectNiveau.value;
        const cycleId = selectCycle.value;
        selectNiveau.innerHTML = '<option value=""><?= _("-- Sélectionner un niveau --") ?></option>';

        if (!cycleId) return;

        const filtered = classesData.filter(c => String(c.cycle_id) === String(cycleId));
        const niveauxMap = new Map();

        filtered.forEach(c => {
            if (!niveauxMap.has(c.niveau)) {
                niveauxMap.set(c.niveau, c.id_classe);
            }
        });

        let foundSelected = false;
        niveauxMap.forEach((sampleClassId, niv) => {
            const opt = document.createElement('option');
            opt.value = sampleClassId;
            opt.textContent = niv;
            if (String(sampleClassId) === String(currentSelected)) {
                opt.selected = true;
                foundSelected = true;
            }
            selectNiveau.appendChild(opt);
        });

        if (foundSelected) {
            selectNiveau.value = currentSelected;
        }
    }

    function populateClasses() {
        const currentSelected = selectClasse.value;
        const cycleId = selectCycle.value;
        const sampleClassId = selectNiveau.value;

        selectClasse.innerHTML = '<option value=""><?= _("-- Sélectionner une classe --") ?></option>';

        if (!cycleId || !sampleClassId) return;

        const sampleClass = classesData.find(c => String(c.id_classe) === String(sampleClassId));
        if (!sampleClass) return;

        const filtered = classesData.filter(c => String(c.cycle_id) === String(cycleId) && c.niveau === sampleClass.niveau);

        let foundSelected = false;
        filtered.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id_classe;
            const name = (c.niveau || '') + ' ' + (c.serie || '') + ' ' + (c.numero || '');
            opt.textContent = name.trim();
            if (String(c.id_classe) === String(currentSelected)) {
                opt.selected = true;
                foundSelected = true;
            }
            selectClasse.appendChild(opt);
        });

        if (foundSelected) {
            selectClasse.value = currentSelected;
        }
    }

    [radioCycle, radioNiveau, radioClasse].forEach(r => r.addEventListener('change', updateScopeVisibility));

    selectCycle.addEventListener('change', function() {
        selectNiveau.value = '';
        selectClasse.value = '';
        populateNiveaux();
        populateClasses();
        updateScopeVisibility();
    });

    selectNiveau.addEventListener('change', function() {
        selectClasse.value = '';
        populateClasses();
        updateScopeVisibility();
    });

    selectClasse.addEventListener('change', function() {
        updateScopeVisibility();
    });

    // Summary AJAX Handler
    btnPreview.addEventListener('click', function() {
        const sequenceId = document.getElementById('sequence_id').value;
        const scopeType = document.querySelector('input[name="scope_type"]:checked').value;
        const scopeId = scopeIdInput.value;

        if (!sequenceId) {
            alert('<?= _("Veuillez sélectionner une séquence.") ?>');
            return;
        }

        if (!scopeId) {
            alert('<?= _("Veuillez sélectionner le périmètre requis.") ?>');
            return;
        }

        stateInitial.classList.add('d-none');
        stateResults.classList.add('d-none');
        stateError.classList.add('d-none');
        stateLoading.classList.remove('d-none');

        fetch(`/bulletins/print/summary?sequence_id=${sequenceId}&scope_type=${scopeType}&scope_id=${scopeId}`)
            .then(res => res.json())
            .then(data => {
                stateLoading.classList.add('d-none');
                if (data.error) {
                    stateError.classList.remove('d-none');
                    msgError.textContent = data.error;
                    btnPrintSubmit.disabled = true;
                } else if (data.success && data.stats) {
                    stateResults.classList.remove('d-none');
                    document.getElementById('statClasses').textContent = data.stats.classes_count;
                    document.getElementById('statStudents').textContent = data.stats.total_students_count;
                    document.getElementById('statOfficial').textContent = data.stats.printable_official_count;
                    document.getElementById('statProvisional').textContent = data.stats.printable_provisional_count;
                    document.getElementById('statBlocked').textContent = data.stats.blocked_financial_count + data.stats.no_bulletin_count + data.stats.incomplete_count;

                    if (data.stats.printable_total > 0) {
                        btnPrintSubmit.disabled = false;
                    } else {
                        btnPrintSubmit.disabled = true;
                    }
                }
            })
            .catch(err => {
                stateLoading.classList.add('d-none');
                stateError.classList.remove('d-none');
                msgError.textContent = '<?= _("Erreur de communication lors de l\'analyse.") ?>';
                btnPrintSubmit.disabled = true;
            });
    });

    updateScopeVisibility();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
