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
                            <h2 class="mb-0"><?= _('Affectations Pédagogiques') ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <?php if (Auth::can('manage_affectations', 'pedagogy')): ?>
                            <a href="/affectations-pedagogiques/create" class="btn btn-primary">
                                <i class="ph-duotone ph-plus-circle me-1"></i>
                                <?= _('Nouvelle Affectation') ?>
                            </a>
                        <?php endif; ?>
                        <a href="/affectations-pedagogiques/history" class="btn btn-outline-secondary ms-2">
                            <i class="ph-duotone ph-clock-counter-clockwise me-1"></i>
                            <?= _('Historique') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- Alerts -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <?php unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <?php unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- [ Filter Card ] -->
        <div class="row">
            <div class="col-12">
                <div class="card mb-3">
                    <div class="card-body py-3">
                        <form action="/affectations-pedagogiques" method="GET" class="row g-2 align-items-end" id="filterForm">
                            <!-- Hidden resolved Class ID -->
                            <input type="hidden" name="classe_id" id="filter_classe_id" value="<?= htmlspecialchars($filters['classe_id'] ?? '') ?>">

                            <!-- Cycle -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold"><?= _('Cycle') ?></label>
                                <select name="cycle_id" id="filter_cycle" class="form-select form-select-sm">
                                    <option value=""><?= _('-- Tous les cycles --') ?></option>
                                    <?php foreach ($cycles as $cy): ?>
                                        <option value="<?= $cy['id_cycle'] ?>" <?= (isset($filters['cycle_id']) && $filters['cycle_id'] == $cy['id_cycle']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cy['nom_cycle']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Niveau -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold"><?= _('Niveau') ?></label>
                                <select name="niveau" id="filter_niveau" class="form-select form-select-sm" <?= empty($filters['cycle_id']) && empty($niveaux) ? 'disabled' : '' ?>>
                                    <option value=""><?= _('-- Tous les niveaux --') ?></option>
                                    <?php foreach ($niveaux as $n): ?>
                                        <option value="<?= htmlspecialchars($n) ?>" <?= (isset($filters['niveau']) && $filters['niveau'] === $n) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($n) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Série -->
                            <div class="col-md-2" id="group_filter_serie" style="<?= (!empty($series) || !empty($filters['serie'])) ? '' : 'display: none;' ?>">
                                <label class="form-label small fw-bold"><?= _('Série') ?></label>
                                <select name="serie" id="filter_serie" class="form-select form-select-sm" <?= empty($filters['niveau']) ? 'disabled' : '' ?>>
                                    <option value=""><?= _('-- Toutes les séries --') ?></option>
                                    <?php foreach ($series as $s): ?>
                                        <option value="<?= htmlspecialchars($s) ?>" <?= (isset($filters['serie']) && $filters['serie'] === $s) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($s) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Numéro / Division -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold"><?= _('Numéro / Div.') ?></label>
                                <select name="numero" id="filter_numero" class="form-select form-select-sm" <?= empty($filters['niveau']) ? 'disabled' : '' ?>>
                                    <option value=""><?= _('-- Tous --') ?></option>
                                    <?php foreach ($numeros as $num): ?>
                                        <option value="<?= htmlspecialchars($num) ?>" <?= (isset($filters['numero']) && (string)$filters['numero'] === (string)$num) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($num) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Matière -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold"><?= _('Matière') ?></label>
                                <select name="matiere_id" id="filter_matiere" class="form-select form-select-sm">
                                    <option value=""><?= _('-- Toutes les matières --') ?></option>
                                    <?php foreach ($matieres as $m): ?>
                                        <option value="<?= $m['id_matiere'] ?>" <?= (isset($filters['matiere_id']) && $filters['matiere_id'] == $m['id_matiere']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($m['nom_matiere']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Enseignant -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold"><?= _('Enseignant') ?></label>
                                <select name="enseignant_id" id="filter_enseignant" class="form-select form-select-sm">
                                    <option value=""><?= _('-- Tous les enseignants --') ?></option>
                                    <?php foreach ($enseignants as $e): ?>
                                        <option value="<?= $e['id_user'] ?>" <?= (isset($filters['enseignant_id']) && $filters['enseignant_id'] == $e['id_user']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($e['full_name'] ?? ($e['prenom'] . ' ' . $e['nom'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Statut -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold"><?= _('Statut') ?></label>
                                <select name="statut" id="filter_statut" class="form-select form-select-sm">
                                    <option value=""><?= _('-- Tous les statuts --') ?></option>
                                    <option value="actif" <?= (isset($filters['statut']) && $filters['statut'] === 'actif') ? 'selected' : '' ?>><?= _('Actif') ?></option>
                                    <option value="suspendu" <?= (isset($filters['statut']) && $filters['statut'] === 'suspendu') ? 'selected' : '' ?>><?= _('Suspendu') ?></option>
                                    <option value="provisoire" <?= (isset($filters['statut']) && $filters['statut'] === 'provisoire') ? 'selected' : '' ?>><?= _('Provisoire') ?></option>
                                    <option value="termine" <?= (isset($filters['statut']) && $filters['statut'] === 'termine') ? 'selected' : '' ?>><?= _('Terminé') ?></option>
                                </select>
                            </div>

                            <div class="col-md-2 text-end ms-auto">
                                <button type="submit" class="btn btn-sm btn-primary me-1"><i class="ph-duotone ph-funnel me-1"></i><?= _('Filtrer') ?></button>
                                <a href="/affectations-pedagogiques" class="btn btn-sm btn-outline-secondary"><?= _('Réinitialiser') ?></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterCycle = document.getElementById('filter_cycle');
    const filterNiveau = document.getElementById('filter_niveau');
    const groupSerie = document.getElementById('group_filter_serie');
    const filterSerie = document.getElementById('filter_serie');
    const filterNumero = document.getElementById('filter_numero');
    const filterClasseId = document.getElementById('filter_classe_id');
    const filterMatiere = document.getElementById('filter_matiere');
    const filterEnseignant = document.getElementById('filter_enseignant');

    function resetSelect(selectEl, defaultText) {
        selectEl.innerHTML = `<option value="">${defaultText}</option>`;
        selectEl.disabled = true;
    }

    // 1. Cycle Change -> Load Niveaux & Teachers
    filterCycle.addEventListener('change', function() {
        const cycleId = this.value;
        resetSelect(filterNiveau, '-- Tous les niveaux --');
        resetSelect(filterSerie, '-- Toutes les séries --');
        resetSelect(filterNumero, '-- Tous --');
        filterClasseId.value = '';

        if (!cycleId) {
            groupSerie.style.display = 'none';
            // Fetch all levels for lycee
            fetch(`/affectations-pedagogiques/get-niveaux`)
                .then(res => res.json())
                .then(data => {
                    filterNiveau.disabled = false;
                    data.forEach(niv => {
                        filterNiveau.innerHTML += `<option value="${niv}">${niv}</option>`;
                    });
                });
            return;
        }

        fetch(`/affectations-pedagogiques/get-niveaux?cycle_id=${cycleId}`)
            .then(res => res.json())
            .then(data => {
                filterNiveau.disabled = false;
                data.forEach(niv => {
                    filterNiveau.innerHTML += `<option value="${niv}">${niv}</option>`;
                });
            });

        fetch(`/affectations-pedagogiques/get-enseignants?cycle_id=${cycleId}`)
            .then(res => res.json())
            .then(teachers => {
                filterEnseignant.innerHTML = '<option value="">-- Tous les enseignants --</option>';
                teachers.forEach(t => {
                    filterEnseignant.innerHTML += `<option value="${t.id_user}">${t.prenom} ${t.nom}</option>`;
                });
            });
    });

    // 2. Niveau Change -> Load Series or Numeros
    filterNiveau.addEventListener('change', function() {
        const niveau = this.value;
        const cycleId = filterCycle.value;
        resetSelect(filterSerie, '-- Toutes les séries --');
        resetSelect(filterNumero, '-- Tous --');
        filterClasseId.value = '';

        if (!niveau) return;

        fetch(`/affectations-pedagogiques/get-series?niveau=${encodeURIComponent(niveau)}&cycle_id=${cycleId}`)
            .then(res => res.json())
            .then(series => {
                if (series && series.length > 0) {
                    groupSerie.style.display = 'block';
                    filterSerie.disabled = false;
                    series.forEach(s => {
                        filterSerie.innerHTML += `<option value="${s}">${s}</option>`;
                    });
                } else {
                    groupSerie.style.display = 'none';
                    fetch(`/affectations-pedagogiques/get-numeros?niveau=${encodeURIComponent(niveau)}&cycle_id=${cycleId}`)
                        .then(res => res.json())
                        .then(numeros => {
                            filterNumero.disabled = false;
                            numeros.forEach(n => {
                                filterNumero.innerHTML += `<option value="${n}">${n}</option>`;
                            });
                        });
                }
            });
    });

    // 3. Serie Change -> Load Numeros
    filterSerie.addEventListener('change', function() {
        const niveau = filterNiveau.value;
        const cycleId = filterCycle.value;
        const serie = this.value;
        resetSelect(filterNumero, '-- Tous --');
        filterClasseId.value = '';

        if (!serie) return;

        fetch(`/affectations-pedagogiques/get-numeros?niveau=${encodeURIComponent(niveau)}&serie=${encodeURIComponent(serie)}&cycle_id=${cycleId}`)
            .then(res => res.json())
            .then(data => {
                filterNumero.disabled = false;
                data.forEach(n => {
                    filterNumero.innerHTML += `<option value="${n}">${n}</option>`;
                });
            });
    });

    // 4. Numero Change -> Resolve Class ID & Load Subjects
    filterNumero.addEventListener('change', function() {
        const cycleId = filterCycle.value;
        const niveau = filterNiveau.value;
        const serie = (groupSerie.style.display !== 'none') ? filterSerie.value : '';
        const numero = this.value;

        if (!numero) return;

        fetch(`/affectations-pedagogiques/get-classe-id?niveau=${encodeURIComponent(niveau)}&serie=${encodeURIComponent(serie)}&numero=${encodeURIComponent(numero)}&cycle_id=${cycleId}`)
            .then(res => res.json())
            .then(data => {
                if (data.id_classe) {
                    filterClasseId.value = data.id_classe;
                    fetch(`/affectations-pedagogiques/get-matieres?classe_id=${data.id_classe}`)
                        .then(res => res.json())
                        .then(matieres => {
                            filterMatiere.innerHTML = '<option value="">-- Toutes les matières --</option>';
                            matieres.forEach(m => {
                                filterMatiere.innerHTML += `<option value="${m.id_matiere}">${m.nom_matiere}</option>`;
                            });
                        });
                }
            });
    });
});
</script>

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Registre des Affectations Pédagogiques') ?> (<?= htmlspecialchars($active_year['libelle'] ?? 'En cours') ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th><?= _('Classe') ?></th>
                                        <th><?= _('Matière') ?></th>
                                        <th><?= _('Enseignant') ?></th>
                                        <th><?= _('Vol. Hebdo') ?></th>
                                        <th><?= _('Dates Effectives') ?></th>
                                        <th><?= _('Statut') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($affectations)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <?= _('Aucune affectation pédagogique trouvée pour ces critères.') ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($affectations as $aff): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars(($aff['niveau'] ?? '') . ' ' . ($aff['serie'] ?? '') . ' ' . ($aff['numero'] ?? '')) ?></strong>
                                                </td>
                                                <td><?= htmlspecialchars($aff['nom_matiere']) ?></td>
                                                <td>
                                                    <span class="fw-bold"><?= htmlspecialchars($aff['enseignant_nom']) ?></span>
                                                    <?php if (!empty($aff['enseignant_matricule'])): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars($aff['enseignant_matricule']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= number_format($aff['volume_horaire_hebdo'] ?? 0, 1) ?> h/sem</td>
                                                <td>
                                                    <small>
                                                        Du : <?= htmlspecialchars($aff['date_debut'] ?? 'N/A') ?><br>
                                                        Au : <?= htmlspecialchars($aff['date_fin'] ?? 'En cours') ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $badge_class = 'badge-success';
                                                    if ($aff['statut'] === 'suspendu') $badge_class = 'badge-warning';
                                                    if ($aff['statut'] === 'provisoire') $badge_class = 'badge-info';
                                                    if ($aff['statut'] === 'termine' || $aff['statut'] === 'annule') $badge_class = 'badge-secondary';
                                                    ?>
                                                    <span class="badge <?= $badge_class ?>"><?= _(ucfirst(htmlspecialchars($aff['statut']))) ?></span>
                                                </td>
                                                <td class="text-end">
                                                    <?php if (Auth::can('manage_affectations', 'pedagogy')): ?>
                                                        <a href="/affectations-pedagogiques/edit?id=<?= $aff['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="<?= _('Modifier / Corriger l\'affectation') ?>">
                                                            <i class="ph-duotone ph-pencil me-1"></i><?= _('Modifier') ?>
                                                        </a>
                                                        <?php if ($aff['statut'] === 'actif'): ?>
                                                            <form action="/affectations-pedagogiques/suspend" method="POST" class="d-inline-block" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir suspendre cette affectation ?') ?>');">
                                                                <input type="hidden" name="id" value="<?= $aff['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-warning me-1" title="<?= _('Suspendre l\'affectation') ?>"><?= _('Suspendre') ?></button>
                                                            </form>
                                                            <form action="/affectations-pedagogiques/terminate" method="POST" class="d-inline-block" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir clôturer cette affectation ?') ?>');">
                                                                <input type="hidden" name="id" value="<?= $aff['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= _('Clôturer l\'affectation') ?>"><?= _('Clôturer') ?></button>
                                                            </form>
                                                        <?php elseif ($aff['statut'] === 'suspendu'): ?>
                                                            <form action="/affectations-pedagogiques/reactivate" method="POST" class="d-inline-block" onsubmit="return confirm('<?= _('Voulez-vous réactiver cette affectation suspendue ?') ?>');">
                                                                <input type="hidden" name="id" value="<?= $aff['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-success me-1" title="<?= _('Réactiver l\'affectation') ?>"><?= _('Réactiver') ?></button>
                                                            </form>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted small">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
