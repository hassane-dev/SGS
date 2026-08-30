<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Cahier de Texte') ?></li>
                        </ul>
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= $is_admin ? _('Consultation du Cahier de Texte') : _('Mon Cahier de Texte') ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <?php if (Auth::get('role_name') === 'enseignant'): ?>
                            <a href="/cahier-texte/create" class="btn btn-primary d-inline-flex align-items-center">
                                <i class="ph-duotone ph-plus-circle me-2"></i> <?= _('Nouvelle Entrée') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Filter Card - Dynamic Hierarchy Cascade ] -->
        <div class="row">
            <div class="col-12">
                <div class="card mb-3 shadow-sm">
                    <div class="card-header py-2 bg-light">
                        <h6 class="mb-0 fw-bold"><i class="ph-duotone ph-funnel me-1"></i> <?= _('Filtres Hiérarchiques Dynamiques') ?></h6>
                    </div>
                    <div class="card-body py-3">
                        <form action="/cahier-texte" method="GET" id="filterForm" class="row g-2 align-items-end">
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

                            <!-- 6. Enseignant Éligible (Affiché si Admin/Superviseur) -->
                            <?php if ($is_admin): ?>
                            <div class="col-md-2">
                                <label class="form-label small mb-1"><?= _('Enseignant') ?></label>
                                <select name="personnel_id" id="filter_enseignant" class="form-select form-select-sm" disabled>
                                    <option value=""><?= _('-- Choisir d\'abord un cycle --') ?></option>
                                </select>
                            </div>
                            <?php endif; ?>

                            <!-- 7. Date -->
                            <div class="col-md-2">
                                <label class="form-label small mb-1"><?= _('Date') ?></label>
                                <input type="date" name="date" id="filter_date" value="<?= htmlspecialchars($filters['date_filter'] ?? '') ?>" class="form-control form-select-sm">
                            </div>

                            <div class="col-md-2 text-end ms-auto">
                                <button type="submit" class="btn btn-sm btn-info me-1"><i class="ph-duotone ph-funnel me-1"></i><?= _('Filtrer') ?></button>
                                <a href="/cahier-texte" class="btn btn-sm btn-secondary"><?= _('Effacer') ?></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5><?= _('Entrées du Cahier de Texte') ?></h5>
                    </div>
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th><?= _('Date') ?></th>
                                        <?php if ($is_admin): ?>
                                            <th><?= _('Enseignant') ?></th>
                                        <?php endif; ?>
                                        <th><?= _('Classe') ?></th>
                                        <th><?= _('Matière') ?></th>
                                        <th><?= _('Contenu') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($entries)): ?>
                                        <tr>
                                            <td colspan="<?= $is_admin ? '6' : '5' ?>" class="text-center p-5 text-muted">
                                                <i class="ph-duotone ph-info fs-1 text-muted mb-2 d-block"></i>
                                                <?= _('Aucune entrée trouvée.') ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($entries as $entry): ?>
                                            <tr>
                                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($entry['date_cours']))) ?></td>
                                                <?php if ($is_admin): ?>
                                                    <td>
                                                        <span class="fw-bold"><?= htmlspecialchars($entry['prenom_personnel'] . ' ' . $entry['nom_personnel']) ?></span>
                                                        <?php if (!empty($entry['matricule_personnel'])): ?>
                                                            <br><small class="text-muted font-monospace"><?= htmlspecialchars($entry['matricule_personnel']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                                <td><span class="badge bg-light-primary text-primary"><?= htmlspecialchars(Classe::getFormattedName($entry)) ?></span></td>
                                                <td><?= htmlspecialchars($entry['nom_matiere']) ?></td>
                                                <td class="text-truncate" style="max-width: 300px;"><?= htmlspecialchars($entry['contenu_cours']) ?></td>
                                                <td class="text-end">
                                                    <!-- Action 1: Consultation -->
                                                    <a href="/cahier-texte/show?id=<?= $entry['cahier_id'] ?>" class="btn btn-sm btn-light-info me-1" title="<?= _('Consulter la séance') ?>">
                                                        <i class="ph-duotone ph-eye"></i>
                                                    </a>
                                                    <!-- Action 2 & 3: Edition et Suppression si propriétaire ou admin -->
                                                    <?php if ($is_admin || Auth::getUserId() == $entry['personnel_id']): ?>
                                                        <a href="/cahier-texte/edit?id=<?= $entry['cahier_id'] ?>" class="btn btn-sm btn-light-primary me-1" title="<?= _('Modifier') ?>">
                                                            <i class="ph-duotone ph-pencil-simple"></i>
                                                        </a>
                                                        <form action="/cahier-texte/destroy" method="POST" class="d-inline-block" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir supprimer cette entrée ?') ?>');">
                                                            <input type="hidden" name="id" value="<?= $entry['cahier_id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-light-danger" title="<?= _('Supprimer') ?>">
                                                                <i class="ph-duotone ph-trash"></i>
                                                            </button>
                                                        </form>
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

    const isAdmin = <?= !empty($is_admin) ? 'true' : 'false' ?>;
    const teacherAssignments = <?= json_encode($teacher_assignments ?? []) ?>;

    // Values passed from server GET request for initial state reconstruction
    const initialValues = {
        cycle_id: "<?= htmlspecialchars($filters['cycle_id'] ?? '') ?>",
        niveau: "<?= htmlspecialchars($filters['niveau'] ?? '') ?>",
        serie: "<?= htmlspecialchars($filters['serie'] ?? '') ?>",
        numero: "<?= htmlspecialchars($filters['numero'] ?? '') ?>",
        classe_id: "<?= htmlspecialchars($filters['classe_id'] ?? '') ?>",
        matiere_id: "<?= htmlspecialchars($filters['matiere_id'] ?? '') ?>",
        personnel_id: "<?= htmlspecialchars($filters['personnel_id_filter'] ?? '') ?>"
    };

    function resetSelect(el, defaultText) {
        if (!el) return;
        el.innerHTML = `<option value="">${defaultText}</option>`;
        el.disabled = true;
    }

    async function loadNiveaux(cycleId, selectedNiveau = '') {
        resetSelect(filterNiveau, '-- Choisir d\'abord un cycle --');
        resetSelect(filterSerie, '-- Toutes --');
        resetSelect(filterNumero, '-- Tous --');
        resetSelect(filterMatiere, '-- Déterminer classe --');
        if (filterEnseignant) {
            resetSelect(filterEnseignant, '-- Choisir d\'abord un cycle --');
        }
        filterClasseId.value = '';
        groupFilterSerie.style.display = 'none';

        if (!cycleId && isAdmin) return;

        if (isAdmin) {
            if (filterEnseignant) {
                fetch(`/affectations-pedagogiques/get-enseignants?cycle_id=${cycleId}`)
                    .then(res => res.json())
                    .then(teachers => {
                        filterEnseignant.innerHTML = '<option value="">-- Tous les enseignants éligibles --</option>';
                        filterEnseignant.disabled = false;
                        teachers.forEach(t => {
                            const sel = (initialValues.personnel_id && String(initialValues.personnel_id) === String(t.id_user)) ? 'selected' : '';
                            filterEnseignant.innerHTML += `<option value="${t.id_user}" ${sel}>${t.prenom} ${t.nom} (${t.identifiant_public || 'ENS'})</option>`;
                        });
                    });
            }

            const res = await fetch(`/affectations-pedagogiques/get-niveaux?cycle_id=${cycleId}`);
            const niveaux = await res.json();

            filterNiveau.disabled = false;
            filterNiveau.innerHTML = '<option value="">-- Tous les niveaux --</option>';
            niveaux.forEach(n => {
                const sel = (selectedNiveau && selectedNiveau === n) ? 'selected' : '';
                filterNiveau.innerHTML += `<option value="${n}" ${sel}>${n}</option>`;
            });
        } else {
            // Teacher scoping: derive distinct niveles from teacherAssignments
            const matching = teacherAssignments.filter(ta => !cycleId || String(ta.cycle_id) === String(cycleId));
            const niveaux = [...new Set(matching.map(ta => ta.niveau).filter(Boolean))];

            filterNiveau.disabled = false;
            filterNiveau.innerHTML = '<option value="">-- Tous mes niveaux --</option>';
            niveaux.forEach(n => {
                const sel = (selectedNiveau && selectedNiveau === n) ? 'selected' : '';
                filterNiveau.innerHTML += `<option value="${n}" ${sel}>${n}</option>`;
            });
        }
    }

    async function loadSeriesOrNumeros(cycleId, niveau, selectedSerie = '', selectedNumero = '') {
        resetSelect(filterSerie, '-- Toutes --');
        resetSelect(filterNumero, '-- Tous --');
        resetSelect(filterMatiere, '-- Déterminer classe --');
        filterClasseId.value = '';

        if (!niveau && isAdmin) return;

        if (isAdmin) {
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
        } else {
            // Teacher scoping
            const matching = teacherAssignments.filter(ta =>
                (!cycleId || String(ta.cycle_id) === String(cycleId)) &&
                (!niveau || String(ta.niveau) === String(niveau))
            );
            const series = [...new Set(matching.map(ta => ta.serie).filter(s => s !== null && s !== ''))];

            if (series && series.length > 0) {
                groupFilterSerie.style.display = 'block';
                filterSerie.disabled = false;
                filterSerie.innerHTML = '<option value="">-- Toutes mes séries --</option>';
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
    }

    async function loadNumeros(cycleId, niveau, serie = '', selectedNumero = '') {
        resetSelect(filterNumero, '-- Tous --');
        resetSelect(filterMatiere, '-- Déterminer classe --');
        filterClasseId.value = '';

        if (!niveau && isAdmin) return;

        if (isAdmin) {
            const res = await fetch(`/affectations-pedagogiques/get-numeros?niveau=${encodeURIComponent(niveau)}&serie=${encodeURIComponent(serie)}&cycle_id=${cycleId}`);
            const numeros = await res.json();

            filterNumero.disabled = false;
            filterNumero.innerHTML = '<option value="">-- Tous les numéros --</option>';
            numeros.forEach(num => {
                const sel = (selectedNumero && String(selectedNumero) === String(num)) ? 'selected' : '';
                filterNumero.innerHTML += `<option value="${num}" ${sel}>${num}</option>`;
            });
        } else {
            // Teacher scoping
            const matching = teacherAssignments.filter(ta =>
                (!cycleId || String(ta.cycle_id) === String(cycleId)) &&
                (!niveau || String(ta.niveau) === String(niveau)) &&
                (!serie || String(ta.serie) === String(serie))
            );
            const numeros = [...new Set(matching.map(ta => ta.numero).filter(n => n !== null && n !== ''))];

            filterNumero.disabled = false;
            filterNumero.innerHTML = '<option value="">-- Tous mes numéros --</option>';
            numeros.forEach(num => {
                const sel = (selectedNumero && String(selectedNumero) === String(num)) ? 'selected' : '';
                filterNumero.innerHTML += `<option value="${num}" ${sel}>${num}</option>`;
            });
        }
    }

    async function resolveClasseAndLoadMatieres(cycleId, niveau, serie = '', numero = '', selectedMatiere = '') {
        resetSelect(filterMatiere, '-- Déterminer classe --');
        filterClasseId.value = '';

        if (isAdmin) {
            if (!cycleId || !niveau || !numero) return;
            const resClass = await fetch(`/affectations-pedagogiques/get-classe-id?niveau=${encodeURIComponent(niveau)}&serie=${encodeURIComponent(serie)}&numero=${encodeURIComponent(numero)}&cycle_id=${cycleId}`);
            const dataClass = await resClass.json();

            if (dataClass.id_classe) {
                filterClasseId.value = dataClass.id_classe;

                const resMatieres = await fetch(`/affectations-pedagogiques/get-matieres?classe_id=${dataClass.id_classe}&include_all=1`);
                const matieres = await resMatieres.json();

                filterMatiere.disabled = false;
                filterMatiere.innerHTML = '<option value="">-- Toutes les matières --</option>';
                matieres.forEach(m => {
                    const sel = (selectedMatiere && String(selectedMatiere) === String(m.id_matiere)) ? 'selected' : '';
                    filterMatiere.innerHTML += `<option value="${m.id_matiere}" ${sel}>${m.nom_matiere}</option>`;
                });
            }
        } else {
            // Teacher scoping
            const matching = teacherAssignments.filter(ta =>
                (!cycleId || String(ta.cycle_id) === String(cycleId)) &&
                (!niveau || String(ta.niveau) === String(niveau)) &&
                (!serie || String(ta.serie) === String(serie)) &&
                (!numero || String(ta.numero) === String(numero))
            );

            if (matching.length > 0) {
                filterClasseId.value = matching[0].id_classe;

                // Extract unique subjects taught by teacher in this class context
                const subjectMap = {};
                matching.forEach(m => {
                    if (m.id_matiere) {
                        subjectMap[m.id_matiere] = m.nom_matiere;
                    }
                });

                filterMatiere.disabled = false;
                filterMatiere.innerHTML = '<option value="">-- Toutes mes matières --</option>';
                Object.keys(subjectMap).forEach(id => {
                    const sel = (selectedMatiere && String(selectedMatiere) === String(id)) ? 'selected' : '';
                    filterMatiere.innerHTML += `<option value="${id}" ${sel}>${subjectMap[id]}</option>`;
                });
            }
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
