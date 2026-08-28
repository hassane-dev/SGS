<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Modifier / Corriger l\'Affectation Pédagogique') ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="/affectations-pedagogiques" class="btn btn-outline-secondary">
                            <i class="ph-duotone ph-arrow-left me-1"></i>
                            <?= _('Retour au registre') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-lg-9 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Édition de l\'Affectation #') . htmlspecialchars($affectation['id']) ?> (<?= htmlspecialchars($affectation['annee_libelle'] ?? '') ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($_SESSION['error_message']) ?>
                                <?php unset($_SESSION['error_message']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-info py-2 mb-3">
                            <i class="ph-duotone ph-info me-2"></i>
                            <?= _('Règle d\'historisation : Si vous modifiez l\'enseignant, la classe ou la matière, l\'ancienne affectation sera clôturée dans l\'historique et une nouvelle affectation sera créée.') ?>
                        </div>

                        <form action="/affectations-pedagogiques/update" method="POST" id="editAffectationForm">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($affectation['id']) ?>">
                            <!-- Hidden resolved Class ID -->
                            <input type="hidden" name="classe_id" id="classe_id" value="<?= htmlspecialchars($affectation['classe_id']) ?>">

                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3"><i class="ph-duotone ph-tree-structure me-1"></i> 1. <?= _('Détermination de la Classe') ?></h6>

                                    <div class="row">
                                        <!-- Cycle -->
                                        <div class="col-md-3 mb-3">
                                            <label for="select_cycle" class="form-label"><?= _('Cycle') ?> <span class="text-danger">*</span></label>
                                            <select id="select_cycle" class="form-select" required>
                                                <option value=""><?= _('-- Choisir --') ?></option>
                                                <?php foreach ($cycles as $cy): ?>
                                                    <option value="<?= $cy['id_cycle'] ?>" data-nom="<?= htmlspecialchars(strtolower($cy['nom_cycle'])) ?>" <?= ((int)($affectation['cycle_id'] ?? 0) === (int)$cy['id_cycle']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($cy['nom_cycle']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Niveau -->
                                        <div class="col-md-3 mb-3">
                                            <label for="select_niveau" class="form-label"><?= _('Niveau') ?> <span class="text-danger">*</span></label>
                                            <select id="select_niveau" class="form-select" required>
                                                <option value="<?= htmlspecialchars($affectation['niveau']) ?>" selected><?= htmlspecialchars($affectation['niveau']) ?></option>
                                            </select>
                                        </div>

                                        <!-- Série -->
                                        <div class="col-md-3 mb-3" id="group_serie" style="<?= !empty($affectation['serie']) ? '' : 'display: none;' ?>">
                                            <label for="select_serie" class="form-label"><?= _('Série') ?></label>
                                            <select id="select_serie" class="form-select">
                                                <?php if (!empty($affectation['serie'])): ?>
                                                    <option value="<?= htmlspecialchars($affectation['serie']) ?>" selected><?= htmlspecialchars($affectation['serie']) ?></option>
                                                <?php else: ?>
                                                    <option value=""><?= _('-- Aucune --') ?></option>
                                                <?php endif; ?>
                                            </select>
                                        </div>

                                        <!-- Numéro -->
                                        <div class="col-md-3 mb-3">
                                            <label for="select_numero" class="form-label"><?= _('Numéro / Division') ?> <span class="text-danger">*</span></label>
                                            <select id="select_numero" class="form-select" required>
                                                <option value="<?= htmlspecialchars($affectation['numero']) ?>" selected><?= htmlspecialchars($affectation['numero']) ?></option>
                                            </select>
                                        </div>
                                    </div>

                                    <div id="classe_status_badge" class="alert alert-success py-2 mb-0 d-flex align-items-center">
                                        <i class="ph-duotone ph-check-circle me-2 fs-5"></i>
                                        <span id="classe_status_text"><strong>Classe actuelle :</strong> <?= htmlspecialchars($affectation['niveau'] . ' ' . ($affectation['serie'] ?? '') . ' ' . $affectation['numero']) ?> (ID #<?= htmlspecialchars($affectation['classe_id']) ?>)</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Matière -->
                                <div class="col-md-6 mb-3">
                                    <label for="matiere_id" class="form-label"><?= _('Matière') ?> <span class="text-danger">*</span></label>
                                    <select name="matiere_id" id="matiere_id" class="form-select" required>
                                        <?php foreach ($matieres as $m): ?>
                                            <option value="<?= $m['id_matiere'] ?>" <?= ((int)$m['id_matiere'] === (int)$affectation['matiere_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($m['nom_matiere']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Enseignant -->
                                <div class="col-md-6 mb-3">
                                    <label for="enseignant_id" class="form-label"><?= _('Enseignant Titulaire') ?> <span class="text-danger">*</span></label>
                                    <select name="enseignant_id" id="enseignant_id" class="form-select" required>
                                        <?php foreach ($enseignants as $t): ?>
                                            <option value="<?= $t['id_user'] ?>" <?= ((int)$t['id_user'] === (int)$affectation['enseignant_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($t['prenom'] . ' ' . $t['nom'] . ' (' . ($t['identifiant_public'] ?? 'ENS') . ')') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="volume_horaire_hebdo" class="form-label"><?= _('Volume Horaire Hebdomadaire (heures)') ?></label>
                                    <input type="number" step="0.5" min="0" name="volume_horaire_hebdo" id="volume_horaire_hebdo" class="form-control" value="<?= htmlspecialchars($affectation['volume_horaire_hebdo']) ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="statut" class="form-label"><?= _('Statut') ?></label>
                                    <select name="statut" id="statut" class="form-select">
                                        <option value="actif" <?= ($affectation['statut'] === 'actif') ? 'selected' : '' ?>><?= _('Actif') ?></option>
                                        <option value="suspendu" <?= ($affectation['statut'] === 'suspendu') ? 'selected' : '' ?>><?= _('Suspendu') ?></option>
                                        <option value="provisoire" <?= ($affectation['statut'] === 'provisoire') ? 'selected' : '' ?>><?= _('Provisoire') ?></option>
                                        <option value="termine" <?= ($affectation['statut'] === 'termine') ? 'selected' : '' ?>><?= _('Terminé') ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="date_debut" class="form-label"><?= _('Date d\'effet / Début') ?> <span class="text-danger">*</span></label>
                                    <input type="date" name="date_debut" id="date_debut" class="form-control" value="<?= htmlspecialchars($affectation['date_debut']) ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="date_fin" class="form-label"><?= _('Date de fin (Optionnelle)') ?></label>
                                    <input type="date" name="date_fin" id="date_fin" class="form-control" value="<?= htmlspecialchars($affectation['date_fin'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="motif_changement" class="form-label"><?= _('Commentaire / Motif de la modification') ?></label>
                                <textarea name="motif_changement" id="motif_changement" class="form-control" rows="2" placeholder="<?= _('Raison de la correction ou de la réaffectation...') ?>"><?= htmlspecialchars($affectation['motif_changement'] ?? '') ?></textarea>
                            </div>

                            <div class="text-end">
                                <a href="/affectations-pedagogiques" class="btn btn-link-secondary me-2"><?= _('Annuler') ?></a>
                                <button type="submit" id="btn_submit" class="btn btn-primary"><?= _('Enregistrer la modification') ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectCycle = document.getElementById('select_cycle');
    const selectNiveau = document.getElementById('select_niveau');
    const groupSerie = document.getElementById('group_serie');
    const selectSerie = document.getElementById('select_serie');
    const selectNumero = document.getElementById('select_numero');
    const inputClasseId = document.getElementById('classe_id');
    const selectMatiere = document.getElementById('matiere_id');
    const selectEnseignant = document.getElementById('enseignant_id');
    const statusText = document.getElementById('classe_status_text');
    const statusBadge = document.getElementById('classe_status_badge');
    const btnSubmit = document.getElementById('btn_submit');
    const currentAssignmentId = "<?= htmlspecialchars($affectation['id']) ?>";

    function resetSelect(selectEl, defaultText) {
        selectEl.innerHTML = `<option value="">${defaultText}</option>`;
        selectEl.disabled = true;
    }

    // 1. Cycle Change -> Load Niveaux
    selectCycle.addEventListener('change', function() {
        const cycleId = this.value;
        resetSelect(selectNiveau, '-- Choisir Niveau --');
        resetSelect(selectSerie, '-- Choisir Série --');
        resetSelect(selectNumero, '-- Choisir Numéro --');
        resetSelect(selectMatiere, '-- Déterminer d\'abord la classe --');
        resetSelect(selectEnseignant, '-- Choisir d\'abord la matière --');
        inputClasseId.value = '';
        btnSubmit.disabled = true;

        if (!cycleId) {
            groupSerie.style.display = 'none';
            selectSerie.required = false;
            return;
        }

        fetch(`/affectations-pedagogiques/get-niveaux?cycle_id=${cycleId}`)
            .then(res => res.json())
            .then(data => {
                selectNiveau.disabled = false;
                data.forEach(niv => {
                    selectNiveau.innerHTML += `<option value="${niv}">${niv}</option>`;
                });
            });
    });

    // 2. Niveau Change -> Load Series or Numeros
    selectNiveau.addEventListener('change', function() {
        const niveau = this.value;
        const cycleId = selectCycle.value;
        resetSelect(selectSerie, '-- Choisir Série --');
        resetSelect(selectNumero, '-- Choisir Numéro --');
        resetSelect(selectMatiere, '-- Déterminer d\'abord la classe --');
        resetSelect(selectEnseignant, '-- Choisir d\'abord la matière --');
        inputClasseId.value = '';
        btnSubmit.disabled = true;

        if (!niveau) return;

        fetch(`/affectations-pedagogiques/get-series?niveau=${encodeURIComponent(niveau)}&cycle_id=${cycleId}`)
            .then(res => res.json())
            .then(series => {
                if (series && series.length > 0) {
                    groupSerie.style.display = 'block';
                    selectSerie.required = true;
                    selectSerie.disabled = false;
                    series.forEach(s => {
                        selectSerie.innerHTML += `<option value="${s}">${s}</option>`;
                    });
                } else {
                    groupSerie.style.display = 'none';
                    selectSerie.required = false;
                    fetch(`/affectations-pedagogiques/get-numeros?niveau=${encodeURIComponent(niveau)}&cycle_id=${cycleId}`)
                        .then(res => res.json())
                        .then(numeros => {
                            selectNumero.disabled = false;
                            numeros.forEach(n => {
                                selectNumero.innerHTML += `<option value="${n}">${n}</option>`;
                            });
                        });
                }
            });
    });

    // 3. Serie Change -> Load Numeros
    selectSerie.addEventListener('change', function() {
        const niveau = selectNiveau.value;
        const cycleId = selectCycle.value;
        const serie = this.value;
        resetSelect(selectNumero, '-- Choisir Numéro --');
        resetSelect(selectMatiere, '-- Déterminer d\'abord la classe --');
        resetSelect(selectEnseignant, '-- Choisir d\'abord la matière --');
        inputClasseId.value = '';
        btnSubmit.disabled = true;

        if (!serie) return;

        fetch(`/affectations-pedagogiques/get-numeros?niveau=${encodeURIComponent(niveau)}&serie=${encodeURIComponent(serie)}&cycle_id=${cycleId}`)
            .then(res => res.json())
            .then(data => {
                selectNumero.disabled = false;
                data.forEach(n => {
                    selectNumero.innerHTML += `<option value="${n}">${n}</option>`;
                });
            });
    });

    // 4. Numero Change -> Resolve Class ID & Load Subjects
    selectNumero.addEventListener('change', function() {
        const cycleId = selectCycle.value;
        const niveau = selectNiveau.value;
        const serie = (groupSerie.style.display !== 'none') ? selectSerie.value : '';
        const numero = this.value;

        resetSelect(selectMatiere, '-- Chargement des matières... --');
        resetSelect(selectEnseignant, '-- Choisir d\'abord la matière --');
        btnSubmit.disabled = true;

        if (!numero) return;

        fetch(`/affectations-pedagogiques/get-classe-id?niveau=${encodeURIComponent(niveau)}&serie=${encodeURIComponent(serie)}&numero=${encodeURIComponent(numero)}&cycle_id=${cycleId}`)
            .then(res => res.json())
            .then(data => {
                if (data.id_classe) {
                    inputClasseId.value = data.id_classe;
                    statusBadge.className = 'alert alert-success py-2 mb-0 d-flex align-items-center';
                    statusText.innerHTML = `<strong>Classe identifiée :</strong> ${niveau} ${serie ? serie + ' ' : ''} - ${numero} (ID #${data.id_classe})`;

                    // Load Available Subjects for this Class (excluding current assignment ID so its subject remains available)
                    fetch(`/affectations-pedagogiques/get-matieres?classe_id=${data.id_classe}&exclude_assignment_id=${currentAssignmentId}`)
                        .then(res => res.json())
                        .then(matieres => {
                            selectMatiere.innerHTML = '<option value="">-- Choisir une matière disponible --</option>';
                            if (matieres.length === 0) {
                                selectMatiere.innerHTML = '<option value="">-- Toutes les matières sont déjà affectées --</option>';
                                statusBadge.className = 'alert alert-warning py-2 mb-0 d-flex align-items-center';
                                statusText.innerHTML += ` | <em>Toutes les matières ont une affectation active/suspendue.</em>`;
                            } else {
                                selectMatiere.disabled = false;
                                matieres.forEach(m => {
                                    selectMatiere.innerHTML += `<option value="${m.id_matiere}">${m.nom_matiere} (Coef: ${m.coefficient})</option>`;
                                });
                            }
                        });

                    // Load Eligible Teachers for this Cycle
                    fetch(`/affectations-pedagogiques/get-enseignants?cycle_id=${cycleId}`)
                        .then(res => res.json())
                        .then(teachers => {
                            selectEnseignant.innerHTML = '<option value="">-- Choisir un enseignant éligible --</option>';
                            selectEnseignant.disabled = false;
                            teachers.forEach(t => {
                                selectEnseignant.innerHTML += `<option value="${t.id_user}">${t.prenom} ${t.nom} (${t.identifiant_public || 'ENS'})</option>`;
                            });
                        });

                } else {
                    statusBadge.className = 'alert alert-danger py-2 mb-0 d-flex align-items-center';
                    statusText.innerHTML = `<strong>Classe non trouvée.</strong>`;
                }
            });
    });

    selectMatiere.addEventListener('change', function() {
        if (this.value && selectEnseignant.value && inputClasseId.value) {
            btnSubmit.disabled = false;
        }
    });

    selectEnseignant.addEventListener('change', function() {
        if (this.value && selectMatiere.value && inputClasseId.value) {
            btnSubmit.disabled = false;
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
