<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _('Gestion des Élèves Actifs') ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Élèves') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <form method="GET" action="/eleves" class="row g-3">
                            <?php if (Auth::can('view_all_lycees', 'lycee')): ?>
                                <div class="col-md-2">
                                    <label class="form-label"><?= _('Lycée') ?></label>
                                    <select name="lycee_id" id="filter_lycee_id" class="form-select">
                                        <option value=""><?= _('Tous les lycées') ?></option>
                                        <?php foreach ($lycees as $l): ?>
                                            <option value="<?= $l['id'] ?>" <?= $filters['lycee_id'] == $l['id'] ? 'selected' : '' ?>><?= htmlspecialchars($l['nom_lycee']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-2">
                                <label class="form-label"><?= _('Cycle') ?></label>
                                <select name="cycle_id" id="filter_cycle_id" class="form-select">
                                    <option value=""><?= _('Tous les cycles') ?></option>
                                    <?php foreach ($cycles as $c): ?>
                                        <option value="<?= $c['id_cycle'] ?>" <?= $filters['cycle_id'] == $c['id_cycle'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nom_cycle']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><?= _('Niveau') ?></label>
                                <select name="niveau" id="filter_niveau" class="form-select">
                                    <option value=""><?= _('Tous les niveaux') ?></option>
                                </select>
                            </div>
                            <div class="col-md-2" id="filter_serie_container" style="display:none;">
                                <label class="form-label"><?= _('Série') ?></label>
                                <select name="serie" id="filter_serie" class="form-select">
                                    <option value=""><?= _('Toutes les séries') ?></option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><?= _('Numéro') ?></label>
                                <select name="numero" id="filter_numero" class="form-select">
                                    <option value=""><?= _('Tous les numéros') ?></option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100"><?= _('Filtrer') ?></button>
                            </div>
                        </form>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1 me-3">
                                <input type="text" id="eleveSearch" class="form-control" placeholder="<?= _('Rechercher un élève (nom, email, classe)...') ?>">
                            </div>
                            <div class="d-flex">
                                <a href="/eleves/archives" class="btn btn-secondary me-2">
                                <?= _('Archives') ?>
                            </a>
                            <a href="/eleves/create" class="btn btn-primary">
                                <?= _('Ajouter un Élève') ?>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?= _('Photo') ?></th>
                                        <th><?= _('Nom Complet') ?></th>
                                        <th><?= _('Lycée') ?></th>
                                        <th><?= _('Classe') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($eleves)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center"><?= _('Aucun élève actif ou en attente trouvé.') ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($eleves as $eleve): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($eleve['photo'])): ?>
                                                        <img src="<?= htmlspecialchars($eleve['photo']) ?>" alt="Photo de l'élève" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <img src="/assets/img/placeholder-photo.png" alt="Avatar par défaut" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?>
                                                    <?php if (!empty($eleve['identifiant_public'])): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars($eleve['identifiant_public'] ?? '') ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($eleve['nom_lycee']) ?></td>
                                                <td>
                                                    <?php if (!empty($eleve['niveau'])): ?>
                                                        <?= htmlspecialchars($eleve['niveau']) ?>
                                                        <?= !empty($eleve['serie']) ? htmlspecialchars($eleve['serie']) : '' ?>
                                                        <?= !empty($eleve['numero']) ? htmlspecialchars($eleve['numero']) : '' ?>
                                                        (<?= htmlspecialchars($eleve['nom_cycle']) ?>)
                                                    <?php else: ?>
                                                        <span class="badge bg-light-warning text-warning"><i class="ph-duotone ph-warning-circle me-1"></i><?= _('Classe non attribuée') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <div class="dropdown d-inline-block">
                                                        <button class="btn btn-sm btn-link-secondary dropdown-toggle no-caret" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="<?= _('Actions') ?>">
                                                            <i class="ph-duotone ph-dots-three-vertical fs-5"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <?php if (empty($eleve['niveau']) && Auth::can('inscrire', 'eleve')): ?>
                                                                <li>
                                                                    <a class="dropdown-item text-primary font-weight-bold" href="/eleves/assign-class?eleve_id=<?= $eleve['id_eleve'] ?>">
                                                                        <i class="ph-duotone ph-user-plus me-2"></i><?= _('Attribuer une classe') ?>
                                                                    </a>
                                                                </li>
                                                                <li><hr class="dropdown-divider"></li>
                                                            <?php endif; ?>

                                                            <li>
                                                                <a class="dropdown-item" href="/eleves/details?id=<?= $eleve['id_eleve'] ?>">
                                                                    <i class="ph-duotone ph-user-focus me-2 text-info"></i><?= _('Fiche détaillée') ?>
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="/eleves/parcours-academique?id=<?= $eleve['id_eleve'] ?>">
                                                                    <i class="ph-duotone ph-chart-line-up me-2 text-warning"></i><?= _('Suivi & Parcours') ?>
                                                                </a>
                                                            </li>

                                                            <?php if (Auth::can('view', 'paiement')): ?>
                                                                <li>
                                                                    <a class="dropdown-item text-success" href="/paiements/show/<?= $eleve['id_eleve'] ?>">
                                                                        <i class="ph-duotone ph-currency-circle-dollar me-2"></i><?= _('Gérer les paiements') ?>
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>

                                                            <?php if (Auth::can('edit', 'eleve')): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="/eleves/parametres-financiers?id=<?= $eleve['id_eleve'] ?>">
                                                                        <i class="ph-duotone ph-sliders me-2 text-secondary"></i><?= _('Paramètres financiers') ?>
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>

                                                            <?php if (Auth::can('manage', 'boutique')): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="/boutique/achats?eleve_id=<?= $eleve['id_eleve'] ?>">
                                                                        <i class="ph-duotone ph-shopping-cart me-2 text-info"></i><?= _('Boutique') ?>
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>

                                                            <li>
                                                                <a class="dropdown-item" href="/carte/generer?eleve_id=<?= $eleve['id_eleve'] ?>" target="_blank">
                                                                    <i class="ph-duotone ph-cardholder me-2 text-primary"></i><?= _('Imprimer la carte') ?>
                                                                </a>
                                                            </li>

                                                            <?php if (Auth::can('edit', 'eleve')): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="/eleves/edit?id=<?= $eleve['id_eleve'] ?>">
                                                                        <i class="ph-duotone ph-pencil me-2 text-warning"></i><?= _('Modifier') ?>
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>

                                                            <?php if (Auth::can('delete', 'eleve')): ?>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <form action="/eleves/destroy" method="POST" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir radier cet élève ? Cette action est réversible.') ?>');">
                                                                        <?= csrf_field() ?>
                                                                        <input type="hidden" name="id" value="<?= $eleve['id_eleve'] ?>">
                                                                        <button type="submit" class="dropdown-item text-danger">
                                                                            <i class="ph-duotone ph-user-minus me-2"></i><?= _('Radier l\'élève') ?>
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
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
<!-- [ Main Content ] end -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    document.getElementById('eleveSearch').addEventListener('keyup', function() {
        let value = this.value.toLowerCase();
        let rows = document.querySelectorAll('table tbody tr');

        rows.forEach(row => {
            if (row.cells.length > 1) { // Skip "No students found" row
                let text = row.innerText.toLowerCase();
                row.style.display = text.includes(value) ? '' : 'none';
            }
        });
    });

    // Filtering functionality
    const lyceeSelect = document.getElementById('filter_lycee_id') || { value: <?= Auth::getLyceeId() ?: 'null' ?> };
    const cycleSelect = document.getElementById('filter_cycle_id');
    const niveauSelect = document.getElementById('filter_niveau');
    const serieContainer = document.getElementById('filter_serie_container');
    const serieSelect = document.getElementById('filter_serie');
    const numeroSelect = document.getElementById('filter_numero');

    const currentFilters = <?= json_encode($filters) ?>;

    function resetSelect(select, defaultOptionText) {
        select.innerHTML = `<option value="">${defaultOptionText}</option>`;
    }

    function updateNiveaux(cycleId, selectedNiveau = null) {
        const lid = lyceeSelect.value;
        if (!lid || !cycleId) {
            resetSelect(niveauSelect, '<?= _('Tous les niveaux') ?>');
            return;
        }

        fetch(`/classes/get-niveaux?cycle_id=${cycleId}&lycee_id=${lid}`)
            .then(response => response.json())
            .then(data => {
                resetSelect(niveauSelect, '<?= _('Tous les niveaux') ?>');
                data.forEach(niveau => {
                    const option = new Option(niveau, niveau);
                    if (niveau === selectedNiveau) option.selected = true;
                    niveauSelect.add(option);
                });
                if (selectedNiveau) updateSeriesAndNumeros(selectedNiveau, currentFilters.serie, currentFilters.numero);
            });
    }

    function updateSeriesAndNumeros(niveau, selectedSerie = null, selectedNumero = null) {
        const lid = lyceeSelect.value;
        const cycleId = cycleSelect.value;

        resetSelect(serieSelect, '<?= _('Toutes les séries') ?>');
        resetSelect(numeroSelect, '<?= _('Tous les numéros') ?>');

        if (!niveau || !lid || !cycleId) return;

        fetch(`/classes/get-series?niveau=${encodeURIComponent(niveau)}&cycle_id=${cycleId}&lycee_id=${lid}`)
            .then(response => response.json())
            .then(series => {
                if (series && series.length > 0) {
                    serieContainer.style.display = 'block';
                    series.forEach(serie => {
                        const option = new Option(serie, serie);
                        if (serie === selectedSerie) option.selected = true;
                        serieSelect.add(option);
                    });
                    if (selectedSerie) updateNumeros(niveau, selectedSerie, selectedNumero);
                } else {
                    serieContainer.style.display = 'none';
                    updateNumeros(niveau, null, selectedNumero);
                }
            });
    }

    function updateNumeros(niveau, serie, selectedNumero = null) {
        const lid = lyceeSelect.value;
        if (!niveau || !lid) return;

        let url = `/classes/get-numeros?niveau=${niveau}&lycee_id=${lid}`;
        if (serie) url += `&serie=${serie}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                resetSelect(numeroSelect, '<?= _('Tous les numéros') ?>');
                data.forEach(numero => {
                    const option = new Option(numero, numero);
                    if (numero == selectedNumero) option.selected = true;
                    numeroSelect.add(option);
                });
            });
    }

    if (cycleSelect) {
        cycleSelect.addEventListener('change', function() {
            updateNiveaux(this.value);
        });
    }

    if (niveauSelect) {
        niveauSelect.addEventListener('change', function() {
            updateSeriesAndNumeros(this.value);
        });
    }

    if (serieSelect) {
        serieSelect.addEventListener('change', function() {
            updateNumeros(niveauSelect.value, this.value);
        });
    }

    if (lyceeSelect && lyceeSelect.addEventListener) {
        lyceeSelect.addEventListener('change', function() {
             if (cycleSelect.value) updateNiveaux(cycleSelect.value);
        });
    }

    // Initial load based on current filters
    if (currentFilters.cycle_id) {
        updateNiveaux(currentFilters.cycle_id, currentFilters.niveau);
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
