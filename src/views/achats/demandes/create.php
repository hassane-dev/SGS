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
                            <h5 class="m-b-10"><?= _("Créer une Demande d'Achat") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="/achats/demandes"><?= _("Demandes d'achats") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Nouvelle DA") ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- Alert messages -->
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
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="ph-duotone ph-plus-circle me-2 text-primary"></i><?= _("Nouvelle Demande d'Achat") ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="/achats/demandes/create" method="POST" id="daForm">
                            <!-- Justification -->
                            <div class="mb-4">
                                <label class="form-label font-weight-bold" for="justification"><?= _("Justification / Motif de la demande") ?> <span class="text-danger">*</span></label>
                                <textarea name="justification" id="justification" rows="3" class="form-control" placeholder="<?= _("Veuillez justifier l'achat demandé (ex: Besoin de ramettes pour les compositions de fin de trimestre...)") ?>" required></textarea>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6><i class="ph-duotone ph-list-numbers me-2 text-primary"></i><?= _("Lignes d'articles demandés") ?></h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="addLineButton">
                                    <i class="ph-duotone ph-plus me-1"></i><?= _("Ajouter une ligne") ?>
                                </button>
                            </div>

                            <!-- Lines Table -->
                            <div class="table-responsive mb-4">
                                <table class="table table-bordered align-middle" id="linesTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 35%;"><?= _("Article / Service") ?> <span class="text-danger">*</span></th>
                                            <th style="width: 15%;"><?= _("Quantité") ?> <span class="text-danger">*</span></th>
                                            <th style="width: 20%;"><?= _("Prix unitaire estimé (HT)") ?> <span class="text-danger">*</span></th>
                                            <th style="width: 25%;"><?= _("Imputation Budgétaire (Optionnel)") ?></th>
                                            <th style="width: 5%;" class="text-center"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="linesContainer">
                                        <!-- Initial Row -->
                                        <tr class="da-line-row">
                                            <td>
                                                <select name="articles[]" class="form-select article-select" required>
                                                    <option value=""><?= _("-- Choisir un article --") ?></option>
                                                    <?php foreach ($articles as $art): ?>
                                                        <option value="<?= $art['id'] ?>" data-price="<?= $art['prix_unitaire_estime'] ?>">
                                                            <?= htmlspecialchars($art['reference']) ?> - <?= htmlspecialchars($art['libelle']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" name="quantites[]" class="form-control qty-input" value="1.00" min="0.01" required>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <input type="number" step="0.01" name="prix_unitaires[]" class="form-control price-input" value="0.00" min="0.00" required>
                                                    <span class="input-group-text">FCFA</span>
                                                </div>
                                            </td>
                                            <td>
                                                <select name="budget_lignes[]" class="form-select">
                                                    <option value=""><?= _("-- Sans budget --") ?></option>
                                                    <?php foreach ($budget_lignes as $bl): ?>
                                                        <option value="<?= $bl['id'] ?>">
                                                            <?= htmlspecialchars($bl['cat_libelle']) ?> (Alloc: <?= number_format($bl['allocation_initiale'], 2, ',', ' ') ?> FCFA)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-light-danger remove-line-btn" disabled>
                                                    <i class="ph-duotone ph-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="/achats/demandes" class="btn btn-light-secondary"><?= _("Annuler") ?></a>
                                <button type="submit" class="btn btn-primary"><?= _("Soumettre la demande d'achat") ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const linesContainer = document.getElementById("linesContainer");
    const addLineButton = document.getElementById("addLineButton");

    // Add row
    addLineButton.addEventListener("click", function() {
        const firstRow = document.querySelector(".da-line-row");
        const newRow = firstRow.cloneNode(true);

        // Reset inputs
        newRow.querySelector(".article-select").value = "";
        newRow.querySelector(".qty-input").value = "1.00";
        newRow.querySelector(".price-input").value = "0.00";
        newRow.querySelector("select[name='budget_lignes[]']").value = "";

        // Enable remove button
        const removeBtn = newRow.querySelector(".remove-line-btn");
        removeBtn.disabled = false;

        linesContainer.appendChild(newRow);
        bindRowEvents(newRow);
        updateRemoveButtons();
    });

    // Remove row event delegation
    linesContainer.addEventListener("click", function(e) {
        const removeBtn = e.target.closest(".remove-line-btn");
        if (removeBtn && !removeBtn.disabled) {
            const row = removeBtn.closest(".da-line-row");
            row.remove();
            updateRemoveButtons();
        }
    });

    // Auto set price from selected article
    function bindRowEvents(row) {
        const select = row.querySelector(".article-select");
        const priceInput = row.querySelector(".price-input");

        select.addEventListener("change", function() {
            const selectedOpt = select.options[select.selectedIndex];
            if (selectedOpt && selectedOpt.value) {
                const price = selectedOpt.getAttribute("data-price");
                priceInput.value = parseFloat(price || 0).toFixed(2);
            }
        });
    }

    function updateRemoveButtons() {
        const rows = document.querySelectorAll(".da-line-row");
        rows.forEach(row => {
            const removeBtn = row.querySelector(".remove-line-btn");
            removeBtn.disabled = (rows.length === 1);
        });
    }

    // Bind events to first row
    document.querySelectorAll(".da-line-row").forEach(bindRowEvents);
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
