<?php
require_once __DIR__ . '/../layouts/header_able.php';
require_once __DIR__ . '/../layouts/sidebar_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Gestion de Validation des Bulletins') ?></h2>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/bulletins"><?= _('Bulletins') ?></a></li>
                            <li class="breadcrumb-item"><?= _('Validation Institutionnelle') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <!-- Périmètre selection card -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ph-duotone ph-check-square me-2 text-primary"></i><?= _('Sélection du Périmètre de Validation') ?></h5>
                    </div>
                    <div class="card-body">
                        <form id="scopeForm">
                            <div class="row g-3">
                                <!-- Sequence selection -->
                                <div class="col-md-4">
                                    <label for="sequence_id" class="form-label fw-bold"><?= _('Période / Séquence') ?></label>
                                    <select name="sequence_id" id="sequence_id" class="form-select" required>
                                        <option value=""><?= _('-- Sélectionner la séquence --') ?></option>
                                        <?php foreach ($sequences as $seq): ?>
                                            <option value="<?= $seq['id'] ?>" data-statut="<?= htmlspecialchars($seq['statut']) ?>">
                                                <?= htmlspecialchars($seq['nom']) ?> (<?= ucfirst(htmlspecialchars($seq['statut'])) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Scope level selection -->
                                <div class="col-md-4">
                                    <label for="scope_type" class="form-label fw-bold"><?= _('Niveau de Périmètre') ?></label>
                                    <select name="scope_type" id="scope_type" class="form-select" required onchange="onScopeTypeChange()">
                                        <option value=""><?= _('-- Sélectionner le périmètre --') ?></option>
                                        <option value="cycle"><?= _('Niveau 1 — Cycle complet') ?></option>
                                        <option value="niveau"><?= _('Niveau 2 — Niveau académique') ?></option>
                                        <option value="classe"><?= _('Niveau 3 — Classe spécifique') ?></option>
                                    </select>
                                </div>

                                <!-- Target scope value selection -->
                                <div class="col-md-4" id="scope_value_container">
                                    <label for="scope_value" class="form-label fw-bold"><?= _('Cible du Périmètre') ?></label>
                                    <select name="scope_value" id="scope_value" class="form-select" required disabled>
                                        <option value=""><?= _('-- Choisir d\'abord un périmètre --') ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" id="btnAnalyze" onclick="analyzeScope()">
                                    <i class="ph-duotone ph-magnifying-glass"></i> <?= _('Analyser le Périmètre') ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Pre-validation Analysis Card -->
            <div class="col-md-12 d-none" id="analysisCard">
                <div class="card border-primary">
                    <div class="card-header bg-light-primary d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-primary"><i class="ph-duotone ph-chart-donut me-2"></i><?= _('Résumé Pré-Validation') ?> : <span id="resScopeLabel" class="fw-bold"></span></h5>
                        <span id="resSequenceBadge" class="badge"></span>
                    </div>
                    <div class="card-body">
                        <!-- Alert for Open Sequence -->
                        <div class="alert alert-warning d-none d-flex align-items-center gap-2" id="openSequenceAlert">
                            <i class="ph-duotone ph-warning-circle h4 mb-0"></i>
                            <div>
                                <strong><?= _('Séquence Non Clôturée !') ?></strong>
                                <p class="mb-0 small"><?= _('Cette séquence est toujours ouverte. Les notes sont modifiables et le snapshot scellé n\'a pas été généré. Vous devez clôturer la séquence avant de pouvoir valider officiellement les bulletins.') ?></p>
                            </div>
                        </div>

                        <!-- Metrics Grid -->
                        <div class="row text-center g-3 my-2">
                            <div class="col-md-2 col-6">
                                <div class="p-3 border rounded bg-light">
                                    <span class="text-muted small d-block"><?= _('Classes') ?></span>
                                    <span class="h3 fw-bold mb-0 text-dark" id="valClassesCount">0</span>
                                </div>
                            </div>
                            <div class="col-md-2 col-6">
                                <div class="p-3 border rounded bg-light">
                                    <span class="text-muted small d-block"><?= _('Élèves Total') ?></span>
                                    <span class="h3 fw-bold mb-0 text-dark" id="valStudentsCount">0</span>
                                </div>
                            </div>
                            <div class="col-md-2 col-6">
                                <div class="p-3 border rounded bg-light-warning">
                                    <span class="text-warning small d-block"><?= _('Provisoires à Valider') ?></span>
                                    <span class="h3 fw-bold mb-0 text-warning" id="valProvisoireCount">0</span>
                                </div>
                            </div>
                            <div class="col-md-2 col-6">
                                <div class="p-3 border rounded bg-light-success">
                                    <span class="text-success small d-block"><?= _('Déjà Validés') ?></span>
                                    <span class="h3 fw-bold mb-0 text-success" id="valValideCount">0</span>
                                </div>
                            </div>
                            <div class="col-md-2 col-6">
                                <div class="p-3 border rounded bg-light-info">
                                    <span class="text-info small d-block"><?= _('Publiés') ?></span>
                                    <span class="h3 fw-bold mb-0 text-info" id="valPublieCount">0</span>
                                </div>
                            </div>
                            <div class="col-md-2 col-6">
                                <div class="p-3 border rounded bg-light-danger" id="boxBloque">
                                    <span class="text-danger small d-block"><?= _('Bloqués / Incomplets') ?></span>
                                    <span class="h3 fw-bold mb-0 text-danger" id="valBloqueCount">0</span>
                                </div>
                            </div>
                        </div>

                        <!-- Blocked Details Table -->
                        <div class="mt-4 d-none" id="blockedContainer">
                            <div class="alert alert-danger d-flex align-items-center gap-2 mb-2">
                                <i class="ph-duotone ph-prohibit h4 mb-0"></i>
                                <div>
                                    <strong><?= _('Bulletins Incomplets Bloquants !') ?></strong>
                                    <p class="mb-0 small"><?= _('La validation globale est bloquée car les élèves ci-dessous possèdent des résultats incomplets ou aucune évaluation saisie. Aucune conversion automatique en 0.00 n\'est effectuée.') ?></p>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-bordered mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th><?= _('Élève') ?></th>
                                            <th><?= _('Matricule') ?></th>
                                            <th><?= _('Classe') ?></th>
                                            <th><?= _('Raison du blocage') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="blockedTableBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Action Bar -->
                        <div class="mt-4 d-flex justify-content-between align-items-center pt-3 border-top">
                            <span class="text-muted small" id="valActionInfo"></span>
                            <button type="button" class="btn btn-success btn-lg d-inline-flex align-items-center gap-2" id="btnConfirmExecute" onclick="confirmExecuteValidation()" disabled>
                                <i class="ph-duotone ph-check-circle h4 mb-0"></i> <?= _('Valider les Bulletins') ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<script>
const rawCycles = <?= json_encode($cycles) ?>;
const rawNiveaux = <?= json_encode($niveaux) ?>;
const rawClasses = <?= json_encode($classes) ?>;

let currentSummaryData = null;

function onScopeTypeChange() {
    const type = document.getElementById('scope_type').value;
    const selectValue = document.getElementById('scope_value');
    selectValue.innerHTML = '';
    selectValue.disabled = false;

    if (type === 'cycle') {
        selectValue.innerHTML = '<option value=""><?= _("-- Sélectionner un cycle --") ?></option>';
        rawCycles.forEach(c => {
            selectValue.innerHTML += `<option value="${c.id_cycle}">${c.nom_cycle}</option>`;
        });
    } else if (type === 'niveau') {
        selectValue.innerHTML = '<option value=""><?= _("-- Sélectionner un niveau --") ?></option>';
        rawNiveaux.forEach(n => {
            selectValue.innerHTML += `<option value="${n}">${n}</option>`;
        });
    } else if (type === 'classe') {
        selectValue.innerHTML = '<option value=""><?= _("-- Sélectionner une classe --") ?></option>';
        rawClasses.forEach(c => {
            const nom = (c.niveau || '') + ' ' + (c.serie || '') + ' ' + (c.numero || '');
            selectValue.innerHTML += `<option value="${c.id_classe}">${nom.trim()}</option>`;
        });
    } else {
        selectValue.innerHTML = '<option value=""><?= _("-- Choisir d\'abord un périmètre --") ?></option>';
        selectValue.disabled = true;
    }
}

function analyzeScope() {
    const sequenceId = document.getElementById('sequence_id').value;
    const scopeType = document.getElementById('scope_type').value;
    const scopeValue = document.getElementById('scope_value').value;

    if (!sequenceId || !scopeType || !scopeValue) {
        alert('<?= _("Veuillez sélectionner la séquence, le niveau de périmètre et la cible.") ?>');
        return;
    }

    const btn = document.getElementById('btnAnalyze');
    btn.disabled = true;
    btn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i><?= _("Analyse en cours...") ?>';

    const formData = new FormData();
    formData.append('sequence_id', sequenceId);
    formData.append('scope_type', scopeType);
    formData.append('scope_value', scopeValue);

    fetch('/bulletins/validation/summary', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="ph-duotone ph-magnifying-glass me-2"></i><?= _("Analyser le Périmètre") ?>';

        if (!data.success) {
            alert(data.message || 'Error');
            return;
        }

        currentSummaryData = data;
        renderSummary(data);
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="ph-duotone ph-magnifying-glass me-2"></i><?= _("Analyser le Périmètre") ?>';
        alert('Erreur serveur : ' + err.message);
    });
}

function renderSummary(d) {
    document.getElementById('analysisCard').classList.remove('d-none');
    document.getElementById('resScopeLabel').innerText = d.scope_label;

    const seqBadge = document.getElementById('resSequenceBadge');
    const openAlert = document.getElementById('openSequenceAlert');
    const btnExecute = document.getElementById('btnConfirmExecute');

    if (d.is_sequence_closed) {
        seqBadge.className = 'badge bg-success';
        seqBadge.innerText = 'Séquence Clôturée (' + d.sequence.nom + ')';
        openAlert.classList.add('d-none');
    } else {
        seqBadge.className = 'badge bg-warning text-dark';
        seqBadge.innerText = 'Séquence Ouverte (' + d.sequence.nom + ')';
        openAlert.classList.remove('d-none');
    }

    document.getElementById('valClassesCount').innerText = d.classes_count;
    document.getElementById('valStudentsCount').innerText = d.students_count;
    document.getElementById('valProvisoireCount').innerText = d.provisoire_count;
    document.getElementById('valValideCount').innerText = d.valide_count;
    document.getElementById('valPublieCount').innerText = d.publie_count;
    document.getElementById('valBloqueCount').innerText = d.bloque_count;

    const blockedContainer = document.getElementById('blockedContainer');
    const tbody = document.getElementById('blockedTableBody');
    tbody.innerHTML = '';

    if (d.bloque_count > 0) {
        blockedContainer.classList.remove('d-none');
        d.bloque_details.forEach(b => {
            tbody.innerHTML += `
                <tr>
                    <td class="fw-bold">${b.nom_complet}</td>
                    <td><code>${b.matricule}</code></td>
                    <td>${b.classe}</td>
                    <td class="text-danger fw-bold">${b.raison}</td>
                </tr>
            `;
        });
    } else {
        blockedContainer.classList.add('d-none');
    }

    const actionInfo = document.getElementById('valActionInfo');

    if (!d.is_sequence_closed) {
        btnExecute.disabled = true;
        actionInfo.innerHTML = '<span class="text-warning fw-bold"><i class="ph-duotone ph-warning-circle me-1"></i><?= _("Validation impossible : Séquence non clôturée.") ?></span>';
    } else if (d.bloque_count > 0) {
        btnExecute.disabled = true;
        actionInfo.innerHTML = '<span class="text-danger fw-bold"><i class="ph-duotone ph-prohibit me-1"></i><?= _("Validation impossible : Certains bulletins sont incomplets.") ?></span>';
    } else if (d.provisoire_count === 0) {
        btnExecute.disabled = true;
        actionInfo.innerHTML = '<span class="text-muted fw-bold"><i class="ph-duotone ph-info me-1"></i><?= _("Aucun bulletin provisoire à valider.") ?></span>';
    } else {
        btnExecute.disabled = false;
        actionInfo.innerHTML = '<span class="text-success fw-bold"><i class="ph-duotone ph-check me-1"></i><?= _("Périmètre valide. Prêt pour confirmation.") ?></span>';
    }
}

function confirmExecuteValidation() {
    if (!currentSummaryData || !currentSummaryData.is_sequence_closed || currentSummaryData.bloque_count > 0 || currentSummaryData.provisoire_count === 0) {
        return;
    }

    const confirmMsg = `Confirmez-vous la validation institutionnelle de ${currentSummaryData.provisoire_count} bulletin(s) provisoire(s) pour le périmètre "${currentSummaryData.scope_label}" ?\n\nCette action est transactionnelle et enregistrera votre signature de validation.`;

    if (!confirm(confirmMsg)) {
        return;
    }

    const btn = document.getElementById('btnConfirmExecute');
    btn.disabled = true;
    btn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i><?= _("Validation en cours...") ?>';

    const formData = new FormData();
    formData.append('sequence_id', currentSummaryData.sequence.id);
    formData.append('scope_type', currentSummaryData.scope_type);
    formData.append('scope_value', currentSummaryData.scope_value);

    fetch('/bulletins/validation/execute', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="ph-duotone ph-check-circle h4 mb-0 me-2"></i><?= _("Valider les Bulletins") ?>';

        if (!data.success) {
            alert(data.message || 'Error');
            return;
        }

        alert(data.message);
        analyzeScope(); // Refresh analysis
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="ph-duotone ph-check-circle h4 mb-0 me-2"></i><?= _("Valider les Bulletins") ?>';
        alert('Erreur lors de la validation : ' + err.message);
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
