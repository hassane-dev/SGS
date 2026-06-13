<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= $title ?></h2>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><?= _("Finances") ?></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Poste Comptable") ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Central Search -->
            <div class="col-md-12 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-7">
                                <h4 class="mb-1"><?= _("Recherche de Classe") ?></h4>
                                <p class="text-muted mb-3"><?= _("Saisissez le nom d'une classe pour accéder à son dashboard (ex: Terminale A4 1)") ?></p>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white border-end-0"><i class="ph-duotone ph-magnifying-glass fs-4"></i></span>
                                    <input type="text" id="classSearchInput" class="form-control border-start-0" placeholder="<?= _("Rechercher une classe...") ?>" autocomplete="off">
                                </div>
                                <div id="searchSuggestions" class="list-group shadow-sm mt-1 position-absolute w-100 z-3 d-none" style="max-width: 95%;"></div>

                                <div class="mt-3 d-flex gap-2">
                                    <select id="selectCycle" class="form-select form-select-sm">
                                        <option value=""><?= _("Cycle") ?></option>
                                        <?php foreach ($cycles as $c): ?>
                                            <option value="<?= $c['id_cycle'] ?>"><?= htmlspecialchars($c['nom_cycle']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select id="selectNiveau" class="form-select form-select-sm" disabled>
                                        <option value=""><?= _("Niveau") ?></option>
                                    </select>
                                    <select id="selectClasse" class="form-select form-select-sm" disabled>
                                        <option value=""><?= _("Classe") ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="row text-center mt-3 mt-md-0">
                                    <div class="col-6">
                                        <h6 class="text-muted mb-1"><?= _("Total Encaissé") ?></h6>
                                        <h4 class="mb-0"><?= number_format($totalGlobal, 0, ',', ' ') ?> <small>FCFA</small></h4>
                                    </div>
                                    <div class="col-6">
                                        <h6 class="text-muted mb-1"><?= _("Aujourd'hui") ?></h6>
                                        <h4 class="text-primary mb-0"><?= number_format($totalToday, 0, ',', ' ') ?> <small>FCFA</small></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Class Dashboard Area -->
        <div id="classDashboardContainer">
            <div class="text-center py-5">
                <i class="ph-duotone ph-student fs-1 text-muted opacity-50"></i>
                <p class="text-muted mt-3"><?= _("Utilisez la recherche ci-dessus pour charger une classe et ses élèves.") ?></p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('classSearchInput');
    const suggestions = document.getElementById('searchSuggestions');
    const dashboardContainer = document.getElementById('classDashboardContainer');

    const selectCycle = document.getElementById('selectCycle');
    const selectNiveau = document.getElementById('selectNiveau');
    const selectClasse = document.getElementById('selectClasse');

    let debounceTimer;

    // --- SEARCH LOGIC ---
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const q = this.value.trim();

        if (q.length < 2) {
            suggestions.classList.add('d-none');
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`/paiements/class/search?q=${encodeURIComponent(q)}`)
                .then(res => {
                    if (!res.ok) throw new Error('Search failed');
                    return res.json();
                })
                .then(data => {
                    suggestions.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(classe => {
                            const btn = document.createElement('button');
                            btn.className = 'list-group-item list-group-item-action border-0';
                            const name = `${classe.niveau} ${classe.serie || ''} ${classe.numero || ''}`.trim();
                            btn.innerHTML = `<i class="ph-duotone ph-chalkboard-teacher me-2 text-primary"></i> ${name}`;
                            btn.onclick = () => loadClassDashboard(classe.id_classe);
                            suggestions.appendChild(btn);
                        });
                        suggestions.classList.remove('d-none');
                    } else {
                        suggestions.classList.add('d-none');
                    }
                })
                .catch(err => {
                    console.error(err);
                    suggestions.innerHTML = `<div class="list-group-item text-danger small"><i class="ph-duotone ph-warning-circle me-1"></i> Erreur lors de la recherche</div>`;
                    suggestions.classList.remove('d-none');
                });
        }, 300);
    });

    function loadClassDashboard(classId) {
        suggestions.classList.add('d-none');
        searchInput.value = '';

        dashboardContainer.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2"><?= _("Chargement de la classe...") ?></p></div>';

        fetch(`/paiements/class/${classId}/dashboard`)
            .then(res => res.text())
            .then(html => {
                dashboardContainer.innerHTML = html;
                initializeDashboardActions();
            });
    }

    function initializeDashboardActions() {
        // Quick pay buttons
        document.querySelectorAll('.btn-quick-pay').forEach(btn => {
            btn.onclick = function() {
                const eleveId = this.dataset.eleveId;
                const montant = this.dataset.montant;
                const mois = this.dataset.mois;

                if (!confirm(`Confirmer le paiement de ${montant} FCFA pour ${mois} ?`)) return;

                const originalHtml = this.innerHTML;
                this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                this.disabled = true;

                const formData = new FormData();
                formData.append('eleve_id', eleveId);
                formData.append('montant', montant);
                formData.append('mois', mois);

                fetch('/paiements/pay', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Re-load dashboard to refresh statuses
                        const currentClassId = document.getElementById('currentClassId').value;
                        loadClassDashboard(currentClassId);
                    } else {
                        alert(data.message);
                        this.innerHTML = originalHtml;
                        this.disabled = false;
                    }
                });
            };
        });
    }

    // --- GUIDED SELECTION LOGIC ---
    selectCycle.addEventListener('change', function() {
        const cycleId = this.value;
        selectNiveau.innerHTML = '<option value=""><?= _("Niveau") ?></option>';
        selectClasse.innerHTML = '<option value=""><?= _("Classe") ?></option>';
        selectClasse.disabled = true;

        if (!cycleId) {
            selectNiveau.disabled = true;
            return;
        }

        fetch(`/classes/get-niveaux?cycle_id=${cycleId}`)
            .then(res => res.json())
            .then(data => {
                data.forEach(n => {
                    const opt = document.createElement('option');
                    opt.value = n;
                    opt.textContent = n;
                    selectNiveau.appendChild(opt);
                });
                selectNiveau.disabled = false;
            });
    });

    selectNiveau.addEventListener('change', function() {
        const niveau = this.value;
        const cycleId = selectCycle.value;
        selectClasse.innerHTML = '<option value=""><?= _("Classe") ?></option>';

        if (!niveau) {
            selectClasse.disabled = true;
            return;
        }

        fetch(`/paiements/class/search?q=${encodeURIComponent(niveau)}`)
            .then(res => res.json())
            .then(data => {
                data.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id_classe;
                    opt.textContent = `${c.niveau} ${c.serie || ''} ${c.numero || ''}`.trim();
                    selectClasse.appendChild(opt);
                });
                selectClasse.disabled = false;
            });
    });

    selectClasse.addEventListener('change', function() {
        if (this.value) loadClassDashboard(this.value);
    });

    // Hide suggestions on click outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestions.contains(e.target)) {
            suggestions.classList.add('d-none');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
