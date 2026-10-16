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
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><i class="ph-duotone ph-funnel me-1"></i><?= _("Filtres Élèves") ?></h5>
                            <?php if (Auth::can('inscrire', 'eleve')): ?>
                                <a href="/inscriptions" class="btn btn-primary btn-sm"><i class="ph-duotone ph-plus me-1"></i><?= _('Nouvelle Inscription') ?></a>
                            <?php endif; ?>
                        </div>
                        <?php
                        $config = ['show_classe' => true, 'submit_label' => _('Filtrer')];
                        require __DIR__ . '/../layouts/_academic_filters.php';
                        ?>
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
                                                        <span class="badge bg-warning"><?= _('Non assigné') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php if (empty($eleve['niveau']) && Auth::can('inscrire', 'eleve')): ?>
                                                        <a href="/eleves/assign-class?eleve_id=<?= $eleve['id_eleve'] ?>" class="btn btn-sm btn-warning me-2" title="<?= _('Assigner une classe') ?>">
                                                            <i class="ph-duotone ph-user-plus me-1"></i><?= _('Assigner classe') ?>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (Auth::can('print_card', 'eleve')): ?>
                                                        <a href="/carte/generer?eleve_id=<?= $eleve['id_eleve'] ?>" class="btn btn-sm btn-light-primary me-2" title="<?= _('Imprimer la carte') ?>" target="_blank">
                                                            <i class="ph-duotone ph-cardholder"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (Auth::can('manage', 'boutique')): ?>
                                                        <a href="/boutique/achats?eleve_id=<?= $eleve['id_eleve'] ?>" class="btn btn-sm btn-light-info me-2" title="<?= _('Boutique') ?>">
                                                            <i class="ph-duotone ph-shopping-cart"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (Auth::can('view', 'paiement')): ?>
                                                        <a href="/paiements/show/<?= $eleve['id_eleve'] ?>" class="btn btn-success btn-sm" title="<?= _('Gérer les paiements') ?>"><?= _('Payer') ?></a>
                                                    <?php endif; ?>
                                                    <?php if (Auth::can('view_all', 'eleve') || Auth::can('view', 'eleve')): ?>
                                                        <a href="/eleves/details?id=<?= $eleve['id_eleve'] ?>" class="btn btn-info btn-sm ms-2" title="<?= _('Dossier complet') ?>"><?= _('Détails') ?></a>
                                                    <?php endif; ?>
                                                    <?php if (Auth::can('view_all', 'eleve') || Auth::can('edit', 'eleve')): ?>
                                                        <a href="/eleves/parcours-academique?id=<?= $eleve['id_eleve'] ?>" class="btn btn-light-warning btn-sm ms-2" title="<?= _('Suivi & Parcours Scolaire') ?>"><i class="ph-duotone ph-chart-line-up"></i></a>
                                                    <?php endif; ?>
                                                    <?php if (Auth::can('edit', 'eleve')): ?>
                                                        <a href="/eleves/parametres-financiers?id=<?= $eleve['id_eleve'] ?>" class="btn btn-warning btn-sm ms-2" title="<?= _('Paramètres financiers') ?>"><i class="ph-duotone ph-currency-dollar"></i></a>
                                                        <a href="/eleves/edit?id=<?= $eleve['id_eleve'] ?>" class="btn btn-primary btn-sm ms-2" title="<?= _('Modifier') ?>"><?= _('Modifier') ?></a>
                                                    <?php endif; ?>
                                                    <?php if (Auth::can('delete', 'eleve')): ?>
                                                        <form action="/eleves/destroy" method="POST" class="d-inline ms-2" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir radier cet élève ? Cette action est réversible.') ?>');">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="id" value="<?= $eleve['id_eleve'] ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm" title="<?= _('Radier l\'élève') ?>"><?= _('Radier') ?></button>
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

        const selectedCycleText = cycleSelect.options[cycleSelect.selectedIndex].text;
        serieContainer.style.display = selectedCycleText.toLowerCase() === 'lycée' ? 'block' : 'none';
    }

    function updateSeriesAndNumeros(niveau, selectedSerie = null, selectedNumero = null) {
        const lid = lyceeSelect.value;
        const selectedCycleText = cycleSelect.options[cycleSelect.selectedIndex].text;

        resetSelect(serieSelect, '<?= _('Toutes les séries') ?>');
        resetSelect(numeroSelect, '<?= _('Tous les numéros') ?>');

        if (!niveau || !lid) return;

        if (selectedCycleText.toLowerCase() === 'lycée') {
            fetch(`/classes/get-series?niveau=${niveau}&lycee_id=${lid}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(serie => {
                        const option = new Option(serie, serie);
                        if (serie === selectedSerie) option.selected = true;
                        serieSelect.add(option);
                    });
                    if (selectedSerie) updateNumeros(niveau, selectedSerie, selectedNumero);
                });
        }

        // Always try to update numbers if not in lycée cycle or if no serie is selected yet
        if (selectedCycleText.toLowerCase() !== 'lycée') {
            updateNumeros(niveau, null, selectedNumero);
        }
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
