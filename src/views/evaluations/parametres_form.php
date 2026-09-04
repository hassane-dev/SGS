<?php include __DIR__ . '/../layouts/header_able.php';
$isEdit = isset($param) && !empty($param);
$paramType = $isEdit ? ($param['type'] ?? 'global') : 'global';
$selectedEvalId = $isEdit ? ($param['type_evaluation_id'] ?? null) : null;
$selectedEvalCode = $isEdit ? ($param['type_evaluation'] ?? 'tous') : 'tous';
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _('Tableau de bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/evaluations/settings"><?= _('Paramètres de Saisie') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= $isEdit ? _('Modifier la Période') : _('Nouveaux Paramètres') ?></li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= $isEdit ? _('Modifier la Période de Saisie') : _('Définir une Période de Saisie') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="ph-duotone ph-warning-circle me-2 fs-5 align-middle"></i>
                <?php
                if ($_GET['error'] === 'missing_classe') echo _('Veuillez sélectionner une classe via la cascade.');
                elseif ($_GET['error'] === 'missing_matiere') echo _('Veuillez sélectionner une matière.');
                elseif ($_GET['error'] === 'missing_classe_or_matiere') echo _('Veuillez sélectionner la classe et la matière.');
                elseif ($_GET['error'] === 'missing_enseignant') echo _('Veuillez sélectionner l\'enseignant.');
                elseif ($_GET['error'] === 'invalid_dates') echo _('La date de fermeture doit être postérieure à la date d\'ouverture.');
                else echo _('Une erreur est survenue lors de l\'enregistrement des paramètres.');
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h5><?= $isEdit ? _('Modification de la période #') . htmlspecialchars($param['id']) : _('Configuration de la période') ?></h5>
                    </div>
                    <div class="card-body">
                        <form id="settings-form" action="<?= $isEdit ? '/evaluations/settings/update' : '/evaluations/settings/store' ?>" method="POST" onsubmit="prepareSubmission()">
                            <?php if ($isEdit): ?>
                                <input type="hidden" name="id" value="<?= htmlspecialchars($param['id']) ?>">
                            <?php endif; ?>

                            <!-- Hidden field for resolved classe_id from cascade -->
                            <input type="hidden" name="classe_id" id="classe_id" value="<?= htmlspecialchars($param['classe_id'] ?? '') ?>">

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="type"><?= _('Niveau de ciblage (Portée)') ?></label>
                                    <select class="form-select" id="type" name="type" required onchange="toggleFields()">
                                        <option value="global" <?= $paramType === 'global' ? 'selected' : '' ?>><?= _('Global (Tout l\'établissement)') ?></option>
                                        <option value="classe" <?= $paramType === 'classe' ? 'selected' : '' ?>><?= _('Par classe') ?></option>
                                        <option value="matiere" <?= $paramType === 'matiere' ? 'selected' : '' ?>><?= _('Par matière') ?></option>
                                        <option value="classe_matiere" <?= $paramType === 'classe_matiere' ? 'selected' : '' ?>><?= _('Par classe + matière') ?></option>
                                        <option value="enseignant" <?= $paramType === 'enseignant' ? 'selected' : '' ?>><?= _('Par enseignant (Spécifique)') ?></option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="sequence_id"><?= _('Séquence concernée') ?></label>
                                    <select class="form-select" id="sequence_id" name="sequence_id">
                                        <option value=""><?= _('Toutes les séquences') ?></option>
                                        <?php foreach ($sequences as $s): ?>
                                            <option value="<?= $s['id'] ?>" <?= ($isEdit && isset($param['sequence_id']) && $param['sequence_id'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label"><?= _('Nature de l\'évaluation autorisée') ?></label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="type_evaluation_id" id="eval_tous" value="tous" <?= (empty($selectedEvalId) && $selectedEvalCode === 'tous') ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold text-dark" for="eval_tous">
                                                <i class="ph-duotone ph-circles-four me-1 text-primary"></i><?= _('Tous les types d\'évaluations') ?>
                                            </label>
                                        </div>
                                        <?php
                                        $activeTypesList = $types_evaluation ?? ParamTypeEvaluation::findActive();
                                        foreach ($activeTypesList as $tItem):
                                            $isChecked = false;
                                            if (!empty($selectedEvalId) && (int)$selectedEvalId === (int)$tItem['id']) {
                                                $isChecked = true;
                                            } elseif (empty($selectedEvalId) && $selectedEvalCode === $tItem['code']) {
                                                $isChecked = true;
                                            }
                                        ?>
                                            <div class="form-check ms-2">
                                                <input class="form-check-input" type="radio" name="type_evaluation_id" id="eval_type_<?= $tItem['id'] ?>" value="<?= $tItem['id'] ?>" <?= $isChecked ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="eval_type_<?= $tItem['id'] ?>">
                                                    <?= htmlspecialchars($tItem['libelle']) ?> <span class="text-muted small">(/<?= number_format((float)($tItem['bareme_defaut'] ?? 20), 0) ?>)</span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- SGS Pedagogical Cascade Container (Cycle -> Niveau -> Série -> Numéro -> Classe) -->
                            <div id="cascade-container" class="card bg-light p-3 mb-3 border d-none">
                                <h6 class="text-primary mb-3"><i class="ph-duotone ph-funnel me-2"></i><?= _('Sélection de la Classe via la Cascade Pédagogique') ?></h6>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold" for="cycle_id"><?= _('Cycle') ?></label>
                                        <select class="form-select form-select-sm" id="cycle_id">
                                            <option value=""><?= _('-- Choisir un cycle --') ?></option>
                                            <?php foreach ($cycles as $cy): ?>
                                                <option value="<?= $cy['id_cycle'] ?>"><?= htmlspecialchars($cy['nom_cycle']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold" for="niveau"><?= _('Niveau') ?></label>
                                        <select class="form-select form-select-sm" id="niveau" disabled>
                                            <option value=""><?= _('-- Choisir d\'abord un cycle --') ?></option>
                                        </select>
                                    </div>
                                    <div class="col-md-3" id="group-serie" style="display: none;">
                                        <label class="form-label small fw-bold" for="serie"><?= _('Série') ?></label>
                                        <select class="form-select form-select-sm" id="serie" disabled>
                                            <option value=""><?= _('-- Toutes les séries --') ?></option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold" for="numero"><?= _('Numéro') ?></label>
                                        <select class="form-select form-select-sm" id="numero" disabled>
                                            <option value=""><?= _('-- Tous les numéros --') ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Direct Matière Select (Used when scope type === 'matiere') -->
                            <div class="row mb-3 d-none" id="field-matiere-direct">
                                <div class="col-12">
                                    <label class="form-label" for="matiere_id_direct"><?= _('Sélectionner la matière (Etablissement)') ?></label>
                                    <select class="form-select" id="matiere_id_direct">
                                        <option value=""><?= _('Choisir une matière...') ?></option>
                                        <?php foreach ($matieres as $m): ?>
                                            <option value="<?= $m['id_matiere'] ?>" <?= ($isEdit && isset($param['matiere_id']) && $param['matiere_id'] == $m['id_matiere']) ? 'selected' : '' ?>><?= htmlspecialchars($m['nom_matiere']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Cascade Matière Select (Used when scope type === 'classe_matiere' or 'enseignant') -->
                            <div class="row mb-3 d-none" id="field-matiere-cascade">
                                <div class="col-12">
                                    <label class="form-label" for="matiere_id"><?= _('Sélectionner la matière de la classe') ?></label>
                                    <select class="form-select" id="matiere_id" name="matiere_id" disabled>
                                        <option value=""><?= _('-- Sélectionner d\'abord la classe dans la cascade --') ?></option>
                                        <?php if ($isEdit && !empty($param['matiere_id'])): ?>
                                            <?php foreach ($matieres as $m): ?>
                                                <option value="<?= $m['id_matiere'] ?>" <?= $param['matiere_id'] == $m['id_matiere'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nom_matiere']) ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Enseignant Select (Used when scope type === 'enseignant') -->
                            <div class="row mb-3 d-none" id="field-enseignant">
                                <div class="col-12">
                                    <label class="form-label" for="enseignant_id"><?= _('Sélectionner l\'enseignant') ?></label>
                                    <select class="form-select" id="enseignant_id" name="enseignant_id">
                                        <option value=""><?= _('Choisir un enseignant...') ?></option>
                                        <?php foreach ($enseignants as $e): ?>
                                            <option value="<?= $e['id_user'] ?>" <?= ($isEdit && isset($param['enseignant_id']) && $param['enseignant_id'] == $e['id_user']) ? 'selected' : '' ?>><?= htmlspecialchars($e['full_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="date_ouverture"><?= _('Date d\'ouverture') ?></label>
                                    <input type="datetime-local" class="form-control" id="date_ouverture" name="date_ouverture" required value="<?= $isEdit ? date('Y-m-d\TH:i', strtotime($param['date_ouverture_saisie'])) : date('Y-m-d\TH:i') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="date_fermeture"><?= _('Date de fermeture') ?></label>
                                    <input type="datetime-local" class="form-control" id="date_fermeture" name="date_fermeture" required value="<?= $isEdit ? date('Y-m-d\TH:i', strtotime($param['date_fermeture_saisie'])) : date('Y-m-d\TH:i', strtotime('+7 days')) ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="commentaire"><?= _('Commentaire / Instruction') ?></label>
                                <textarea class="form-control" id="commentaire" name="commentaire" rows="3" placeholder="<?= _('Ex: Période de saisie normale pour le premier semestre') ?>"><?= $isEdit ? htmlspecialchars($param['commentaire'] ?? '') : '' ?></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="/evaluations/settings" class="btn btn-link text-muted"><?= _('Annuler') ?></a>
                                <button type="submit" class="btn btn-primary d-inline-flex align-items-center">
                                    <i class="ph-duotone <?= $isEdit ? 'ph-floppy-disk' : 'ph-plus-circle' ?> me-2"></i>
                                    <?= $isEdit ? _('Mettre à jour les paramètres') : _('Enregistrer les paramètres') ?>
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

<script src="/assets/js/sgs-pedagogical-cascade.js"></script>

<script>
function toggleFields() {
    const type = document.getElementById('type').value;

    const cascadeContainer = document.getElementById('cascade-container');
    const fieldMatiereDirect = document.getElementById('field-matiere-direct');
    const fieldMatiereCascade = document.getElementById('field-matiere-cascade');
    const fieldEnseignant = document.getElementById('field-enseignant');

    cascadeContainer.classList.add('d-none');
    fieldMatiereDirect.classList.add('d-none');
    fieldMatiereCascade.classList.add('d-none');
    fieldEnseignant.classList.add('d-none');

    if (type === 'classe') {
        cascadeContainer.classList.remove('d-none');
    } else if (type === 'matiere') {
        fieldMatiereDirect.classList.remove('d-none');
    } else if (type === 'classe_matiere') {
        cascadeContainer.classList.remove('d-none');
        fieldMatiereCascade.classList.remove('d-none');
    } else if (type === 'enseignant') {
        cascadeContainer.classList.remove('d-none');
        fieldMatiereCascade.classList.remove('d-none');
        fieldEnseignant.classList.remove('d-none');
    }
}

function prepareSubmission() {
    const type = document.getElementById('type').value;
    const matiereCascade = document.getElementById('matiere_id');
    const matiereDirect = document.getElementById('matiere_id_direct');

    if (type === 'matiere' && matiereDirect && matiereCascade) {
        matiereCascade.value = matiereDirect.value;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    toggleFields();

    const initialValues = <?= json_encode($initialValues ?? []) ?>;

    new SGSPedagogicalCascade({
        cycleId: 'cycle_id',
        niveauId: 'niveau',
        groupSerieId: 'group-serie',
        serieId: 'serie',
        numeroId: 'numero',
        classeId: 'classe_id',
        matiereId: 'matiere_id',
        teacherId: 'enseignant_id',
        initialValues: initialValues,
        includeAllSubjects: true
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer_able.php'; ?>
