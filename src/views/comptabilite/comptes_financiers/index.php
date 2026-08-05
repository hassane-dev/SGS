<?php
$title = _("Gestion des Comptes Financiers");
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
                            <li class="breadcrumb-item"><a href="/"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Comptes Financiers") ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- Alert messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-check-circle me-1"></i>
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

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
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><?= _("Registre des Comptes Financiers (Caisses, Banques, Mobile Money)") ?></h5>
                        <?php if (Auth::can('create', 'comptes_financiers')): ?>
                            <a href="/comptes-financiers/create" class="btn btn-primary d-inline-flex align-items-center">
                                <i class="ph-duotone ph-plus-circle me-1"></i> <?= _("Nouveau Compte") ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th><?= _('Nom du Compte') ?></th>
                                        <th><?= _('Type') ?></th>
                                        <th><?= _('Solde Courant') ?></th>
                                        <th><?= _('Responsable') ?></th>
                                        <th><?= _('Statut') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($comptes)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4"><?= _("Aucun compte financier n'est enregistré pour cet établissement.") ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($comptes as $c): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($c['nom_compte']) ?></strong>
                                                    <span class="d-block text-muted small">ID: #<?= $c['id'] ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($c['type_compte'] === 'caisse'): ?>
                                                        <span class="badge bg-light-primary"><i class="ph-duotone ph-safe me-1"></i><?= _('Caisse physique') ?></span>
                                                    <?php elseif ($c['type_compte'] === 'banque'): ?>
                                                        <span class="badge bg-light-info"><i class="ph-duotone ph-bank me-1"></i><?= _('Banque / Chèque') ?></span>
                                                    <?php elseif ($c['type_compte'] === 'mobile_money'): ?>
                                                        <span class="badge bg-light-warning"><i class="ph-duotone ph-device-mobile me-1"></i><?= _('Mobile Money') ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-secondary"><i class="ph-duotone ph-credit-card me-1"></i><?= _('Autre') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <h6 class="mb-0 text-dark font-weight-bold">
                                                        <?= number_format($c['solde_courant'], 2, ',', ' ') ?> <?= htmlspecialchars($c['devise']) ?>
                                                    </h6>
                                                </td>
                                                <td>
                                                    <span><?= htmlspecialchars($c['responsable_nom']) ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($c['statut'] === 'actif'): ?>
                                                        <span class="badge bg-light-success text-success"><?= _('Actif') ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-danger text-danger"><?= _('Suspendu') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group">
                                                        <?php if (Auth::can('edit', 'comptes_financiers')): ?>
                                                            <a href="/comptes-financiers/edit/<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Modifier">
                                                                <i class="ph-duotone ph-pencil"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if (Auth::can('manage', 'comptes_financiers')): ?>
                                                            <a href="/comptes-financiers/destroy/<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" title="Suspendre ou Supprimer" onclick="return confirm('<?= _('Êtes-vous sûr de vouloir suspendre ou supprimer ce compte financier ?') ?>');">
                                                                <i class="ph-duotone ph-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
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
        <!-- [ Main Content ] end -->
    </div>
</div>

<?php
require_once __DIR__ . '/../../layouts/footer_able.php';
$content = ob_get_clean();
echo $content;
?>