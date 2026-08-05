<?php
$title = _("Registre des Engagements Budgétaires");
ob_start();

require_once __DIR__ . '/../layouts/header_able.php';
require_once __DIR__ . '/../layouts/sidebar_able.php';
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
                            <li class="breadcrumb-item" aria-current="page"><?= _("Engagements Budgétaires") ?></li>
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
                        <h5><?= _("Tous les engagements et réservations de crédits") ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th><?= _("Date") ?></th>
                                        <th><?= _("Dépense liée") ?></th>
                                        <th><?= _("Ligne de Budget") ?></th>
                                        <th class="text-end"><?= _("Montant Engagé") ?></th>
                                        <th class="text-center"><?= _("Statut de l'engagement") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($engagements)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted"><?= _("Aucun engagement enregistré.") ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($engagements as $e): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($e['date_engagement']) ?></td>
                                                <td>
                                                    <strong>#<?= htmlspecialchars($e['depense_id']) ?></strong> - <?= htmlspecialchars($e['depense_objet']) ?>
                                                </td>
                                                <td><span class="badge bg-light-primary"><?= htmlspecialchars($e['nom_categorie']) ?></span></td>
                                                <td class="text-end fw-bold"><?= number_format($e['montant'], 2, ',', ' ') ?> FCFA</td>
                                                <td class="text-center">
                                                    <?php if ($e['statut'] === 'consomme'): ?>
                                                        <span class="badge bg-success"><?= _("Consommé (Payé)") ?></span>
                                                    <?php elseif ($e['statut'] === 'engage'): ?>
                                                        <span class="badge bg-primary"><?= _("Engagé (Approuvé)") ?></span>
                                                    <?php elseif ($e['statut'] === 'reserve'): ?>
                                                        <span class="badge bg-warning text-dark"><?= _("Réservé (Soumis)") ?></span>
                                                    <?php elseif ($e['statut'] === 'libere'): ?>
                                                        <span class="badge bg-info text-dark"><?= _("Libéré (Rejeté)") ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary"><?= htmlspecialchars($e['statut']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
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
require_once __DIR__ . '/../layouts/footer_able.php';
?>
