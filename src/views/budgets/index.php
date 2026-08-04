<?php
// src/views/budgets/index.php
include __DIR__ . '/../layouts/header_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ Breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="javascript: void(0)">Pilotage Budgétaire</a></li>
                            <li class="breadcrumb-item" aria-current="page">Liste des Budgets</li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title d-flex justify-content-between align-items-center">
                            <h2 class="mb-0">Exercices Budgétaires</h2>
                            <?php if (Auth::can('create', 'budget')): ?>
                                <a href="/budgets/create" class="btn btn-primary d-inline-flex align-items-center">
                                    <i class="ph-duotone ph-plus-circle me-2 fs-5"></i> Nouveau Budget
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Breadcrumb ] end -->

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
                        <h5>Budgets Annuels Configurés</h5>
                        <a href="/budgets/adjustment" class="btn btn-link-secondary d-inline-flex align-items-center">
                            <i class="ph-duotone ph-arrows-left-right me-2"></i> Gérer les Ajustements
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Libellé du Budget</th>
                                        <th>Exercice Financier</th>
                                        <th>Statut</th>
                                        <th>Date de Création</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($budgets)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i class="ph-duotone ph-folder-open fs-1 d-block mb-2"></i>
                                                Aucun budget configuré pour cet établissement.
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
                                                        <span class="badge bg-light-secondary text-secondary">Brouillon</span>
                                                    <?php elseif ($b['statut'] === 'soumis'): ?>
                                                        <span class="badge bg-light-warning text-warning">Soumis</span>
                                                    <?php elseif ($b['statut'] === 'valide'): ?>
                                                        <span class="badge bg-light-info text-info">Validé</span>
                                                    <?php elseif ($b['statut'] === 'actif'): ?>
                                                        <span class="badge bg-light-success text-success">Actif</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-danger text-danger">Clos</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('d/m/Y H:i', strtotime($b['date_creation'])) ?></td>
                                                <td class="text-end">
                                                    <a href="/budgets/show/<?= $b['id'] ?>" class="btn btn-icon btn-link-secondary" title="Détail & Lignes">
                                                        <i class="ph-duotone ph-eye fs-5"></i>
                                                    </a>
                                                    <?php if ($b['statut'] === 'actif'): ?>
                                                        <a href="/budgets/report/<?= $b['id'] ?>" class="btn btn-icon btn-link-success" title="Rapport d'exécution">
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