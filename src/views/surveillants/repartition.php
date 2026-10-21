<?php
/**
 * Vue : Assistant de Répartition Automatique des Surveillants
 */
require_once __DIR__ . '/../layouts/header_able.php';
require_once __DIR__ . '/../layouts/sidebar_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- Breadcrumb & Header -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Assistant de Répartition Automatique des Classes") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/dashboard"><?= _("Accueil") ?></a></li>
                            <li class="breadcrumb-item"><a href="/surveillants"><?= _("Surveillants") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Répartition Automatique") ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Formulaire de Configuration -->
            <div class="col-lg-5 col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ph-duotone ph-sliders me-2 text-primary"></i>1. <?= _("Sélection & Paramètres") ?></h5>
                    </div>
                    <div class="card-body">
                        <form id="formProposeDistribution" onsubmit="handlePropose(event);">
                            <?= csrf_field() ?>

                            <div class="mb-3">
                                <label class="form-label fw-bold"><?= _("Mode de Répartition") ?> *</label>
                                <select name="mode" id="sel_mode" class="form-select" onchange="toggleModeParams();">
                                    <option value="equitable"><?= _("Équitable (Nombre égal de classes par surveillant)") ?></option>
                                    <option value="plage_consecutive"><?= _("Plage consécutive (N classes consécutives)") ?></option>
                                    <option value="niveau"><?= _("Par niveau académique (Regroupement par niveau)") ?></option>
                                </select>
                            </div>

                            <div class="mb-3 d-none" id="grp_max_per">
                                <label class="form-label fw-bold"><?= _("Nombre de classes max par surveillant") ?></label>
                                <input type="number" name="max_per_surveillant" class="form-control" value="3" min="1" max="20">
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-bold mb-0"><?= _("Classes à distribuer") ?> *</label>
                                    <button type="button" class="btn btn-sm btn-link p-0" onclick="selectAll('classe_ids[]', true);"><?= _("Tout cocher") ?></button>
                                </div>
                                <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($classes as $c): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="classe_ids[]" value="<?= $c['id_classe'] ?>" id="chk_c_<?= $c['id_classe'] ?>" checked>
                                            <label class="form-check-label" for="chk_c_<?= $c['id_classe'] ?>">
                                                <?= htmlspecialchars($c['niveau'] . ' ' . ($c['serie'] ?? '') . ' ' . ($c['numero'] ?? '')) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-bold mb-0"><?= _("Surveillants disponibles") ?> *</label>
                                    <button type="button" class="btn btn-sm btn-link p-0" onclick="selectAll('surveillant_ids[]', true);"><?= _("Tout cocher") ?></button>
                                </div>
                                <div class="border rounded p-2" style="max-height: 150px; overflow-y: auto;">
                                    <?php foreach ($surveillants as $s): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="surveillant_ids[]" value="<?= $s['id_user'] ?>" id="chk_s_<?= $s['id_user'] ?>" checked>
                                            <label class="form-check-label" for="chk_s_<?= $s['id_user'] ?>">
                                                <?= htmlspecialchars($s['prenom'] . ' ' . $s['nom']) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100" id="btnPropose">
                                <i class="ph-duotone ph-sparkle me-1"></i><?= _("Générer la Proposition") ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Aperçu de la Proposition DRAFT -->
            <div class="col-lg-7 col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="ph-duotone ph-eye me-2 text-primary"></i>2. <?= _("Aperçu de la Proposition (Ajustable)") ?></h5>
                        <span class="badge bg-secondary" id="badgeDraftStatus"><?= _("En attente de génération") ?></span>
                    </div>
                    <div class="card-body" id="containerProposal">
                        <div class="text-center py-5 text-muted">
                            <i class="ph-duotone ph-arrows-merge fs-1 d-block mb-3"></i>
                            <p><?= _("Sélectionnez les classes et surveillants puis cliquez sur 'Générer la Proposition' pour obtenir une distribution optimale.") ?></p>
                        </div>
                    </div>
                    <div class="card-footer bg-light d-none" id="footerProposalActions">
                        <form id="formApplyDistribution" onsubmit="handleApply(event);">
                            <?= csrf_field() ?>
                            <input type="hidden" name="payload_json" id="inputPayloadJson">

                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <label class="form-label small fw-bold mb-0 me-2"><?= _("Mode d'application :") ?></label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="apply_mode" id="radReplace" value="replace" checked>
                                        <label class="form-check-label small" for="radReplace"><?= _("Remplacer les affectations actives") ?></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="apply_mode" id="radAppend" value="append">
                                        <label class="form-check-label small" for="radAppend"><?= _("Ajouter en complément") ?></label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success" id="btnApply">
                                    <i class="ph-duotone ph-check-circle me-1"></i><?= _("Valider la Répartition Definite") ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentProposal = null;

function toggleModeParams() {
    const val = document.getElementById('sel_mode').value;
    const grp = document.getElementById('grp_max_per');
    if (val === 'plage_consecutive') {
        grp.classList.remove('d-none');
    } else {
        grp.classList.add('d-none');
    }
}

function selectAll(name, checked) {
    document.querySelectorAll(`input[name="${name}"]`).forEach(el => el.checked = checked);
}

function handlePropose(e) {
    e.preventDefault();
    const btn = document.getElementById('btnPropose');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Calcul en cours...';

    const formData = new FormData(document.getElementById('formProposeDistribution'));

    fetch('/surveillants/propose', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="ph-duotone ph-sparkle me-1"></i> Générer la Proposition';

        if (!data.success) {
            alert(data.error || 'Erreur lors du calcul de la proposition.');
            return;
        }

        currentProposal = data.proposal;
        renderProposalPreview(currentProposal);
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="ph-duotone ph-sparkle me-1"></i> Générer la Proposition';
        alert('Erreur serveur lors de la proposition.');
    });
}

function renderProposalPreview(prop) {
    document.getElementById('badgeDraftStatus').className = 'badge bg-warning text-dark';
    document.getElementById('badgeDraftStatus').innerText = 'Proposition générée (Brouillon non enregistré)';

    const container = document.getElementById('containerProposal');
    let html = '';

    if (prop.distribution.length === 0) {
        html = '<div class="alert alert-warning">Aucune distribution générée.</div>';
    } else {
        html += '<div class="row g-3">';
        prop.distribution.forEach(item => {
            const s = item.surveillant;
            html += `
                <div class="col-md-6">
                    <div class="card border mb-0">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0 fw-bold text-primary">
                                <i class="ph-duotone ph-user me-1"></i>${s.prenom} ${s.nom}
                            </h6>
                        </div>
                        <div class="card-body p-2">
                            <div class="d-flex flex-wrap gap-1" id="classes_surv_${s.id_user}">
            `;
            if (item.classes.length === 0) {
                html += '<span class="text-muted small">Aucune classe affectée</span>';
            } else {
                item.classes.forEach(c => {
                    html += `
                        <span class="badge bg-light-primary text-primary p-2">
                            ${c.niveau} ${c.serie || ''} ${c.numero || ''}
                        </span>
                    `;
                });
            }
            html += `
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';

        if (prop.unassigned_classes && prop.unassigned_classes.length > 0) {
            html += '<div class="alert alert-warning mt-3 mb-0 small"><strong>Classes non affectées :</strong> ';
            prop.unassigned_classes.forEach(c => {
                html += `<span class="badge bg-warning text-dark me-1">${c.niveau} ${c.serie || ''} ${c.numero || ''}</span>`;
            });
            html += '</div>';
        }
    }

    container.innerHTML = html;
    document.getElementById('footerProposalActions').classList.remove('d-none');
}

function handleApply(e) {
    e.preventDefault();
    if (!currentProposal) return;

    const applyMode = document.querySelector('input[name="apply_mode"]:checked').value;

    // Build submission distribution payload
    const distPayload = currentProposal.distribution.map(item => {
        return {
            surveillant_id: item.surveillant.id_user,
            classe_ids: item.classes.map(c => c.id_classe)
        };
    });

    const fullPayload = {
        state_hash: currentProposal.state_hash,
        lycee_id: currentProposal.lycee_id,
        annee_academique_id: currentProposal.annee_academique_id,
        apply_mode: applyMode,
        selected_classe_ids: currentProposal.selected_classe_ids,
        distribution: distPayload
    };

    document.getElementById('inputPayloadJson').value = JSON.stringify(fullPayload);

    const btn = document.getElementById('btnApply');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Validation...';

    const formData = new FormData();
    formData.append('csrf_token', '<?= CsrfService::token() ?>');
    formData.append('payload_json', JSON.stringify(fullPayload));

    fetch('/surveillants/apply', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="ph-duotone ph-check-circle me-1"></i> Valider la Répartition Définitive';

        if (data.success) {
            window.location.href = data.redirect || '/surveillants';
        } else {
            alert(data.error || 'Erreur lors de la validation.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="ph-duotone ph-check-circle me-1"></i> Valider la Répartition Définitive';
        alert('Erreur serveur lors de la validation.');
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
