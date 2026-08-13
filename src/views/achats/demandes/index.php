<?php
require_once __DIR__ . '/../../layouts/header_able.php';
require_once __DIR__ . '/../../layouts/sidebar_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Demandes d'Achats") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="#!"><?= _("Achats & Fournisseurs") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Demandes d'achats") ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- Alert messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-check-circle me-1"></i>
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-warning-circle me-1"></i>
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-sm-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                        <h5 class="mb-0"><i class="ph-duotone ph-clipboard-text me-2 text-primary"></i><?= _("Suivi des Demandes d'Achats (DA)") ?></h5>
                        <?php if (Auth::can('create', 'achat_demande')): ?>
                            <a href="/achats/demandes/create" class="btn btn-primary d-inline-flex align-items-center">
                                <i class="ph-duotone ph-plus-circle me-2"></i><?= _("Créer une DA") ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-8 col-sm-12">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ph-duotone ph-magnifying-glass"></i></span>
                                    <input type="text" id="searchInput" class="form-control" placeholder="<?= _("Rechercher par demandeur, justification...") ?>">
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-12">
                                <select id="statusFilter" class="form-select">
                                    <option value="all"><?= _("Tous les statuts") ?></option>
                                    <option value="en_attente_approbation"><?= _("En attente d'approbation") ?></option>
                                    <option value="approuvee"><?= _("Approuvée / Réservée") ?></option>
                                    <option value="rejete"><?= _("Rejetée") ?></option>
                                    <option value="annule"><?= _("Annulée") ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="demandesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _("Code DA") ?></th>
                                        <th><?= _("Date demande") ?></th>
                                        <th><?= _("Demandeur") ?></th>
                                        <th><?= _("Justification") ?></th>
                                        <th><?= _("Approbateur") ?></th>
                                        <th class="text-center"><?= _("Statut") ?></th>
                                        <th class="text-center d-print-none"><?= _("Actions") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($demandes)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <i class="ph-duotone ph-folder-open fs-1 d-block mb-2"></i>
                                                <?= _("Aucune demande d'achat enregistrée.") ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($demandes as $d): ?>
                                            <tr class="demande-row" data-search="<?= htmlspecialchars(strtolower($d['demandeur_nom'] . ' ' . $d['demandeur_prenom'] . ' ' . $d['justification'])) ?>" data-status="<?= htmlspecialchars($d['statut']) ?>">
                                                <td><span class="badge bg-light-primary text-primary">DA-<?= str_pad($d['id'], 5, '0', STR_PAD_LEFT) ?></span></td>
                                                <td><?= date('d/m/Y', strtotime($d['date_demande'])) ?></td>
                                                <td>
                                                    <span class="d-block h6 mb-0"><?= htmlspecialchars($d['demandeur_prenom'] . ' ' . $d['demandeur_nom']) ?></span>
                                                </td>
                                                <td>
                                                    <span class="d-block text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($d['justification']) ?>">
                                                        <?= htmlspecialchars($d['justification']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($d['approuve_par']): ?>
                                                        <span class="small d-block text-dark"><?= htmlspecialchars($d['approbateur_prenom'] . ' ' . $d['approbateur_nom']) ?></span>
                                                        <span class="small text-muted"><?= date('d/m/Y', strtotime($d['date_approbation'])) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted small">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($d['statut'] === 'en_attente_approbation'): ?>
                                                        <span class="badge bg-light-warning text-warning"><i class="ph-duotone ph-clock me-1"></i><?= _("À approuver") ?></span>
                                                    <?php elseif ($d['statut'] === 'approuvee'): ?>
                                                        <span class="badge bg-light-success text-success"><i class="ph-duotone ph-check-circle me-1"></i><?= _("Approuvée / Réservée") ?></span>
                                                    <?php elseif ($d['statut'] === 'rejete'): ?>
                                                        <span class="badge bg-light-danger text-danger"><i class="ph-duotone ph-x-circle me-1"></i><?= _("Rejetée") ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-secondary text-secondary"><?= htmlspecialchars($d['statut']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center d-print-none">
                                                    <?php if ($d['statut'] === 'en_attente_approbation' && Auth::can('approve', 'achat_demande')): ?>
                                                        <a href="/achats/demandes/approve?id=<?= $d['id'] ?>" class="btn btn-sm btn-warning">
                                                            <i class="ph-duotone ph-shield-check me-1"></i><?= _("Traiter") ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="/achats/demandes/approve?id=<?= $d['id'] ?>" class="btn btn-sm btn-light-secondary">
                                                            <i class="ph-duotone ph-eye me-1"></i><?= _("Consulter") ?>
                                                        </a>
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
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("searchInput");
    const statusFilter = document.getElementById("statusFilter");
    const rows = document.querySelectorAll(".demande-row");

    function filterTable() {
        const query = searchInput.value.toLowerCase().trim();
        const status = statusFilter.value;

        rows.forEach(row => {
            const searchText = row.getAttribute("data-search");
            const rowStatus = row.getAttribute("data-status");

            const matchesSearch = searchText.includes(query);
            const matchesStatus = (status === "all") || (rowStatus === status);

            if (matchesSearch && matchesStatus) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    }

    if (searchInput) searchInput.addEventListener("input", filterTable);
    if (statusFilter) statusFilter.addEventListener("change", filterTable);
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
