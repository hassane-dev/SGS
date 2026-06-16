<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Gestion des Mensualités') ?></h2>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/paiements"><?= _('Comptabilité') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Mensualités') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Sélectionner une classe') ?></h5>
                    </div>
                    <div class="card-body">
                        <form id="filterForm" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label"><?= _('Cycle') ?></label>
                                <select id="filter_cycle_id" class="form-select">
                                    <option value=""><?= _('Choisir un cycle') ?></option>
                                    <?php foreach ($cycles as $c): ?>
                                        <option value="<?= $c['id_cycle'] ?>"><?= htmlspecialchars($c['nom_cycle']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><?= _('Niveau') ?></label>
                                <select id="filter_niveau" class="form-select" disabled>
                                    <option value=""><?= _('Choisir un niveau') ?></option>
                                </select>
                            </div>
                            <div class="col-md-3" id="filter_serie_container" style="display:none;">
                                <label class="form-label"><?= _('Série') ?></label>
                                <select id="filter_serie" class="form-select">
                                    <option value=""><?= _('Choisir une série') ?></option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><?= _('Numéro/Classe') ?></label>
                                <select id="filter_numero" class="form-select" disabled>
                                    <option value=""><?= _('Choisir une classe') ?></option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="classDashboardContainer">
            <div class="alert alert-info border-0 shadow-sm">
                <i class="ph-duotone ph-info me-2"></i><?= _('Veuillez sélectionner un cycle, un niveau et une classe pour afficher le tableau de bord des paiements.') ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const lyceeId = <?= Auth::getLyceeId() ?>;
    const cycleSelect = document.getElementById('filter_cycle_id');
    const niveauSelect = document.getElementById('filter_niveau');
    const serieContainer = document.getElementById('filter_serie_container');
    const serieSelect = document.getElementById('filter_serie');
    const numeroSelect = document.getElementById('filter_numero');
    const dashboardContainer = document.getElementById('classDashboardContainer');

    function resetSelect(select, text) {
        select.innerHTML = `<option value="">${text}</option>`;
        select.disabled = true;
    }

    cycleSelect.addEventListener('change', function() {
        const cycleId = this.value;
        resetSelect(niveauSelect, '<?= _('Chargement...') ?>');
        resetSelect(numeroSelect, '<?= _('Choisir une classe') ?>');
        serieContainer.style.display = 'none';

        if (cycleId) {
            fetch(`/classes/get-niveaux?cycle_id=${cycleId}&lycee_id=${lyceeId}`)
                .then(r => r.json())
                .then(data => {
                    resetSelect(niveauSelect, '<?= _('Choisir un niveau') ?>');
                    niveauSelect.disabled = false;
                    data.forEach(n => {
                        niveauSelect.add(new Option(n, n));
                    });
                });

            const cycleText = this.options[this.selectedIndex].text.toLowerCase();
            if (cycleText.includes('lycée')) {
                serieContainer.style.display = 'block';
            }
        }
    });

    niveauSelect.addEventListener('change', function() {
        const niveau = this.value;
        const cycleText = cycleSelect.options[cycleSelect.selectedIndex].text.toLowerCase();

        resetSelect(numeroSelect, '<?= _('Chargement...') ?>');

        if (cycleText.includes('lycée')) {
            fetch(`/classes/get-series?niveau=${niveau}&lycee_id=${lyceeId}`)
                .then(r => r.json())
                .then(data => {
                    resetSelect(serieSelect, '<?= _('Choisir une série') ?>');
                    serieSelect.disabled = false;
                    data.forEach(s => {
                        serieSelect.add(new Option(s, s));
                    });
                });
        } else {
            updateNumeros(niveau, null);
        }
    });

    serieSelect.addEventListener('change', function() {
        updateNumeros(niveauSelect.value, this.value);
    });

    function updateNumeros(niveau, serie) {
        let url = `/classes/get-numeros?niveau=${niveau}&lycee_id=${lyceeId}`;
        if (serie) url += `&serie=${serie}`;

        fetch(url)
            .then(r => r.json())
            .then(data => {
                resetSelect(numeroSelect, '<?= _('Choisir une classe') ?>');
                numeroSelect.disabled = false;
                data.forEach(n => {
                    // We need to fetch the class ID somehow.
                    // Let's assume the API can return {id, numero} or we change how we fetch.
                    // For now, let's look for a way to get the class ID.
                    numeroSelect.add(new Option('Classe ' + n, n));
                });
            });
    }

    numeroSelect.addEventListener('change', function() {
        const numero = this.value;
        const niveau = niveauSelect.value;
        const serie = serieSelect.value;

        if (numero && niveau) {
            dashboardContainer.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2"><?= _('Chargement du tableau de bord...') ?></p></div>';

            // We need the actual class ID. Let's find it.
            // Simplified: we'll call a helper to get ID or change route to use params.
            // Better: update the index.php to have a findClassId route or similar.

            fetch(`/classes/show?lycee_id=${lyceeId}&niveau=${niveau}&serie=${serie}&numero=${numero}`)
            .then(r => r.json())
            .then(classe => {
                if(classe && classe.id_classe) {
                    loadDashboard(classe.id_classe);
                }
            });
        }
    });

    function loadDashboard(classeId) {
        fetch(`/mensualites/class/${classeId}`)
            .then(r => r.text())
            .then(html => {
                dashboardContainer.innerHTML = html;
            });
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
