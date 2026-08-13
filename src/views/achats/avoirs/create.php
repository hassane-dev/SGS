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
                            <h5 class="m-b-10"><?= _("Créer un Avoir Fournisseur") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="/achats/factures"><?= _("Factures") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Créer un avoir") ?></li>
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
                        <h5 class="mb-0"><i class="ph-duotone ph-arrow-counter-clockwise me-2 text-danger"></i><?= _("Émission d'un avoir sur facture") ?></h5>
                    </div>
                    <div class="card-body">
                        <!-- Invoice Info -->
                        <div class="alert alert-light-danger border-0 mb-4 text-dark small">
                            <strong><?= _("Facture d'origine :") ?></strong> N° <?= htmlspecialchars($facture['reference_facture']) ?><br>
                            <strong><?= _("Montant total d'origine :") ?></strong> <?= number_format($facture['montant_ttc'], 2, ',', ' ') ?> FCFA<br>
                        </div>

                        <form action="/achats/avoirs/create" method="POST" id="avoirForm">
                            <input type="hidden" name="facture_id" value="<?= $facture['id'] ?>">
                            <input type="hidden" name="fournisseur_id" value="<?= $facture['fournisseur_id'] ?>">

                            <div class="table-responsive mb-4">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th><?= _("Article / Service") ?></th>
                                            <th class="text-end" style="width: 15%;"><?= _("Qté Facturée") ?></th>
                                            <th style="width: 15%;"><?= _("Qté Avoir") ?> <span class="text-danger">*</span></th>
                                            <th style="width: 20%;"><?= _("P.U. Avoir (HT)") ?> <span class="text-danger">*</span></th>
                                            <th class="text-end" style="width: 20%;"><?= _("Total TTC Ligne") ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($lignes as $index => $ligne): ?>
                                            <tr class="avoir-row">
                                                <input type="hidden" name="facture_ligne_ids[]" value="<?= $ligne['id'] ?>">
                                                <input type="hidden" name="taux_tva_avoir[]" class="tva-input" value="<?= htmlspecialchars($ligne['taux_tva_facture'] ?? '0.0000') ?>">
                                                <td>
                                                    <span class="d-block font-weight-bold text-dark"><?= htmlspecialchars($ligne['article_libelle'] ?? _("Article rattaché")) ?></span>
                                                    <small class="text-muted"><?= htmlspecialchars($ligne['article_ref'] ?? '') ?></small>
                                                </td>
                                                <td class="text-end font-weight-bold">
                                                    <?= number_format($ligne['quantite_facturee'], 2, ',', ' ') ?>
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" name="quantites_avoir[]" class="form-control qty-avoir-input" value="0.00" max="<?= htmlspecialchars($ligne['quantite_facturee']) ?>" min="0.00" required>
                                                </td>
                                                <td>
                                                    <div class="input-group">
                                                        <input type="number" step="0.01" name="prix_unitaires_avoir[]" class="form-control price-avoir-input" value="<?= htmlspecialchars($ligne['prix_unitaire_facture']) ?>" required readonly>
                                                        <span class="input-group-text">FCFA</span>
                                                    </div>
                                                </td>
                                                <td class="text-end font-weight-bold text-danger ttc-cell">
                                                    0,00 FCFA
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Live Totals Summary -->
                            <div class="row justify-content-end mb-4">
                                <div class="col-md-5">
                                    <div class="p-3 bg-light rounded text-dark">
                                        <div class="d-flex justify-content-between font-weight-bold h5 mb-0 text-danger">
                                            <span><?= _("Total Avoir TTC :") ?></span>
                                            <span id="totalAvoirTTC">0,00 FCFA</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="/achats/factures" class="btn btn-light-secondary"><?= _("Annuler") ?></a>
                                <button type="submit" class="btn btn-danger"><i class="ph-duotone ph-check-circle me-1"></i><?= _("Confirmer l'Avoir") ?></button>
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
    const rows = document.querySelectorAll(".avoir-row");
    const totalAvoirLabel = document.getElementById("totalAvoirTTC");

    function calculateTotals() {
        let totalTTC = 0.00;

        rows.forEach(row => {
            const qty = parseFloat(row.querySelector(".qty-avoir-input").value || 0);
            const price = parseFloat(row.querySelector(".price-avoir-input").value || 0);
            const tvaTaux = parseFloat(row.querySelector(".tva-input").value || 0);

            const ht = qty * price;
            const tva = ht * tvaTaux;
            const ttc = ht + tva;

            totalTTC += ttc;

            row.querySelector(".ttc-cell").textContent = ttc.toLocaleString("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " FCFA";
        });

        totalAvoirLabel.textContent = totalTTC.toLocaleString("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " FCFA";
    }

    rows.forEach(row => {
        row.querySelector(".qty-avoir-input").addEventListener("input", calculateTotals);
    });

    calculateTotals();
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
