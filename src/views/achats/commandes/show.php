<?php
require_once __DIR__ . '/../../layouts/header_able.php';
require_once __DIR__ . '/../../layouts/sidebar_able.php';

$totalPO = 0.00;
foreach ($lignes as $ligne) {
    $totalPO += (float)$ligne['quantite_commandee'] * (float)$ligne['prix_unitaire_negocie'];
}
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header d-print-none">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Bon de Commande") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="/achats/commandes"><?= _("Bons de commande") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= htmlspecialchars($commande['numero_commande']) ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- Action Bar -->
        <div class="row d-print-none mb-4">
            <div class="col-12 text-end">
                <button onclick="window.print();" class="btn btn-outline-secondary me-2">
                    <i class="ph-duotone ph-printer me-2"></i><?= _("Imprimer") ?>
                </button>
                <a href="/achats/commandes" class="btn btn-primary">
                    <i class="ph-duotone ph-arrow-left me-2"></i><?= _("Retour à la liste") ?>
                </a>
            </div>
        </div>

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <!-- Header Section -->
                        <div class="row justify-content-between mb-4">
                            <div class="col-md-6 col-sm-12">
                                <h3 class="font-weight-bold text-primary"><?= _("BON DE COMMANDE") ?></h3>
                                <h5 class="text-muted mb-0">N° <?= htmlspecialchars($commande['numero_commande']) ?></h5>
                                <span class="small text-muted d-block mt-2"><strong><?= _("Date d'émission :") ?></strong> <?= date('d/m/Y', strtotime($commande['date_commande'])) ?></span>
                            </div>
                            <div class="col-md-5 col-sm-12 text-md-end text-sm-start mt-sm-3 mt-md-0">
                                <div class="bg-light p-3 rounded text-start">
                                    <h6 class="text-muted text-uppercase mb-2 font-weight-bold small"><?= _("Fournisseur") ?></h6>
                                    <h5 class="mb-1 text-dark"><?= htmlspecialchars($fournisseur['raison_sociale']) ?></h5>
                                    <span class="d-block small text-muted"><strong>Code :</strong> <?= htmlspecialchars($fournisseur['code_fournisseur']) ?></span>
                                    <span class="d-block small text-muted"><strong>NIF :</strong> <?= htmlspecialchars($fournisseur['nif'] ?: '-') ?></span>
                                    <span class="d-block small text-muted"><strong>RCCM :</strong> <?= htmlspecialchars($fournisseur['rccm'] ?: '-') ?></span>
                                    <span class="d-block small text-muted"><strong>Téléphone :</strong> <?= htmlspecialchars($fournisseur['telephone'] ?: '-') ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Lines Table -->
                        <div class="table-responsive my-4">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 15%;"><?= _("Référence") ?></th>
                                        <th style="width: 45%;"><?= _("Article / Service") ?></th>
                                        <th style="width: 10%; text-align: right;"><?= _("Quantité") ?></th>
                                        <th style="width: 15%; text-align: right;"><?= _("P.U. Négocié") ?></th>
                                        <th style="width: 15%; text-align: right;"><?= _("Montant HT") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lignes as $ligne):
                                        $mtLine = (float)$ligne['quantite_commandee'] * (float)$ligne['prix_unitaire_negocie'];
                                    ?>
                                        <tr>
                                            <td><code class="text-dark font-weight-bold"><?= htmlspecialchars($ligne['article_ref']) ?></code></td>
                                            <td>
                                                <span class="d-block h6 mb-0"><?= htmlspecialchars($ligne['article_libelle']) ?></span>
                                                <span class="text-muted small"><?= htmlspecialchars($ligne['unite_mesure']) ?></span>
                                            </td>
                                            <td style="text-align: right; font-weight: bold;"><?= number_format($ligne['quantite_commandee'], 2, ',', ' ') ?></td>
                                            <td style="text-align: right;"><?= number_format($ligne['prix_unitaire_negocie'], 2, ',', ' ') ?> FCFA</td>
                                            <td style="text-align: right; font-weight: bold;" class="text-primary"><?= number_format($mtLine, 2, ',', ' ') ?> FCFA</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Total Block -->
                        <div class="row justify-content-end">
                            <div class="col-md-5 col-sm-12">
                                <table class="table table-borderless align-middle mb-0">
                                    <tbody>
                                        <tr class="border-top">
                                            <td class="font-weight-bold h5 text-muted"><?= _("Montant Total HT :") ?></td>
                                            <td class="text-end font-weight-bold h4 text-primary"><?= number_format($totalPO, 2, ',', ' ') ?> FCFA</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <hr class="my-5">

                        <!-- Footer Signatures -->
                        <div class="row justify-content-between text-center mt-4">
                            <div class="col-md-4">
                                <span class="d-block text-muted small mb-5"><strong><?= _("Le Fournisseur") ?></strong></span>
                                <div style="height: 60px;"></div>
                                <span class="d-block text-muted small border-top pt-2 mx-5"><?= _("Date et Signature") ?></span>
                            </div>
                            <div class="col-md-4">
                                <span class="d-block text-muted small mb-5"><strong><?= _("L'Établissement") ?></strong></span>
                                <div style="height: 60px;"></div>
                                <span class="d-block text-muted small border-top pt-2 mx-5"><?= _("Date, Cachet et Signature") ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
