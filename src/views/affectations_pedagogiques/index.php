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

        <?php if (empty($is_teacher_only)): ?>
        <!-- [ Filter Card - Dynamic Hierarchy Cascade ] -->
        <div class="row">
            <div class="col-12">
                <div class="card mb-3">
                    <div class="card-header py-2 bg-light">
                        <h6 class="mb-0 fw-bold"><i class="ph-duotone ph-funnel me-1"></i> <?= _('Filtres Hiérarchiques Dynamiques') ?></h6>
                    </div>
                    <div class="card-body py-3">
                        <form action="/affectations-pedagogiques" method="GET" id="filterForm" class="row g-2 align-items-end">
                            <input type="hidden" name="classe_id" id="filter_classe_id" value="<?= htmlspecialchars($filters['classe_id'] ?? '') ?>">

                            <!-- 1. Cycle -->
                            <div class="col-md-2">
                                <label class="form-label small mb-1"><?= _('Cycle') ?></label>
                                <select name="cycle_id" id="filter_cycle" class="form-select form-select-sm">
                                    <option value=""><?= _('-- Tous les cycles --') ?></option>
                                    <?php foreach ($cycles as $cy): ?>
                                        <option value="<?= $cy['id_cycle'] ?>" <?= (isset($filters['cycle_id']) && $filters['cycle_id'] == $cy['id_cycle']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cy['nom_cycle']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- 2. Niveau -->
                            <div class="col-md-2">
                                <label class="form-label small mb-1"><?= _('Niveau') ?></label>
                                <select name="niveau" id="filter_niveau" class="form-select form-select-sm" disabled>
                                    <option value=""><?= _('-- Choisir d\'abord un cycle --') ?></option>
                                </select>
                            </div>

                            <!-- 3. Série (Lycée only) -->
                            <div class="col-md-2" id="group_filter_serie" style="display: none;">
                                <label class="form-label small mb-1"><?= _('Série') ?></label>
                                <select name="serie" id="filter_serie" class="form-select form-select-sm" disabled>
                                    <option value=""><?= _('-- Toutes --') ?></option>
                                </select>
                            </div>

                            <!-- 4. Numéro / Division -->
                            <div class="col-md-2">
                                <label class="form-label small mb-1"><?= _('Numéro') ?></label>
                                <select name="numero" id="filter_numero" class="form-select form-select-sm" disabled>
                                    <option value=""><?= _('-- Tous --') ?></option>
                                </select>
                            </div>

                            <!-- 5. Matière (Inclusif: toutes les matières de classe_matieres) -->
                            <div class="col-md-2">
                                <label class="form-label small mb-1"><?= _('Matière') ?></label>
                                <select name="matiere_id" id="filter_matiere" class="form-select form-select-sm" disabled>
                                    <option value=""><?= _('-- Déterminer classe --') ?></option>
                                </select>
                            </div>

                            <!-- 6. Enseignant Éligible par Cycle -->
                            <div class="col-md-2">
                                <label class="form-label small mb-1"><?= _('Enseignant') ?></label>
                                <select name="enseignant_id" id="filter_enseignant" class="form-select form-select-sm" disabled>
                                    <option value=""><?= _('-- Choisir d\'abord un cycle --') ?></option>
                                </select>
                            </div>

                            <!-- 7. Statut -->
                            <div class="col-md-2">
                                <label class="form-label small mb-1"><?= _('Statut') ?></label>
                                <select name="statut" id="filter_statut" class="form-select form-select-sm">
                                    <option value=""><?= _('-- Tous les statuts --') ?></option>
                                    <option value="actif" <?= (isset($filters['statut']) && $filters['statut'] === 'actif') ? 'selected' : '' ?>><?= _('Actif') ?></option>
                                    <option value="suspendu" <?= (isset($filters['statut']) && $filters['statut'] === 'suspendu') ? 'selected' : '' ?>><?= _('Suspendu') ?></option>
                                    <option value="provisoire" <?= (isset($filters['statut']) && $filters['statut'] === 'provisoire') ? 'selected' : '' ?>><?= _('Provisoire') ?></option>
                                    <option value="termine" <?= (isset($filters['statut']) && $filters['statut'] === 'termine') ? 'selected' : '' ?>><?= _('Terminé') ?></option>
                                </select>
                            </div>

                            <div class="col-md-2 text-end ms-auto">
                                <button type="submit" class="btn btn-sm btn-secondary me-1"><i class="ph-duotone ph-magnifying-glass me-1"></i><?= _('Filtrer') ?></button>
                                <a href="/affectations-pedagogiques" class="btn btn-sm btn-link-secondary"><?= _('Réinitialiser') ?></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><?= !empty($is_teacher_only) ? _('Mes Affectations Pédagogiques Actives') : _('Registre des Affectations Pédagogiques') ?> (<?= htmlspecialchars($active_year['libelle'] ?? 'En cours') ?>)</h5>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterCycle = document.getElementById('filter_cycle');
    const filterNiveau = document.getElementById('filter_niveau');
    const groupFilterSerie = document.getElementById('group_filter_serie');
    const filterSerie = document.getElementById('filter_serie');
    const filterNumero = document.getElementById('filter_numero');
    const filterClasseId = document.getElementById('filter_classe_id');
    const filterMatiere = document.getElementById('filter_matiere');
    const filterEnseignant = document.getElementById('filter_enseignant');

    // Values passed from server GET request for initial state reconstruction
    const initialValues = {
        cycle_id: "<?= htmlspecialchars($filters['cycle_id'] ?? '') ?>",
        niveau: "<?= htmlspecialchars($filters['niveau'] ?? '') ?>",
        serie: "<?= htmlspecialchars($filters['serie'] ?? '') ?>",
        numero: "<?= htmlspecialchars($filters['numero'] ?? '') ?>",
        classe_id: "<?= htmlspecialchars($filters['classe_id'] ?? '') ?>",
        matiere_id: "<?= htmlspecialchars($filters['matiere_id'] ?? '') ?>",
        enseignant_id: "<?= htmlspecialchars($filters['enseignant_id'] ?? '') ?>"
    };

    function resetSelect(el, defaultText) {
        el.innerHTML = `<option value="">${defaultText}</option>`;
        el.disabled = true;
    }

    async function loadNiveaux(cycleId, selectedNiveau = '') {
        resetSelect(filterNiveau, '-- Choisir d\'abord un cycle --');
        resetSelect(filterSerie, '-- Toutes --');
        resetSelect(filterNumero, '-- Tous --');
        resetSelect(filterMatiere, '-- Déterminer classe --');
        resetSelect(filterEnseignant, '-- Choisir d\'abord un cycle --');
        filterClasseId.value = '';
        groupFilterSerie.style.display = 'none';

        if (!cycleId) return;

        // Load Teachers eligible for this Cycle
        fetch(`/affectations-pedagogiques/get-enseignants?cycle_id=${cycleId}`)
            .then(res => res.json())
            .then(teachers => {
                filterEnseignant.innerHTML = '<option value="">-- Tous les enseignants éligibles --</option>';
                filterEnseignant.disabled = false;
                teachers.forEach(t => {
                    const sel = (initialValues.enseignant_id && String(initialValues.enseignant_id) === String(t.id_user)) ? 'selected' : '';
                    filterEnseignant.innerHTML += `<option value="${t.id_user}" ${sel}>${t.prenom} ${t.nom} (${t.identifiant_public || 'ENS'})</option>`;
                });
            });

        // Load Niveaux for this Cycle
        const res = await fetch(`/affectations-pedagogiques/get-niveaux?cycle_id=${cycleId}`);
        const niveaux = await res.json();

        filterNiveau.disabled = false;
        filterNiveau.innerHTML = '<option value="">-- Tous les niveaux --</option>';
        niveaux.forEach(n => {
            const sel = (selectedNiveau && selectedNiveau === n) ? 'selected' : '';
            filterNiveau.innerHTML += `<option value="${n}" ${sel}>${n}</option>`;
        });
    }

    async function loadSeriesOrNumeros(cycleId, niveau, selectedSerie = '', selectedNumero = '') {
        resetSelect(filterSerie, '-- Toutes --');
        resetSelect(filterNumero, '-- Tous --');
        resetSelect(filterMatiere, '-- Déterminer classe --');
        filterClasseId.value = '';

        if (!niveau || !cycleId) return;

        const resSeries = await fetch(`/affectations-pedagogiques/get-series?niveau=${encodeURIComponent(niveau)}&cycle_id=${cycleId}`);
        const series = await resSeries.json();

        if (series && series.length > 0) {
            groupFilterSerie.style.display = 'block';
            filterSerie.disabled = false;
            filterSerie.innerHTML = '<option value="">-- Toutes les séries --</option>';
            series.forEach(s => {
                const sel = (selectedSerie && selectedSerie === s) ? 'selected' : '';
                filterSerie.innerHTML += `<option value="${s}" ${sel}>${s}</option>`;
            });

            if (selectedSerie) {
                await loadNumeros(cycleId, niveau, selectedSerie, selectedNumero);
            }
        } else {
            groupFilterSerie.style.display = 'none';
            await loadNumeros(cycleId, niveau, '', selectedNumero);
        }
    }

    async function loadNumeros(cycleId, niveau, serie = '', selectedNumero = '') {
        resetSelect(filterNumero, '-- Tous --');
        resetSelect(filterMatiere, '-- Déterminer classe --');
        filterClasseId.value = '';

        if (!niveau || !cycleId) return;

        const res = await fetch(`/affectations-pedagogiques/get-numeros?niveau=${encodeURIComponent(niveau)}&serie=${encodeURIComponent(serie)}&cycle_id=${cycleId}`);
        const numeros = await res.json();

        filterNumero.disabled = false;
        filterNumero.innerHTML = '<option value="">-- Tous les numéros --</option>';
        numeros.forEach(num => {
            const sel = (selectedNumero && String(selectedNumero) === String(num)) ? 'selected' : '';
            filterNumero.innerHTML += `<option value="${num}" ${sel}>${num}</option>`;
        });
    }

    async function resolveClasseAndLoadMatieres(cycleId, niveau, serie = '', numero = '', selectedMatiere = '') {
        resetSelect(filterMatiere, '-- Déterminer classe --');
        filterClasseId.value = '';

        if (!cycleId || !niveau || !numero) return;

        const resClass = await fetch(`/affectations-pedagogiques/get-classe-id?niveau=${encodeURIComponent(niveau)}&serie=${encodeURIComponent(serie)}&numero=${encodeURIComponent(numero)}&cycle_id=${cycleId}`);
        const dataClass = await resClass.json();

        if (dataClass.id_classe) {
            filterClasseId.value = dataClass.id_classe;

            // Load all subjects of this class (include_all=1 for index search)
            const resMatieres = await fetch(`/affectations-pedagogiques/get-matieres?classe_id=${dataClass.id_classe}&include_all=1`);
            const matieres = await resMatieres.json();

            filterMatiere.disabled = false;
            filterMatiere.innerHTML = '<option value="">-- Toutes les matières --</option>';
            matieres.forEach(m => {
                const sel = (selectedMatiere && String(selectedMatiere) === String(m.id_matiere)) ? 'selected' : '';
                filterMatiere.innerHTML += `<option value="${m.id_matiere}" ${sel}>${m.nom_matiere}</option>`;
            });
        }
    }

    // --- Dynamic Event Listeners ---

    filterCycle.addEventListener('change', function() {
        const cycleId = this.value;
        loadNiveaux(cycleId);
    });

    filterNiveau.addEventListener('change', function() {
        const cycleId = filterCycle.value;
        const niveau = this.value;
        loadSeriesOrNumeros(cycleId, niveau);
    });

    filterSerie.addEventListener('change', function() {
        const cycleId = filterCycle.value;
        const niveau = filterNiveau.value;
        const serie = this.value;
        loadNumeros(cycleId, niveau, serie);
    });

    filterNumero.addEventListener('change', function() {
        const cycleId = filterCycle.value;
        const niveau = filterNiveau.value;
        const serie = (groupFilterSerie.style.display !== 'none') ? filterSerie.value : '';
        const numero = this.value;
        resolveClasseAndLoadMatieres(cycleId, niveau, serie, numero);
    });

    // --- GET Rehydration Cascade Sequence ---
    (async function rehydrateFilters() {
        if (initialValues.cycle_id) {
            await loadNiveaux(initialValues.cycle_id, initialValues.niveau);
            if (initialValues.niveau) {
                await loadSeriesOrNumeros(initialValues.cycle_id, initialValues.niveau, initialValues.serie, initialValues.numero);
                if (initialValues.numero) {
                    await resolveClasseAndLoadMatieres(initialValues.cycle_id, initialValues.niveau, initialValues.serie, initialValues.numero, initialValues.matiere_id);
                }
            }
        }
    })();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
