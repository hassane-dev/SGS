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
                            <h5 class="m-b-10"><?= _("Gestion des Fournisseurs") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="#!"><?= _("Achats & Fournisseurs") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Fournisseurs") ?></li>
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
                        <h5 class="mb-0"><i class="ph-duotone ph-truck me-2 text-primary"></i><?= _("Fiches Fournisseurs") ?></h5>
                        <?php if (Auth::can('manage', 'fournisseur')): ?>
                            <a href="/achats/fournisseurs/create" class="btn btn-primary d-inline-flex align-items-center">
                                <i class="ph-duotone ph-plus-circle me-2"></i><?= _("Nouveau fournisseur") ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-8 col-sm-12">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ph-duotone ph-magnifying-glass"></i></span>
                                    <input type="text" id="searchInput" class="form-control" placeholder="<?= _("Rechercher par raison sociale, code, NIF/RCCM...") ?>">
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-12">
                                <select id="statusFilter" class="form-select">
                                    <option value="all"><?= _("Tous les statuts") ?></option>
                                    <option value="1"><?= _("Actifs uniquement") ?></option>
                                    <option value="0"><?= _("Inactifs uniquement") ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Suppliers Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="fournisseursTable">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _("Raison Sociale") ?></th>
                                        <th><?= _("Code") ?></th>
                                        <th><?= _("NIF / RCCM") ?></th>
                                        <th><?= _("Compte OHADA") ?></th>
                                        <th><?= _("Contact") ?></th>
                                        <th class="text-end"><?= _("Total Facturé") ?></th>
                                        <th class="text-end"><?= _("Reste à Payer") ?></th>
                                        <th class="text-center"><?= _("Statut") ?></th>
                                        <th class="text-center d-print-none"><?= _("Actions") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($fournisseurs)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4 text-muted">
                                                <i class="ph-duotone ph-folder-open fs-1 d-block mb-2"></i>
                                                <?= _("Aucun fournisseur trouvé.") ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($fournisseurs as $f):
                                            $metrics = Fournisseur::getMetrics($f['id']);
                                        ?>
                                            <tr class="fournisseur-row" data-search="<?= htmlspecialchars(strtolower($f['raison_sociale'] . ' ' . $f['code_fournisseur'] . ' ' . $f['nif'] . ' ' . $f['rccm'])) ?>" data-active="<?= $f['actif'] ?>">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avtar avtar-s bg-light-primary text-primary me-2">
                                                            <i class="ph-duotone ph-buildings fs-5"></i>
                                                        </div>
                                                        <div>
                                                            <span class="d-block h6 mb-0"><?= htmlspecialchars($f['raison_sociale']) ?></span>
                                                            <span class="text-muted small"><?= htmlspecialchars($f['email'] ?? _("Pas d'email")) ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><code class="text-dark font-weight-bold"><?= htmlspecialchars($f['code_fournisseur']) ?></code></td>
                                                <td>
                                                    <span class="d-block small"><strong>NIF:</strong> <?= htmlspecialchars($f['nif'] ?: '-') ?></span>
                                                    <span class="d-block small text-muted"><strong>RCCM:</strong> <?= htmlspecialchars($f['rccm'] ?: '-') ?></span>
                                                </td>
                                                <td><span class="badge bg-light-secondary text-secondary"><?= htmlspecialchars($f['compte_comptable_tiers'] ?: '401100') ?></span></td>
                                                <td>
                                                    <span class="d-block small"><?= htmlspecialchars($f['contact_nom'] ?: '-') ?></span>
                                                    <span class="text-muted small"><?= htmlspecialchars($f['telephone'] ?: '-') ?></span>
                                                </td>
                                                <td class="text-end font-weight-bold"><?= number_format($metrics['total_facture'], 2, ',', ' ') ?> FCFA</td>
                                                <td class="text-end text-danger font-weight-bold"><?= number_format($metrics['reste_a_payer'], 2, ',', ' ') ?> FCFA</td>
                                                <td class="text-center">
                                                    <?php if ($f['actif']): ?>
                                                        <span class="badge bg-light-success text-success"><i class="ph-duotone ph-check-circle me-1"></i><?= _("Actif") ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-danger text-danger"><i class="ph-duotone ph-x-circle me-1"></i><?= _("Inactif") ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center d-print-none">
                                                    <div class="dropdown d-inline-block">
                                                        <button class="btn btn-sm btn-link-secondary dropdown-toggle no-caret" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ph-duotone ph-dots-three-vertical fs-5"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item" href="/achats/fournisseurs/show?id=<?= $f['id'] ?>">
                                                                    <i class="ph-duotone ph-eye me-2 text-primary"></i><?= _("Fiche détaillée") ?>
                                                                </a>
                                                            </li>
                                                            <?php if (Auth::can('manage', 'fournisseur')): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="/achats/fournisseurs/edit?id=<?= $f['id'] ?>">
                                                                        <i class="ph-duotone ph-pencil me-2 text-warning"></i><?= _("Modifier") ?>
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="/achats/fournisseurs/toggle?id=<?= $f['id'] ?>">
                                                                        <i class="ph-duotone ph-power me-2 <?= $f['actif'] ? 'text-danger' : 'text-success' ?>"></i>
                                                                        <?= $f['actif'] ? _("Désactiver") : _("Activer") ?>
                                                                    </a>
                                                                </li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <a class="dropdown-item text-danger" href="/achats/fournisseurs/delete?id=<?= $f['id'] ?>" onclick="return confirm('<?= _("Êtes-vous sûr de vouloir supprimer ce fournisseur ? S\'il est lié à des documents, il sera désactivé.") ?>');">
                                                                        <i class="ph-duotone ph-trash me-2"></i><?= _("Supprimer") ?>
                                                                    </a>
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

<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("searchInput");
    const statusFilter = document.getElementById("statusFilter");
    const rows = document.querySelectorAll(".fournisseur-row");

    function filterTable() {
        const query = searchInput.value.toLowerCase().trim();
        const status = statusFilter.value;

        rows.forEach(row => {
            const searchText = row.getAttribute("data-search");
            const activeStatus = row.getAttribute("data-active");

            const matchesSearch = searchText.includes(query);
            const matchesStatus = (status === "all") || (activeStatus === status);

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
