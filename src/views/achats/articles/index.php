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
                            <h5 class="m-b-10"><?= _("Catalogue Articles & Services") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="#!"><?= _("Achats & Fournisseurs") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Catalogue") ?></li>
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
                        <h5 class="mb-0"><i class="ph-duotone ph-package me-2 text-primary"></i><?= _("Articles, Fournitures & Prestations") ?></h5>
                        <?php if (Auth::can('manage', 'achat_article')): ?>
                            <a href="/achats/articles/create" class="btn btn-primary d-inline-flex align-items-center">
                                <i class="ph-duotone ph-plus-circle me-2"></i><?= _("Ajouter un article/service") ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <!-- Search & Filter -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ph-duotone ph-magnifying-glass"></i></span>
                                    <input type="text" id="searchInput" class="form-control" placeholder="<?= _("Rechercher par libellé, référence...") ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select id="typeFilter" class="form-select">
                                    <option value="all"><?= _("Tous les types") ?></option>
                                    <option value="0"><?= _("Articles physiques") ?></option>
                                    <option value="1"><?= _("Services & Prestations") ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="articlesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _("Référence") ?></th>
                                        <th><?= _("Désignation") ?></th>
                                        <th><?= _("Catégorie") ?></th>
                                        <th><?= _("Unité") ?></th>
                                        <th class="text-end"><?= _("P.U. Estimé") ?></th>
                                        <th class="text-center"><?= _("Type") ?></th>
                                        <th class="text-center"><?= _("Statut") ?></th>
                                        <th class="text-center d-print-none"><?= _("Actions") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($articles)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                <i class="ph-duotone ph-folder-open fs-1 d-block mb-2"></i>
                                                <?= _("Aucun article dans le catalogue.") ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($articles as $art): ?>
                                            <tr class="article-row" data-search="<?= htmlspecialchars(strtolower($art['libelle'] . ' ' . $art['reference'])) ?>" data-service="<?= $art['is_service'] ?>">
                                                <td><code class="text-dark font-weight-bold"><?= htmlspecialchars($art['reference']) ?></code></td>
                                                <td>
                                                    <span class="d-block h6 mb-0"><?= htmlspecialchars($art['libelle']) ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light-secondary text-secondary"><?= htmlspecialchars($art['nom_categorie']) ?></span>
                                                    <br><small class="text-muted"><?= htmlspecialchars($art['compte_comptable_charge']) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($art['unite_mesure']) ?></td>
                                                <td class="text-end font-weight-bold"><?= number_format($art['prix_unitaire_estime'], 2, ',', ' ') ?> FCFA</td>
                                                <td class="text-center">
                                                    <?php if ($art['is_service']): ?>
                                                        <span class="badge bg-light-warning text-warning"><i class="ph-duotone ph-wrench me-1"></i><?= _("Service / Prestation") ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-info text-info"><i class="ph-duotone ph-package me-1"></i><?= _("Article physique") ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($art['actif']): ?>
                                                        <span class="badge bg-light-success text-success"><?= _("Actif") ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-danger text-danger"><?= _("Inactif") ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center d-print-none">
                                                    <?php if (Auth::can('manage', 'achat_article')): ?>
                                                        <a href="/achats/articles/edit?id=<?= $art['id'] ?>" class="btn btn-sm btn-light-warning me-1">
                                                            <i class="ph-duotone ph-pencil me-1"></i><?= _("Modifier") ?>
                                                        </a>
                                                        <a href="/achats/articles/delete?id=<?= $art['id'] ?>" class="btn btn-sm btn-light-danger" onclick="return confirm('<?= _("Voulez-vous vraiment supprimer cet article ?") ?>');">
                                                            <i class="ph-duotone ph-trash me-1"></i><?= _("Supprimer") ?>
                                                        </a>
                                                    <?php else: ?>
                                                        -
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
    const typeFilter = document.getElementById("typeFilter");
    const rows = document.querySelectorAll(".article-row");

    function filterTable() {
        const query = searchInput.value.toLowerCase().trim();
        const type = typeFilter.value;

        rows.forEach(row => {
            const searchText = row.getAttribute("data-search");
            const isService = row.getAttribute("data-service");

            const matchesSearch = searchText.includes(query);
            const matchesType = (type === "all") || (isService === type);

            if (matchesSearch && matchesType) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    }

    if (searchInput) searchInput.addEventListener("input", filterTable);
    if (typeFilter) typeFilter.addEventListener("change", filterTable);
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
