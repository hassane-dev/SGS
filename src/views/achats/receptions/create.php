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
                            <h5 class="m-b-10"><?= _("Réceptionner la Commande") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="/achats/commandes"><?= _("Bons de commande") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page">BC-<?= htmlspecialchars($commande['numero_commande']) ?></li>
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
                        <h5 class="mb-0"><i class="ph-duotone ph-download-simple me-2 text-primary"></i><?= _("Validation de la réception physique") ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-light-primary border-0 mb-4">
                            <strong><?= _("Bon de Commande :") ?></strong> <?= htmlspecialchars($commande['numero_commande']) ?><br>
                            <strong><?= _("Date :") ?></strong> <?= date('d/m/Y', strtotime($commande['date_commande'])) ?><br>
                        </div>

                        <form action="/achats/receptions/create" method="POST" id="receptionForm">
                            <input type="hidden" name="commande_id" value="<?= $commande['id'] ?>">

                            <div class="table-responsive mb-4">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th><?= _("Article / Service") ?></th>
                                            <th class="text-end" style="width: 15%;"><?= _("Commandé") ?></th>
                                            <th style="width: 15%;"><?= _("Reçu Conforme") ?> <span class="text-danger">*</span></th>
                                            <th style="width: 15%;"><?= _("Refusé / Écart") ?></th>
                                            <th style="width: 25%;"><?= _("Motif de refus") ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($lignes as $index => $ligne): ?>
                                            <tr>
                                                <input type="hidden" name="commande_ligne_ids[]" value="<?= $ligne['id'] ?>">
                                                <td>
                                                    <span class="d-block font-weight-bold text-dark"><?= htmlspecialchars($ligne['article_libelle']) ?></span>
                                                    <small class="text-muted"><?= htmlspecialchars($ligne['article_ref']) ?> - <?= htmlspecialchars($ligne['unite_mesure']) ?></small>
                                                </td>
                                                <td class="text-end font-weight-bold text-primary">
                                                    <?= number_format($ligne['quantite_commandee'], 2, ',', ' ') ?>
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" name="quantites_recues[]" class="form-control qty-rec-input" value="<?= htmlspecialchars($ligne['quantite_commandee']) ?>" max="<?= htmlspecialchars($ligne['quantite_commandee']) ?>" min="0.00" required>
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" name="quantites_refusees[]" class="form-control qty-ref-input" value="0.00" min="0.00" required>
                                                </td>
                                                <td>
                                                    <input type="text" name="motifs_refus[]" class="form-control" placeholder="<?= _("Ex: Abîmé, Non conforme...") ?>">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="/achats/commandes" class="btn btn-light-secondary"><?= _("Annuler") ?></a>
                                <button type="submit" class="btn btn-success"><i class="ph-duotone ph-check-circle me-1"></i><?= _("Valider le Bon de Réception") ?></button>
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
    const qtyRecInputs = document.querySelectorAll(".qty-rec-input");
    qtyRecInputs.forEach(input => {
        input.addEventListener("input", function() {
            const maxVal = parseFloat(input.getAttribute("max") || 0);
            const val = parseFloat(input.value || 0);
            if (val > maxVal) {
                alert("La quantité reçue ne peut excéder la quantité commandée.");
                input.value = maxVal.toFixed(2);
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
