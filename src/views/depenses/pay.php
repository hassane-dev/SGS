<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Règlement Financier de la Dépense') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <pre style="white-space: pre-wrap; margin-bottom: 0; font-family: inherit;"><?= htmlspecialchars($_SESSION['error_message']) ?></pre>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Récapitulatif de la Dépense Approuvée') ?></h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th class="ps-0" style="width: 40%;"><?= _('Pièce N°') ?></th>
                                <td><strong><?= htmlspecialchars($depense['numero_piece']) ?></strong></td>
                            </tr>
                            <tr>
                                <th class="ps-0"><?= _('Montant à payer') ?></th>
                                <td><h3 class="text-success mb-0"><?= number_format($depense['montant'], 2, ',', ' ') ?> FCFA</h3></td>
                            </tr>
                            <tr>
                                <th class="ps-0"><?= _('Motif/Désignation') ?></th>
                                <td><?= htmlspecialchars($depense['motif']) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Sélection de la Source de Trésorerie') ?></h5>
                    </div>
                    <form action="/depenses/process-payment/<?= $depense['id'] ?>" method="POST" class="card-body">
                        <div class="mb-3">
                            <label class="form-label required"><?= _('Compte financier de décaissement') ?></label>
                            <select name="compte_id" class="form-select" required>
                                <option value=""><?= _('Sélectionner le compte de décaissement') ?></option>
                                <?php foreach ($comptes as $compte): ?>
                                    <?php if ($compte['statut'] === 'actif'): ?>
                                        <option value="<?= $compte['id'] ?>">
                                            <?= htmlspecialchars($compte['nom_compte']) ?> (<?= ucfirst(str_replace('_', ' ', $compte['type_compte'])) ?>) - Solde : <?= number_format($compte['solde_courant'], 2, ',', ' ') ?> FCFA
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?= _('Justificatif / Note de paiement') ?></label>
                            <textarea name="motif_payment" class="form-control" rows="3" placeholder="<?= _('Saisissez une note optionnelle pour le paiement...') ?>"></textarea>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="/depenses" class="btn btn-outline-secondary"><?= _('Retour') ?></a>
                            <button type="submit" class="btn btn-success">
                                <i class="ti ti-cash"></i> <?= _('Valider & Enregistrer le Paiement') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>