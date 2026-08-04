<?php
// src/views/budgets/show.php
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
                            <li class="breadcrumb-item"><a href="/budgets">Pilotage Budgétaire</a></li>
                            <li class="breadcrumb-item" aria-current="page">Détail du Budget</li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title d-flex justify-content-between align-items-center">
                            <h2 class="mb-0"><?= htmlspecialchars($budget['libelle']) ?></h2>
                            <div class="d-flex gap-2">
                                <form action="/budgets/rebuild/<?= $budget['id'] ?>" method="POST" class="d-inline">
                                    <button type="submit" class="btn btn-outline-secondary d-inline-flex align-items-center">
                                        <i class="ph-duotone ph-arrows-counter-clockwise me-2"></i> Recalculer les Lignes
                                    </button>
                                </form>

                                <?php if ($budget['statut'] === 'brouillon' && Auth::can('update', 'budget')): ?>
                                    <form action="/budgets/submit/<?= $budget['id'] ?>" method="POST" class="d-inline">
                                        <button type="submit" class="btn btn-warning d-inline-flex align-items-center">
                                            <i class="ph-duotone ph-paper-plane me-2"></i> Soumettre pour Validation
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($budget['statut'] === 'soumis' && Auth::can('activate', 'budget')): ?>
                                    <form action="/budgets/approve/<?= $budget['id'] ?>" method="POST" class="d-inline">
                                        <button type="submit" class="btn btn-success d-inline-flex align-items-center">
                                            <i class="ph-duotone ph-check-square me-2"></i> Valider & Activer
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($budget['statut'] === 'actif' && Auth::can('close', 'budget')): ?>
                                    <form action="/budgets/close/<?= $budget['id'] ?>" method="POST" class="d-inline">
                                        <button type="submit" class="btn btn-danger d-inline-flex align-items-center" onclick="return confirm('Êtes-vous sûr de vouloir clôturer ce budget définitivement ?');">
                                            <i class="ph-duotone ph-lock me-2"></i> Clôturer le Budget
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
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

        <!-- Statut Banner -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card bg-light-secondary border-0">
                    <div class="card-body py-3 d-flex justify-content-between align-items-center">
                        <span class="text-secondary fw-bold">Statut Actuel du Budget :</span>
                        <?php if ($budget['statut'] === 'brouillon'): ?>
                            <span class="badge bg-secondary">Brouillon (Modification Autorisée)</span>
                        <?php elseif ($budget['statut'] === 'soumis'): ?>
                            <span class="badge bg-warning text-dark">Soumis (En Attente d'Approbation)</span>
                        <?php elseif ($budget['statut'] === 'valide'): ?>
                            <span class="badge bg-info text-dark">Validé (Pre-actif)</span>
                        <?php elseif ($budget['statut'] === 'actif'): ?>
                            <span class="badge bg-success">Actif (Dépenses Imputables)</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Clos (Bloqué en Lecture Seule)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cards Row metrics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Allocation Initiale</h6>
                        <h4><?= number_format($summary['allocation_initiale'], 0, ',', ' ') ?> <small>FCFA</small></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Crédits Ajustés (Dotations/Transferts)</h6>
                        <h4 class="<?= $summary['montant_ajustements'] >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= ($summary['montant_ajustements'] >= 0 ? '+' : '') . number_format($summary['montant_ajustements'], 0, ',', ' ') ?> <small>FCFA</small>
                        </h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Crédits Consommés / Engagés</h6>
                        <h4><?= number_format($summary['total_utilise'], 0, ',', ' ') ?> <small>FCFA</small> <span class="fs-6 text-muted">(<?= $summary['taux_consommation'] ?>%)</span></h4>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $summary['taux_consommation'] ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Solde Budgétaire Restant</h6>
                        <h4 class="text-success"><?= number_format($summary['montant_disponible'], 0, ',', ' ') ?> <small>FCFA</small></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Budget Lines table and Form addition -->
        <div class="row">
            <!-- Add line form (Only if Draft) -->
            <?php if ($budget['statut'] === 'brouillon' && Auth::can('update', 'budget')): ?>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Ajouter une Ligne Budgétaire</h5>
                        </div>
                        <div class="card-body">
                            <form action="/budgets/lines/store" method="POST">
                                <input type="hidden" name="budget_id" value="<?= $budget['id'] ?>">

                                <div class="mb-3">
                                    <label class="form-label" for="categorie_id">Catégorie de Dépenses <span class="text-danger">*</span></label>
                                    <select class="form-select" id="categorie_id" name="categorie_id" required>
                                        <option value="">-- Sélectionner --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom_categorie']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="centre_cout_id">Centre de Coûts (Optionnel)</label>
                                    <select class="form-select" id="centre_cout_id" name="centre_cout_id">
                                        <option value="">-- Tous les centres / Aucun --</option>
                                        <?php foreach ($centres as $cc): ?>
                                            <option value="<?= $cc['id'] ?>"><?= htmlspecialchars($cc['nom_centre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="allocation_initiale">Allocation Initiale (FCFA) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="allocation_initiale" name="allocation_initiale" required>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary w-100 d-inline-flex align-items-center justify-content-center">
                                        <i class="ph-duotone ph-plus-circle me-2"></i> Ajouter la Ligne
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="<?= ($budget['statut'] === 'brouillon' && Auth::can('update', 'budget')) ? 'col-md-8' : 'col-md-12' ?>">
                <div class="card">
                    <div class="card-header">
                        <h5>Lignes de Budget</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Imputation (Catégorie & Centre)</th>
                                        <th>Allocation Initiale</th>
                                        <th>Ajustements</th>
                                        <th>Consommé / Engagé</th>
                                        <th>Disponible</th>
                                        <th>Taux</th>
                                        <?php if ($budget['statut'] === 'brouillon' && Auth::can('update', 'budget')): ?>
                                            <th class="text-end">Action</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($lines)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                Aucune ligne budgétaire configurée.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($lines as $line): ?>
                                            <tr>
                                                <td>
                                                    <span class="text-dark fw-bold"><?= htmlspecialchars($line['nom_categorie']) ?></span>
                                                    <?php if (!empty($line['nom_centre'])): ?>
                                                        <br><small class="text-muted"><i class="ph-duotone ph-tag"></i> Centre: <?= htmlspecialchars($line['nom_centre']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= number_format($line['allocation_initiale'], 0, ',', ' ') ?> FCFA</td>
                                                <td>
                                                    <span class="<?= $line['montant_ajustements'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                                        <?= ($line['montant_ajustements'] >= 0 ? '+' : '') . number_format($line['montant_ajustements'], 0, ',', ' ') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold"><?= number_format($line['total_utilise'], 0, ',', ' ') ?></span>
                                                    <br><small class="text-muted">Engagé: <?= number_format($line['montant_engage'], 0, ',', ' ') ?></small>
                                                </td>
                                                <td>
                                                    <span class="text-success fw-bold"><?= number_format($line['disponible'], 0, ',', ' ') ?> FCFA</span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $line['status_badge'] ?> mb-1"><?= $line['pourcentage_consomme'] ?>%</span>
                                                    <div class="progress" style="height: 4px; width: 80px;">
                                                        <div class="progress-bar bg-<?= $line['status_color'] ?>" role="progressbar" style="width: <?= $line['pourcentage_consomme'] ?>%"></div>
                                                    </div>
                                                </td>
                                                <?php if ($budget['statut'] === 'brouillon' && Auth::can('update', 'budget')): ?>
                                                    <td class="text-end">
                                                        <form action="/budgets/lines/delete" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette ligne budgétaire ?');">
                                                            <input type="hidden" name="ligne_id" value="<?= $line['id'] ?>">
                                                            <input type="hidden" name="budget_id" value="<?= $budget['id'] ?>">
                                                            <button type="submit" class="btn btn-icon btn-link-danger">
                                                                <i class="ph-duotone ph-trash fs-5"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                <?php endif; ?>
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