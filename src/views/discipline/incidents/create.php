<?php
$title = _("Signaler un Incident Disciplinaire");
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
                            <h5 class="m-b-10"><?= _("Signalement d'Incident") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _("Accueil") ?></a></li>
                            <li class="breadcrumb-item"><a href="/discipline/incidents"><?= _("Discipline") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Nouveau Signalement") ?></li>
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

        <form action="/discipline/incidents/store" method="POST" id="formIncidentCreate">
            <div class="row">

                <!-- Contexte & faits -->
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="ph-duotone ph-info me-2 text-primary"></i><?= _("1. Informations sur l'Incident") ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="type_incident_id" class="form-label required-field"><?= _("Type d'Incident") ?></label>
                                <select name="type_incident_id" id="type_incident_id" class="form-select" required>
                                    <option value=""><?= _("-- Sélectionner le type d'incident --") ?></option>
                                    <?php foreach ($typesIncidents as $ti): ?>
                                        <option value="<?= $ti['id'] ?>">
                                            <?= htmlspecialchars($ti['libelle']) ?> (Gravité : <?= htmlspecialchars($ti['niveau_gravite']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="date_incident" class="form-label required-field"><?= _("Date des Faits") ?></label>
                                    <input type="date" name="date_incident" id="date_incident" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="heure_incident" class="form-label"><?= _("Heure (optionnel)") ?></label>
                                    <input type="time" name="heure_incident" id="heure_incident" class="form-control" value="<?= date('H:i') ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="lieu" class="form-label"><?= _("Lieu de l'Incident") ?></label>
                                <input type="text" name="lieu" id="lieu" class="form-control" placeholder="<?= _('Ex: Salle 102, Cour de récréation, Refectoire...') ?>">
                            </div>

                            <div class="mb-3">
                                <label for="description_faits" class="form-label required-field"><?= _("Description Détaillée des Faits") ?></label>
                                <textarea name="description_faits" id="description_faits" rows="5" class="form-control" placeholder="<?= _('Décrivez objectivement les événements survenus...') ?>" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sélection des élèves impliqués -->
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="ph-duotone ph-users me-2 text-primary"></i><?= _("2. Élève(s) Impliqué(s)") ?></h5>
                        </div>
                        <div class="card-body">

                            <!-- Recherche & Ajout -->
                            <div class="p-3 bg-light rounded mb-3">
                                <label class="form-label fw-bold"><?= _("Ajouter un élève à l'incident") ?></label>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <select id="selectClasseFilter" class="form-select form-select-sm" onchange="loadElevesByClasse()">
                                            <option value=""><?= _("-- Filtrer par classe --") ?></option>
                                            <?php foreach ($classes as $cls): ?>
                                                <option value="<?= $cls['id_classe'] ?>"><?= htmlspecialchars($cls['niveau'] . ($cls['serie'] ? ' ' . $cls['serie'] : '') . ($cls['numero'] ? ' ' . $cls['numero'] : '')) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <select id="selectEleveAdd" class="form-select form-select-sm" disabled>
                                            <option value=""><?= _("-- Sélectionner l'élève --") ?></option>
                                        </select>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary w-100 d-inline-flex align-items-center justify-content-center gap-1" onclick="addSelectedEleve()">
                                    <i class="ph-duotone ph-plus-circle"></i><?= _("Ajouter cet élève à la liste") ?>
                                </button>
                            </div>

                            <!-- Table des élèves sélectionnés -->
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle" id="tableElevesImpliques">
                                    <thead class="table-light">
                                        <tr>
                                            <th><?= _("Élève") ?></th>
                                            <th style="width: 150px;"><?= _("Rôle") ?></th>
                                            <th><?= _("Observation") ?></th>
                                            <th style="width: 40px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyEleves">
                                        <tr id="rowEmptyEleves">
                                            <td colspan="4" class="text-center text-muted py-3">
                                                <?= _("Aucun élève ajouté pour le moment. Veuillez utiliser le sélecteur ci-dessus.") ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Soumission -->
                <div class="col-12 text-end mb-4">
                    <a href="/discipline/incidents" class="btn btn-link text-muted me-2"><?= _("Annuler") ?></a>
                    <button type="submit" class="btn btn-primary btn-lg d-inline-flex align-items-center gap-2">
                        <i class="ph-duotone ph-paper-plane-right fs-4"></i><?= _("Enregistrer le signalement") ?>
                    </button>
                </div>

            </div>
        </form>

    </div>
</div>

<script>
let eleveIndex = 0;
let addedEleveIds = new Set();

function loadElevesByClasse() {
    const classeId = document.getElementById('selectClasseFilter').value;
    const selectEleve = document.getElementById('selectEleveAdd');

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
                opt.dataset.nom = `${e.nom} ${e.prenom}`;
                selectEleve.appendChild(opt);
            });
            selectEleve.disabled = false;
        })
        .catch(err => {
            console.error(err);
            selectEleve.innerHTML = '<option value=""><?= _("Erreur de chargement") ?></option>';
        });
}

function addSelectedEleve() {
    const selectEleve = document.getElementById('selectEleveAdd');
    const eleveId = selectEleve.value;

    if (!eleveId) {
        alert("<?= _("Veuillez sélectionner un élève.") ?>");
        return;
    }

    if (addedEleveIds.has(eleveId)) {
        alert("<?= _("Cet élève est déjà ajouté à la liste.") ?>");
        return;
    }

    const selectedOpt = selectEleve.options[selectEleve.selectedIndex];
    const nomEleve = selectedOpt.dataset.nom || selectedOpt.textContent;

    const tbody = document.getElementById('tbodyEleves');
    const rowEmpty = document.getElementById('rowEmptyEleves');
    if (rowEmpty) {
        rowEmpty.remove();
    }

    const tr = document.createElement('tr');
    tr.id = 'rowEleve_' + eleveId;
    tr.innerHTML = `
        <td>
            <input type="hidden" name="eleves[${eleveIndex}][eleve_id]" value="${eleveId}">
            <div class="fw-bold">${nomEleve}</div>
        </td>
        <td>
            <select name="eleves[${eleveIndex}][role_implication]" class="form-select form-select-sm">
                <option value="auteur_principal" selected><?= _("Auteur Principal") ?></option>
                <option value="co_auteur"><?= _("Co-auteur") ?></option>
                <option value="complice"><?= _("Complice") ?></option>
                <option value="victime"><?= _("Victime") ?></option>
                <option value="temoin"><?= _("Témoin") ?></option>
            </select>
        </td>
        <td>
            <input type="text" name="eleves[${eleveIndex}][observation_individuelle]" class="form-control form-select-sm" placeholder="<?= _('Observation spécifique...') ?>">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-icon btn-light-danger" onclick="removeEleveRow('${eleveId}')">
                <i class="ph-duotone ph-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    addedEleveIds.add(eleveId);
    eleveIndex++;

    selectEleve.value = '';
}

function removeEleveRow(eleveId) {
    const row = document.getElementById('rowEleve_' + eleveId);
    if (row) {
        row.remove();
        addedEleveIds.delete(eleveId);
    }

    const tbody = document.getElementById('tbodyEleves');
    if (tbody.children.length === 0) {
        tbody.innerHTML = `
            <tr id="rowEmptyEleves">
                <td colspan="4" class="text-center text-muted py-3">
                    <?= _("Aucun élève ajouté pour le moment. Veuillez utiliser le sélecteur ci-dessus.") ?>
                </td>
            </tr>
        `;
    }
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>