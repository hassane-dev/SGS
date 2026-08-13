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
                            <h5 class="m-b-10"><?= _("Enregistrer la Facture (3-Way Matching)") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="/achats/factures"><?= _("Factures") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Rapprochement") ?></li>
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
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="ph-duotone ph-shield-check me-2 text-primary"></i><?= _("Validation du rapprochement Facture - Réception - Commande") ?></h5>
                    </div>
                    <div class="card-body">
                        <!-- Summary info -->
                        <div class="row g-3 mb-4 text-dark small bg-light p-3 rounded">
                            <div class="col-md-4">
                                <strong><?= _("Fournisseur :") ?></strong> <?= htmlspecialchars($fournisseur['raison_sociale']) ?>
                            </div>
                            <div class="col-md-4">
                                <strong><?= _("Bon de Commande :") ?></strong> BC-<?= htmlspecialchars($commande['numero_commande']) ?>
                            </div>
                            <div class="col-md-4">
                                <strong><?= _("Bon de Réception :") ?></strong> BR-<?= htmlspecialchars($reception['numero_reception']) ?>
                            </div>
                        </div>

                        <form action="/achats/factures/rapprochement" method="POST" id="matchingForm">
                            <!-- Hidden pointers -->
                            <input type="hidden" name="fournisseur_id" value="<?= $fournisseur['id'] ?>">
                            <input type="hidden" name="commande_id" value="<?= $commande['id'] ?>">
                            <input type="hidden" name="reception_id" value="<?= $reception['id'] ?>">

                            <div class="row g-3 mb-4">
                                <!-- Reference Facture -->
                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold" for="reference_facture"><?= _("N° de Facture Fournisseur") ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="reference_facture" id="reference_facture" class="form-control" placeholder="Ex: FACT-2023-999" required>
                                </div>

                                <!-- Date Facture -->
                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold" for="date_facture"><?= _("Date de facturation") ?> <span class="text-danger">*</span></label>
                                    <input type="date" name="date_facture" id="date_facture" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>

                                <!-- Date Echeance -->
                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold" for="date_echeance"><?= _("Date d'échéance de règlement") ?> <span class="text-danger">*</span></label>
                                    <input type="date" name="date_echeance" id="date_echeance" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                                </div>
                            </div>

                            <h6 class="mb-3 text-primary"><i class="ph-duotone ph-list-bullets me-1"></i><?= _("Rapprochement des lignes articles") ?></h6>

                            <div class="table-responsive mb-4">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th><?= _("Article / Prestation") ?></th>
                                            <th class="text-end" style="width: 12%;"><?= _("Commandé (BC)") ?></th>
                                            <th class="text-end" style="width: 12%;"><?= _("Réceptionné (BR)") ?></th>
                                            <th style="width: 12%;"><?= _("Qté Facturée") ?> <span class="text-danger">*</span></th>
                                            <th style="width: 15%;"><?= _("P.U. Facturé (HT)") ?> <span class="text-danger">*</span></th>
                                            <th style="width: 12%;"><?= _("TVA") ?></th>
                                            <th class="text-end" style="width: 15%;"><?= _("Total TTC") ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($lignes as $index => $line): ?>
                                            <tr class="matching-row">
                                                <input type="hidden" name="reception_ligne_ids[]" value="<?= $line['id'] ?>">
                                                <td>
                                                    <span class="d-block font-weight-bold"><?= htmlspecialchars($line['article_libelle']) ?></span>
                                                    <small class="text-muted"><?= htmlspecialchars($line['article_ref']) ?></small>
                                                </td>
                                                <td class="text-end"><?= number_format($line['quantite_commandee'], 2, ',', ' ') ?></td>
                                                <td class="text-end text-success font-weight-bold"><?= number_format($line['quantite_receptionnee'], 2, ',', ' ') ?></td>
                                                <td>
                                                    <!-- Quantité facturée defaults to quantity received conforming -->
                                                    <input type="number" step="0.01" name="quantites_facturees[]" class="form-control qty-fact-input" value="<?= htmlspecialchars($line['quantite_receptionnee']) ?>" max="<?= htmlspecialchars($line['quantite_receptionnee']) ?>" min="0.01" required>
                                                </td>
                                                <td>
                                                    <!-- Unit price defaults to PO price -->
                                                    <div class="input-group">
                                                        <input type="number" step="0.01" name="prix_unitaires_facturees[]" class="form-control price-fact-input" value="<?= htmlspecialchars($line['prix_unitaire_negocie']) ?>" required readonly>
                                                        <span class="input-group-text">FCFA</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <select name="taux_tva_facturees[]" class="form-select tva-select">
                                                        <option value="0.0000" selected>0%</option>
                                                        <option value="0.1800">18%</option>
                                                    </select>
                                                </td>
                                                <td class="text-end font-weight-bold text-primary ttc-cell">
                                                    0.00 FCFA
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
                                        <div class="d-flex justify-content-between mb-2">
                                            <span><?= _("Total HT :") ?></span>
                                            <span id="totalHT">0,00 FCFA</span>
                                        </div>
                                        <div class="d-flex justify-content-between font-weight-bold h5 mb-0 text-primary">
                                            <span><?= _("Total TTC :") ?></span>
                                            <span id="totalTTC">0,00 FCFA</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="/achats/receptions" class="btn btn-light-secondary"><?= _("Annuler") ?></a>
                                <button type="submit" class="btn btn-success"><i class="ph-duotone ph-check-square me-1"></i><?= _("Enregistrer & Comptabiliser la Facture") ?></button>
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
    const rows = document.querySelectorAll(".matching-row");
    const totalHTLabel = document.getElementById("totalHT");
    const totalTTCLabel = document.getElementById("totalTTC");

    function calculateTotals() {
        let totalHT = 0.00;
        let totalTTC = 0.00;

        rows.forEach(row => {
            const qty = parseFloat(row.querySelector(".qty-fact-input").value || 0);
            const price = parseFloat(row.querySelector(".price-fact-input").value || 0);
            const tvaTaux = parseFloat(row.querySelector(".tva-select").value || 0);

            const ht = qty * price;
            const tva = ht * tvaTaux;
            const ttc = ht + tva;

            totalHT += ht;
            totalTTC += ttc;

            row.querySelector(".ttc-cell").textContent = ttc.toLocaleString("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " FCFA";
        });

        totalHTLabel.textContent = totalHT.toLocaleString("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " FCFA";
        totalTTCLabel.textContent = totalTTC.toLocaleString("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " FCFA";
    }

    rows.forEach(row => {
        row.querySelector(".qty-fact-input").addEventListener("input", calculateTotals);
        row.querySelector(".tva-select").addEventListener("change", calculateTotals);
    });

    calculateTotals();
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
