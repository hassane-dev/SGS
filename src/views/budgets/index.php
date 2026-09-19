<?php
// src/views/budgets/index.php
include __DIR__ . '/../layouts/header_able.php';
include __DIR__ . '/../layouts/sidebar_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ Breadcrumb ] start -->
        <div class="page-header mb-3">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Gestion Budgétaire') ?></h2>
                        </div>
                        <ul class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="/home"><?= _('Accueil') ?></a></li>
                            <li class="breadcrumb-item"><a href="/budgets"><?= _('Finances & Comptabilité') ?></a></li>
                            <li class="breadcrumb-item active"><?= _('Gestion Budgétaire') ?></li>
                        </ul>
                    </div>
                    <div class="col-md-4 text-end">
                        <?php if (Auth::can('create', 'budget')): ?>
                            <a href="/budgets/create" class="btn btn-primary d-inline-flex align-items-center">
                                <i class="ph-duotone ph-plus-circle me-2 fs-5"></i> <?= _('Nouveau Budget') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Breadcrumb ] end -->

        <!-- HUB ACTIONS / CTA BAR -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0 bg-grd-primary text-white">
                    <div class="card-body p-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <h5 class="text-white mb-1"><i class="ph-duotone ph-chart-pie me-2"></i><?= _("Cockpit de Pilotage Budgétaire") ?></h5>
                                <p class="text-white-50 small mb-0"><?= _("Planification, exécution, ajustements de crédits et engagements budgétaires.") ?></p>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="/budgets" class="btn btn-light btn-sm font-weight-bold">
                                    <i class="ph-duotone ph-list me-1"></i><?= _("Liste des Budgets") ?>
                                </a>

                                <?php if (Auth::can('report', 'budget')): ?>
                                    <a href="/budgets/report" class="btn btn-warning btn-sm text-dark font-weight-bold">
                                        <i class="ph-duotone ph-chart-line-up me-1"></i><?= _("Exécution Budgétaire") ?>
                                    </a>
                                <?php endif; ?>

                                <?php if (Auth::can('adjust', 'budget')): ?>
                                    <a href="/budgets/adjustment" class="btn btn-outline-light btn-sm">
                                        <i class="ph-duotone ph-arrows-left-right me-1"></i><?= _("Ajustements de Crédits") ?>
                                    </a>
                                <?php endif; ?>

                                <?php if (Auth::can('view', 'budget')): ?>
                                    <a href="/budgets/engagements" class="btn btn-outline-light btn-sm">
                                        <i class="ph-duotone ph-file-text me-1"></i><?= _("Engagements") ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-check-circle me-2 fs-5"></i>
                <?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-warning me-2 fs-5"></i>
                <?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="card table-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><?= _("Budgets Annuels Configurés") ?></h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th><?= _("Libellé du Budget") ?></th>
                                        <th><?= _("Exercice Financier") ?></th>
                                        <th><?= _("Statut") ?></th>
                                        <th><?= _("Date de Création") ?></th>
                                        <th class="text-end"><?= _("Actions") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($budgets)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i class="ph-duotone ph-folder-open fs-1 d-block mb-2"></i>
                                                <?= _("Aucun budget configuré pour cet établissement.") ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($budgets as $b): ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-bold text-dark"><?= htmlspecialchars($b['libelle']) ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($b['exercice_libelle'] ?? 'Exercice Unique') ?></td>
                                                <td>
                                                    <?php if ($b['statut'] === 'brouillon'): ?>
                                                        <span class="badge bg-light-secondary text-secondary"><?= _("Brouillon") ?></span>
                                                    <?php elseif ($b['statut'] === 'soumis'): ?>
                                                        <span class="badge bg-light-warning text-warning"><?= _("Soumis") ?></span>
                                                    <?php elseif ($b['statut'] === 'valide'): ?>
                                                        <span class="badge bg-light-info text-info"><?= _("Validé") ?></span>
                                                    <?php elseif ($b['statut'] === 'actif'): ?>
                                                        <span class="badge bg-light-success text-success"><?= _("Actif") ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-danger text-danger"><?= _("Clos") ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('d/m/Y H:i', strtotime($b['date_creation'])) ?></td>
                                                <td class="text-end">
                                                    <a href="/budgets/show/<?= $b['id'] ?>" class="btn btn-icon btn-link-secondary" title="<?= _("Détail & Lignes") ?>">
                                                        <i class="ph-duotone ph-eye fs-5"></i>
                                                    </a>
                                                    <?php if ($b['statut'] === 'actif'): ?>
                                                        <a href="/budgets/report/<?= $b['id'] ?>" class="btn btn-icon btn-link-success" title="<?= _("Rapport d'exécution") ?>">
                                                            <i class="ph-duotone ph-chart-pie fs-5"></i>
                                                        </a>
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

<?php include __DIR__ . '/../layouts/footer_able.php'; ?>