<?php
require_once __DIR__ . '/../../layouts/header_able.php';
require_once __DIR__ . '/../../layouts/sidebar_able.php';

// Generate safe unique idempotency key for this payment session
$idempotencyKey = 'pay_opt_' . bin2hex(random_bytes(16));
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Régler la Facture Fournisseur") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="/achats/factures"><?= _("Factures") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Régler") ?></li>
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
            <div class="col-sm-12 col-md-8 offset-md-2">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="ph-duotone ph-wallet me-2 text-success"></i><?= _("Règlement de facture fournisseur") ?></h5>
                    </div>
                    <div class="card-body">
                        <!-- Invoice Info -->
                        <div class="alert alert-light-primary border-0 mb-4 text-dark small">
                            <strong><?= _("Facture :") ?></strong> N° <?= htmlspecialchars($facture['reference_facture']) ?><br>
                            <strong><?= _("Montant total TTC :") ?></strong> <?= number_format($facture['montant_ttc'], 2, ',', ' ') ?> FCFA<br>
                            <strong><?= _("Reste à régler :") ?></strong> <span class="text-danger font-weight-bold"><?= number_format($reste, 2, ',', ' ') ?> FCFA</span>
                        </div>

                        <form action="/achats/factures/pay" method="POST" id="paymentForm">
                            <!-- Hidden inputs for validation & security -->
                            <input type="hidden" name="facture_id" value="<?= $facture['id'] ?>">
                            <input type="hidden" name="idempotency_key" value="<?= $idempotencyKey ?>">
                            <input type="hidden" name="session_caisse_id" value="<?= $session ? $session['id'] : '' ?>">

                            <!-- Montant à payer -->
                            <div class="mb-3">
                                <label class="form-label font-weight-bold" for="montant_paye"><?= _("Montant du règlement") ?> <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="montant_paye" id="montant_paye" class="form-control" value="<?= htmlspecialchars($reste) ?>" max="<?= htmlspecialchars($reste) ?>" min="0.01" required>
                                    <span class="input-group-text">FCFA</span>
                                </div>
                            </div>

                            <!-- Mode de paiement -->
                            <div class="mb-3">
                                <label class="form-label font-weight-bold" for="mode_paiement"><?= _("Mode de règlement") ?> <span class="text-danger">*</span></label>
                                <select name="mode_paiement" id="mode_paiement" class="form-select" required>
                                    <option value="Banque"><?= _("Virement / Chèque Banque") ?></option>
                                    <option value="Espèces" <?= $session ? 'selected' : '' ?>><?= _("Espèces (Caisse)") ?></option>
                                    <option value="Mobile Money"><?= _("Mobile Money (Orange/MTN/Wave)") ?></option>
                                </select>
                            </div>

                            <!-- Compte Financier -->
                            <div class="mb-3">
                                <label class="form-label font-weight-bold" for="compte_financier_id"><?= _("Compte financier émetteur") ?> <span class="text-danger">*</span></label>
                                <select name="compte_financier_id" id="compte_financier_id" class="form-select" required>
                                    <option value=""><?= _("-- Choisir le compte source --") ?></option>
                                    <?php foreach ($comptes as $cp): ?>
                                        <option value="<?= $cp['id'] ?>">
                                            <?= htmlspecialchars($cp['nom_compte']) ?> (<?= htmlspecialchars($cp['mode_paiement']) ?> - <?= htmlspecialchars($cp['compte_comptable_numero']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Session de caisse status check -->
                            <?php if (!$session): ?>
                                <div class="alert alert-warning border-0 small mt-2" id="cashWarning" style="display: none;">
                                    <i class="ph-duotone ph-warning-circle me-1"></i>
                                    <?= _("Attention : Vous devez avoir une session de caisse active pour régler en espèces.") ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success border-0 small mt-2" id="cashSuccess" style="display: none;">
                                    <i class="ph-duotone ph-check-circle me-1"></i>
                                    <?= _("Session de caisse active détectée : ") ?> <strong><?= htmlspecialchars($session['code_session']) ?></strong>
                                </div>
                            <?php endif; ?>

                            <div class="mt-4 d-flex justify-content-end gap-2">
                                <a href="/achats/factures" class="btn btn-light-secondary"><?= _("Annuler") ?></a>
                                <button type="submit" class="btn btn-success"><i class="ph-duotone ph-check-circle me-1"></i><?= _("Confirmer le règlement") ?></button>
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
    const modeSelect = document.getElementById("mode_paiement");
    const cashWarning = document.getElementById("cashWarning");
    const cashSuccess = document.getElementById("cashSuccess");

    function toggleCashAlerts() {
        const selected = modeSelect.value;
        if (selected === "Espèces") {
            if (cashWarning) cashWarning.style.display = "block";
            if (cashSuccess) cashSuccess.style.display = "block";
        } else {
            if (cashWarning) cashWarning.style.display = "none";
            if (cashSuccess) cashSuccess.style.display = "none";
        }
    }

    modeSelect.addEventListener("change", toggleCashAlerts);
    toggleCashAlerts();
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
