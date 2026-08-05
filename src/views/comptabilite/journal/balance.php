<?php
$title = _("Balance Générale des Comptes");
ob_start();

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
                            <h5 class="m-b-10"><?= htmlspecialchars($title) ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Balance Générale") ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><?= _("Synthèse globale des débits, crédits et soldes") ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th><?= _("ID") ?></th>
                                        <th><?= _("Nom du Compte") ?></th>
                                        <th><?= _("Type de Compte") ?></th>
                                        <th class="text-end"><?= _("Total Débits (Entrées)") ?></th>
                                        <th class="text-end"><?= _("Total Crédits (Sorties)") ?></th>
                                        <th class="text-end"><?= _("Solde Courant") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($balances)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted"><?= _("Aucun compte financier enregistré.") ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php
                                        $grandDebit = 0.00;
                                        $grandCredit = 0.00;
                                        $grandSolde = 0.00;
                                        foreach ($balances as $b):
                                            $grandDebit += (float)$b['total_debit'];
                                            $grandCredit += (float)$b['total_credit'];
                                            $grandSolde += (float)$b['solde_courant'];
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($b['id']) ?></td>
                                                <td class="fw-bold"><?= htmlspecialchars($b['nom_compte']) ?></td>
                                                <td><span class="badge bg-light-primary"><?= htmlspecialchars($b['type_compte']) ?></span></td>
                                                <td class="text-end text-success"><?= number_format($b['total_debit'], 2, ',', ' ') ?> <?= htmlspecialchars($b['devise']) ?></td>
                                                <td class="text-end text-danger"><?= number_format($b['total_credit'], 2, ',', ' ') ?> <?= htmlspecialchars($b['devise']) ?></td>
                                                <td class="text-end fw-bold"><?= number_format($b['solde_courant'], 2, ',', ' ') ?> <?= htmlspecialchars($b['devise']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-dark">
                                            <td colspan="3" class="fw-bold"><?= _("TOTAL GÉNÉRAL") ?></td>
                                            <td class="text-end fw-bold"><?= number_format($grandDebit, 2, ',', ' ') ?> FCFA</td>
                                            <td class="text-end fw-bold"><?= number_format($grandCredit, 2, ',', ' ') ?> FCFA</td>
                                            <td class="text-end fw-bold"><?= number_format($grandSolde, 2, ',', ' ') ?> FCFA</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../layouts/footer_able.php';
?>
