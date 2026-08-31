<?php include __DIR__ . '/../layouts/header_able.php';
$isEdit = isset($param) && !empty($param);
$paramType = $isEdit ? ($param['type'] ?? 'global') : 'global';
$typeEval = $isEdit ? ($param['type_evaluation'] ?? 'tous') : 'tous';
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
                if ($_GET['error'] === 'missing_classe') echo _('Veuillez sélectionner une classe.');
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
                        <form action="<?= $isEdit ? '/evaluations/settings/update' : '/evaluations/settings/store' ?>" method="POST">
                            <?php if ($isEdit): ?>
                                <input type="hidden" name="id" value="<?= htmlspecialchars($param['id']) ?>">
                            <?php endif; ?>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="type"><?= _('Niveau de ciblage') ?></label>
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
                                    <label class="form-label"><?= _('Nature de l\'évaluation') ?></label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="type_evaluation" id="eval_tous" value="tous" <?= $typeEval === 'tous' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="eval_tous">Tous (Devoirs & Compositions)</label>
                                        </div>
                                        <div class="form-check ms-3">
                                            <input class="form-check-input" type="radio" name="type_evaluation" id="eval_devoir" value="devoir" <?= $typeEval === 'devoir' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="eval_devoir">Devoirs uniquement</label>
                                        </div>
                                        <div class="form-check ms-3">
                                            <input class="form-check-input" type="radio" name="type_evaluation" id="eval_composition" value="composition" <?= $typeEval === 'composition' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="eval_composition">Compositions uniquement</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cascade Selection Container -->
                            <div class="card mb-3 bg-light border-0 d-none" id="field-cascade">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3 text-primary"><i class="ph-duotone ph-tree-structure me-1"></i><?= _("Sélection Pédagogique (Cascade SGS)") ?></h6>
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label for="cascade_cycle_id" class="form-label fw-bold"><?= _('Cycle') ?></label>
                                            <select id="cascade_cycle_id" class="form-select">
                                                <option value=""><?= _('-- Choisir Cycle --') ?></option>
                                                <?php if (!empty($cycles)): ?>
                                                    <?php foreach ($cycles as $cyc): ?>
                                                        <option value="<?= $cyc['id_cycle'] ?>"><?= htmlspecialchars($cyc['nom_cycle']) ?></option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="cascade_niveau" class="form-label fw-bold"><?= _('Niveau') ?></label>
                                            <select id="cascade_niveau" class="form-select">
                                                <option value=""><?= _('-- Choisir d\'abord le cycle --') ?></option>
                                            </select>
                                        </div>
                                        <div class="col-md-3" id="group_serie" style="display: none;">
                                            <label for="cascade_serie" class="form-label fw-bold"><?= _('Série') ?></label>
                                            <select id="cascade_serie" class="form-select">
                                                <option value=""><?= _('-- Toutes les séries --') ?></option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="cascade_numero" class="form-label fw-bold"><?= _('Numéro / Classe') ?></label>
                                            <select id="cascade_numero" class="form-select">
                                                <option value=""><?= _('-- Choisir d\'abord le niveau --') ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <input type="hidden" id="classe_id" name="classe_id" value="<?= ($isEdit && !empty($param['classe_id'])) ? htmlspecialchars($param['classe_id']) : '' ?>">
                                </div>
                            </div>

                            <div class="row mb-3 d-none" id="field-matiere">
                                <div class="col-12">
                                    <label class="form-label" for="matiere_id"><?= _('Sélectionner la matière') ?></label>
                                    <select class="form-select" id="matiere_id" name="matiere_id">
                                        <option value=""><?= _('Choisir une matière...') ?></option>
                                        <?php foreach ($matieres as $m): ?>
                                            <option value="<?= $m['id_matiere'] ?>" <?= ($isEdit && isset($param['matiere_id']) && $param['matiere_id'] == $m['id_matiere']) ? 'selected' : '' ?>><?= htmlspecialchars($m['nom_matiere']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

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

    const cascadeEl = document.getElementById('field-cascade');
    const matiereEl = document.getElementById('field-matiere');
    const enseignantEl = document.getElementById('field-enseignant');

    if (cascadeEl) cascadeEl.classList.add('d-none');
    if (matiereEl) matiereEl.classList.add('d-none');
    if (enseignantEl) enseignantEl.classList.add('d-none');

    if (type === 'classe') {
        if (cascadeEl) cascadeEl.classList.remove('d-none');
    } else if (type === 'matiere') {
        if (matiereEl) matiereEl.classList.remove('d-none');
    } else if (type === 'classe_matiere') {
        if (cascadeEl) cascadeEl.classList.remove('d-none');
        if (matiereEl) matiereEl.classList.remove('d-none');
    } else if (type === 'enseignant') {
        if (cascadeEl) cascadeEl.classList.remove('d-none');
        if (matiereEl) matiereEl.classList.remove('d-none');
        if (enseignantEl) enseignantEl.classList.remove('d-none');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    toggleFields();

    <?php
    $initCycle = $selectedClasseDetails['cycle_id'] ?? '';
    $initNiveau = $selectedClasseDetails['niveau'] ?? '';
    $initSerie = $selectedClasseDetails['serie'] ?? '';
    $initNumero = $selectedClasseDetails['numero'] ?? '';
    $initMatiere = $param['matiere_id'] ?? '';
    $initTeacher = $param['enseignant_id'] ?? '';
    ?>

    new SGSPedagogicalCascade({
        cycleId: 'cascade_cycle_id',
        niveauId: 'cascade_niveau',
        groupSerieId: 'group_serie',
        serieId: 'cascade_serie',
        numeroId: 'cascade_numero',
        classeId: 'classe_id',
        matiereId: 'matiere_id',
        teacherId: 'enseignant_id',
        includeAllSubjects: true,
        initialValues: {
            cycleId: '<?= htmlspecialchars($initCycle) ?>',
            niveau: '<?= htmlspecialchars($initNiveau) ?>',
            serie: '<?= htmlspecialchars($initSerie) ?>',
            numero: '<?= htmlspecialchars($initNumero) ?>',
            matiereId: '<?= htmlspecialchars($initMatiere) ?>',
            teacherId: '<?= htmlspecialchars($initTeacher) ?>'
        }
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer_able.php'; ?>
