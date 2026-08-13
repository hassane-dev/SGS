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
                            <h5 class="m-b-10"><?= _("Émettre un Bon de Commande") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="/achats/commandes"><?= _("Bons de commande") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Émettre un BC") ?></li>
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
                        <h5 class="mb-0"><i class="ph-duotone ph-file-plus me-2 text-primary"></i><?= _("Détails du Bon de Commande") ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="/achats/commandes/create" method="POST">
                            <!-- Hidden inputs for mapping DA -->
                            <input type="hidden" name="demande_id" value="">

                            <div class="row g-3 mb-4">
                                <!-- Supplier -->
                                <div class="col-md-6 col-sm-12">
                                    <label class="form-label font-weight-bold" for="fournisseur_id"><?= _("Fournisseur destinataire") ?> <span class="text-danger">*</span></label>
                                    <select name="fournisseur_id" id="fournisseur_id" class="form-select" required>
                                        <option value=""><?= _("-- Choisir un fournisseur actif --") ?></option>
                                        <?php foreach ($fournisseurs as $f): ?>
                                            <option value="<?= $f['id'] ?>">
                                                <?= htmlspecialchars($f['raison_sociale']) ?> (<?= htmlspecialchars($f['code_fournisseur']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6><i class="ph-duotone ph-list-numbers me-2 text-primary"></i><?= _("Articles commandés") ?></h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="addLineButton">
                                    <i class="ph-duotone ph-plus me-1"></i><?= _("Ajouter un article") ?>
                                </button>
                            </div>

                            <!-- Lines Table -->
                            <div class="table-responsive mb-4">
                                <table class="table table-bordered align-middle" id="linesTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 45%;"><?= _("Article / Prestation") ?> <span class="text-danger">*</span></th>
                                            <th style="width: 20%;"><?= _("Quantité commandée") ?> <span class="text-danger">*</span></th>
                                            <th style="width: 30%;"><?= _("Prix unitaire négocié (HT)") ?> <span class="text-danger">*</span></th>
                                            <th style="width: 5%;" class="text-center"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="linesContainer">
                                        <tr class="cmd-line-row">
                                            <!-- Dummy empty line_id for non-DA items -->
                                            <input type="hidden" name="demande_ligne_ids[]" value="">
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
                                <a href="/achats/commandes" class="btn btn-light-secondary"><?= _("Annuler") ?></a>
                                <button type="submit" class="btn btn-primary"><?= _("Émettre le bon de commande") ?></button>
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
        const firstRow = document.querySelector(".cmd-line-row");
        const newRow = firstRow.cloneNode(true);

        // Reset inputs
        newRow.querySelector(".article-select").value = "";
        newRow.querySelector(".qty-input").value = "1.00";
        newRow.querySelector(".price-input").value = "0.00";
        newRow.querySelector("input[name='demande_ligne_ids[]']").value = "";

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
            const row = removeBtn.closest(".cmd-line-row");
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
        const rows = document.querySelectorAll(".cmd-line-row");
        rows.forEach(row => {
            const removeBtn = row.querySelector(".remove-line-btn");
            removeBtn.disabled = (rows.length === 1);
        });
    }

    // Bind events to first row
    document.querySelectorAll(".cmd-line-row").forEach(bindRowEvents);
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
