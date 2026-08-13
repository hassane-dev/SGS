<?php
require_once __DIR__ . '/../../layouts/header_able.php';
require_once __DIR__ . '/../../layouts/sidebar_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header d-print-none">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Détails du Bon de Réception") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="/achats/receptions"><?= _("Bons de réception") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= htmlspecialchars($reception['numero_reception']) ?></li>
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
                <a href="/achats/receptions" class="btn btn-primary">
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
                                <h3 class="font-weight-bold text-success"><?= _("BON DE RÉCEPTION") ?></h3>
                                <h5 class="text-muted mb-0">N° <?= htmlspecialchars($reception['numero_reception']) ?></h5>
                                <span class="small text-muted d-block mt-2"><strong><?= _("Date de réception :") ?></strong> <?= date('d/m/Y', strtotime($reception['date_reception'])) ?></span>
                                <span class="small text-muted d-block"><strong><?= _("Rattaché au BC :") ?></strong> <?= htmlspecialchars($commande['numero_commande']) ?></span>
                            </div>
                            <div class="col-md-4 col-sm-12 text-md-end text-sm-start mt-sm-3 mt-md-0">
                                <div class="bg-light p-3 rounded text-start">
                                    <h6 class="text-muted text-uppercase mb-2 font-weight-bold small"><?= _("Réceptionné par") ?></h6>
                                    <h5 class="mb-1 text-dark"><?= htmlspecialchars($reception['receptionne_par_prenom'] . ' ' . $reception['receptionne_par_nom']) ?></h5>
                                    <span class="d-block small text-muted"><strong><?= _("Statut :") ?></strong> <?= htmlspecialchars($reception['statut']) ?></span>
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
                                        <th style="width: 13%; text-align: right;"><?= _("Commandé") ?></th>
                                        <th style="width: 13%; text-align: right;"><?= _("Réceptionné") ?></th>
                                        <th style="width: 14%; text-align: right;"><?= _("Refusé") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lignes as $ligne): ?>
                                        <tr>
                                            <td><code class="text-dark font-weight-bold"><?= htmlspecialchars($ligne['article_ref']) ?></code></td>
                                            <td>
                                                <span class="d-block font-weight-bold"><?= htmlspecialchars($ligne['article_libelle']) ?></span>
                                                <span class="text-muted small"><?= htmlspecialchars($ligne['unite_mesure']) ?></span>
                                            </td>
                                            <td style="text-align: right;"><?= number_format($ligne['quantite_commandee'], 2, ',', ' ') ?></td>
                                            <td style="text-align: right;" class="font-weight-bold text-success"><?= number_format($ligne['quantite_receptionnee'], 2, ',', ' ') ?></td>
                                            <td style="text-align: right;" class="font-weight-bold text-danger">
                                                <?= number_format($ligne['quantite_refusee'], 2, ',', ' ') ?>
                                                <?php if (!empty($ligne['motif_refus'])): ?>
                                                    <br><small class="text-muted font-weight-normal"><?= htmlspecialchars($ligne['motif_refus']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
