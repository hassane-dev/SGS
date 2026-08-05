<?php
$title = _("Grand Livre de Trésorerie");
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
                            <li class="breadcrumb-item" aria-current="page"><?= _("Grand Livre") ?></li>
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
                        <h5><?= _("Tous les flux et écritures de trésorerie") ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?= _("Date/Heure") ?></th>
                                        <th><?= _("Compte") ?></th>
                                        <th><?= _("Type") ?></th>
                                        <th><?= _("Montant") ?></th>
                                        <th><?= _("Mode") ?></th>
                                        <th><?= _("Référence") ?></th>
                                        <th><?= _("Événement") ?></th>
                                        <th><?= _("Motif") ?></th>
                                        <th><?= _("Opérateur") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($movements)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted"><?= _("Aucun mouvement enregistré.") ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($movements as $m): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($m['date_mouvement']) ?></td>
                                                <td><?= htmlspecialchars($m['nom_compte']) ?></td>
                                                <td>
                                                    <?php if ($m['type_mouvement'] === 'entree'): ?>
                                                        <span class="badge bg-light-success"><i class="ph-duotone ph-arrow-down-left"></i> <?= _("Débit (Entrée)") ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-danger"><i class="ph-duotone ph-arrow-up-right"></i> <?= _("Crédit (Sortie)") ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="fw-bold"><?= number_format($m['montant'], 2, ',', ' ') ?> FCFA</td>
                                                <td><?= htmlspecialchars($m['mode_paiement']) ?></td>
                                                <td><?= htmlspecialchars($m['reference_transaction'] ?? 'N/A') ?></td>
                                                <td><span class="badge bg-light-primary"><?= htmlspecialchars($m['evenement_type']) ?></span></td>
                                                <td><?= htmlspecialchars($m['motif']) ?></td>
                                                <td><?= htmlspecialchars(($m['user_prenom'] ?? '') . ' ' . ($m['user_nom'] ?? 'System')) ?></td>
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
require_once __DIR__ . '/../../layouts/footer_able.php';
?>
