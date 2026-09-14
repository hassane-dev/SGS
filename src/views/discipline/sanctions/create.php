<?php
$title = _("Prononcer une Sanction Disciplinaire");
require_once __DIR__ . '/../../layouts/header_able.php';
require_once __DIR__ . '/../../layouts/sidebar_able.php';
?>

<div class="pc-container">
    <div class="pc-content">

        <!-- Breadcrumb -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Prononcer une Sanction") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _("Accueil") ?></a></li>
                            <li class="breadcrumb-item"><a href="/discipline/sanctions"><?= _("Discipline") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Nouvelle Sanction") ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash error -->
        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-warning-circle me-2 fs-5"></i>
                <?= htmlspecialchars($_SESSION['flash_error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <form action="/discipline/sanctions/store" method="POST" id="formSanctionCreate">
            <?php if (!empty($incident)): ?>
                <input type="hidden" name="incident_id" value="<?= $incident['id'] ?>">
            <?php endif; ?>

            <div class="row">

                <!-- Context Card -->
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="ph-duotone ph-user me-2 text-primary"></i><?= _("1. Élève & Contexte") ?></h5>
                        </div>
                        <div class="card-body">

                            <?php if (!empty($incident)): ?>
                                <div class="p-3 bg-light-primary rounded mb-3">
                                    <div class="fw-bold text-primary mb-1">
                                        <i class="ph-duotone ph-link me-1"></i><?= _("Sanction rattachée à l'incident #") ?><?= $incident['id'] ?>
                                    </div>
                                    <div class="small text-muted">
                                        <strong><?= _("Type :") ?></strong> <?= htmlspecialchars($incident['type_libelle']) ?> |
                                        <strong><?= _("Date :") ?></strong> <?= date('d/m/Y', strtotime($incident['date_incident'])) ?>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        <?= htmlspecialchars(mb_strimwidth($incident['description_faits'], 0, 120, '...')) ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Selection of student -->
                            <?php if (!empty($incidentEleves)): ?>
                                <div class="mb-3">
                                    <label for="eleve_id" class="form-label required-field"><?= _("Sélectionner l'élève impliqué") ?></label>
                                    <select name="eleve_id" id="eleve_id" class="form-select" required>
                                        <option value=""><?= _("-- Choisir un élève impliqué dans cet incident --") ?></option>
                                        <?php foreach ($incidentEleves as $e): ?>
                                            <option value="<?= $e['eleve_id'] ?>">
                                                <?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?> (<?= htmlspecialchars($e['nom_niveau']) ?> - Rôle: <?= htmlspecialchars($e['role_implication']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <label for="selectClasseFilter" class="form-label required-field"><?= _("Classe de l'élève") ?></label>
                                    <select id="selectClasseFilter" class="form-select" onchange="loadElevesByClasse()" required>
                                        <option value=""><?= _("-- Filtrer par classe --") ?></option>
                                        <?php foreach ($classes as $cls): ?>
                                            <option value="<?= $cls['id_classe'] ?>"><?= htmlspecialchars($cls['niveau'] . ($cls['serie'] ? ' ' . $cls['serie'] : '') . ($cls['numero'] ? ' ' . $cls['numero'] : '')) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="eleve_id" class="form-label required-field"><?= _("Élève à sanctionner") ?></label>
                                    <select name="eleve_id" id="eleve_id" class="form-select" disabled required>
                                        <option value=""><?= _("-- Sélectionner d'abord une classe --") ?></option>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="date_decision" class="form-label required-field"><?= _("Date de Décision") ?></label>
                                <input type="date" name="date_decision" id="date_decision" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                <div class="form-text text-muted"><?= _("Détermine la période administrative de rattachement de la sanction.") ?></div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Sanction Details Card -->
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="ph-duotone ph-gavel me-2 text-primary"></i><?= _("2. Décision & Modalités") ?></h5>
                        </div>
                        <div class="card-body">

                            <div class="mb-3">
                                <label for="type_sanction_id" class="form-label required-field"><?= _("Type de Sanction") ?></label>
                                <select name="type_sanction_id" id="type_sanction_id" class="form-select" onchange="toggleSanctionFields()" required>
                                    <option value=""><?= _("-- Sélectionner le type de sanction --") ?></option>
                                    <?php foreach ($typesSanctions as $ts): ?>
                                        <option value="<?= $ts['id'] ?>"
                                                data-duree="<?= $ts['demande_duree_jours'] ?>"
                                                data-heures="<?= $ts['demande_heures'] ?>">
                                            <?= htmlspecialchars($ts['libelle']) ?> (Autorité : <?= htmlspecialchars($ts['autorite_min_requise']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="motif" class="form-label required-field"><?= _("Motif Officiel") ?></label>
                                <input type="text" name="motif" id="motif" class="form-control" value="<?= !empty($incident) ? htmlspecialchars("Comportement lors de l'incident : " . $incident['type_libelle']) : '' ?>" placeholder="<?= _('Ex: Non respect du règlement intérieur, bagarre...') ?>" required>
                            </div>

                            <!-- Duration / Hours Fields -->
                            <div class="row g-3 mb-3" id="groupDureeJours" style="display: none;">
                                <div class="col-md-12">
                                    <label for="duree_jours" class="form-label required-field"><?= _("Durée de l'exclusion (en jours)") ?></label>
                                    <input type="number" name="duree_jours" id="duree_jours" class="form-control" min="1" max="90" value="1">
                                </div>
                            </div>

                            <div class="row g-3 mb-3" id="groupDureeHeures" style="display: none;">
                                <div class="col-md-12">
                                    <label for="duree_heures" class="form-label required-field"><?= _("Nombre d'heures (de retenue / TIS)") ?></label>
                                    <input type="number" name="duree_heures" id="duree_heures" class="form-control" min="1" max="40" value="2">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="date_debut_execution" class="form-label"><?= _("Date Début Exécution") ?></label>
                                    <input type="date" name="date_debut_execution" id="date_debut_execution" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label for="date_fin_execution" class="form-label"><?= _("Date Fin Exécution") ?></label>
                                    <input type="date" name="date_fin_execution" id="date_fin_execution" class="form-control">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="details" class="form-label"><?= _("Détails / Instructions administratives") ?></label>
                                <textarea name="details" id="details" rows="3" class="form-control" placeholder="<?= _('Instructions particulières, travaux à effectuer...') ?>"></textarea>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Submission -->
                <div class="col-12 text-end mb-4">
                    <a href="/discipline/sanctions" class="btn btn-link text-muted me-2"><?= _("Annuler") ?></a>
                    <button type="submit" class="btn btn-primary btn-lg d-inline-flex align-items-center gap-2">
                        <i class="ph-duotone ph-check-circle fs-4"></i><?= _("Prononcer la sanction") ?>
                    </button>
                </div>

            </div>
        </form>

    </div>
</div>

<script>
function loadElevesByClasse() {
    const classeId = document.getElementById('selectClasseFilter').value;
    const selectEleve = document.getElementById('eleve_id');

    selectEleve.innerHTML = '<option value=""><?= _("-- Chargement... --") ?></option>';
    selectEleve.disabled = true;

    if (!classeId) {
        selectEleve.innerHTML = '<option value=""><?= _("-- Sélectionner d\'abord une classe --") ?></option>';
        return;
    }

    fetch('/discipline/incidents/ajax-eleves?classe_id=' + classeId)
        .then(res => res.json())
        .then(data => {
            selectEleve.innerHTML = '<option value=""><?= _("-- Sélectionner l\'élève --") ?></option>';
            if (data.length === 0) {
                selectEleve.innerHTML = '<option value=""><?= _("Aucun élève trouvé dans cette classe") ?></option>';
                return;
            }
            data.forEach(e => {
                const opt = document.createElement('option');
                opt.value = e.id_eleve;
                opt.textContent = `${e.nom} ${e.prenom} (${e.matricule})`;
                selectEleve.appendChild(opt);
            });
            selectEleve.disabled = false;
        })
        .catch(err => {
            console.error(err);
            selectEleve.innerHTML = '<option value=""><?= _("Erreur de chargement") ?></option>';
        });
}

function toggleSanctionFields() {
    const selectType = document.getElementById('type_sanction_id');
    const selectedOpt = selectType.options[selectType.selectedIndex];

    const groupJours = document.getElementById('groupDureeJours');
    const groupHeures = document.getElementById('groupDureeHeures');

    if (selectedOpt && selectedOpt.dataset) {
        const needJours = parseInt(selectedOpt.dataset.duree) === 1;
        const needHeures = parseInt(selectedOpt.dataset.heures) === 1;

        groupJours.style.display = needJours ? 'block' : 'none';
        groupHeures.style.display = needHeures ? 'block' : 'none';
    } else {
        groupJours.style.display = 'none';
        groupHeures.style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>