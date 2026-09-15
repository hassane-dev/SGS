<?php
/**
 * Vue : Liste des Conseils de Discipline (Phase 6.1)
 */
require_once __DIR__ . '/../../layouts/header_able.php';
require_once __DIR__ . '/../../layouts/sidebar_able.php';

$councilStatutBadges = [
    'planifie' => 'bg-warning text-dark',
    'convoque' => 'bg-info text-white',
    'en_session' => 'bg-primary',
    'delibere' => 'bg-purple text-white',
    'cloture' => 'bg-success',
    'annule' => 'bg-secondary',
];

$councilStatutLabels = [
    'planifie' => 'Planifié',
    'convoque' => 'Convoqué',
    'en_session' => 'En session',
    'delibere' => 'En délibération',
    'cloture' => 'Clôturé',
    'annule' => 'Annulé',
];
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">

        <!-- Fil d'Ariane -->
        <div class="page-header mb-3">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-header-title">
                            <h4 class="mb-0"><i class="ph-duotone ph-users-four me-2 text-primary"></i>Conseils de Discipline</h4>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <?php if ($canManage): ?>
                            <a href="/discipline/councils/create" class="btn btn-primary btn-sm">
                                <i class="ph-duotone ph-plus me-1"></i>Planifier un Conseil
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Flash Messages -->
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-check-circle me-2 fs-5"></i><?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-warning-circle me-2 fs-5"></i><?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Filtres -->
        <div class="card shadow-sm mb-4">
            <div class="card-body p-3">
                <form method="GET" action="/discipline/councils" class="row g-2 align-items-end">
                    <?php if (!$isTeacher): ?>
                        <div class="col-md-3">
                            <label class="form-label text-muted fs-7 mb-1">Année Académique</label>
                            <select name="annee_academique_id" class="form-select form-select-sm">
                                <?php foreach ($academicYears as $y): ?>
                                    <option value="<?= $y['id'] ?>" <?= ((int)($filters['annee_academique_id'] ?? $activeYear['id'] ?? 0) === (int)$y['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($y['libelle']) ?> <?= $y['est_active'] ? '(Active)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-2">
                        <label class="form-label text-muted fs-7 mb-1">Statut</label>
                        <select name="statut" class="form-select form-select-sm">
                            <option value="">Tous les statuts</option>
                            <?php foreach ($councilStatutLabels as $key => $lbl): ?>
                                <option value="<?= $key ?>" <?= ($filters['statut'] ?? '') === $key ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label text-muted fs-7 mb-1">Date Début</label>
                        <input type="date" name="date_debut" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_debut'] ?? '') ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label text-muted fs-7 mb-1">Date Fin</label>
                        <input type="date" name="date_fin" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_fin'] ?? '') ?>">
                    </div>

                    <div class="col-md-3 d-flex gap-1 ms-auto">
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="ph-duotone ph-funnel me-1"></i>Filtrer
                        </button>
                        <a href="/discipline/councils" class="btn btn-sm btn-outline-secondary" title="Réinitialiser">
                            <i class="ph-duotone ph-arrow-counter-clockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des Conseils -->
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="ph-duotone ph-list-numbers me-2 text-primary"></i>Sessions du Conseil de Discipline</h5>
                <span class="badge bg-light-primary text-primary"><?= count($councils) ?> session(s)</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($councils)): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="ph-duotone ph-users-four fs-1 text-secondary mb-2 d-block"></i>
                        Aucune session du Conseil de Discipline enregistrée pour cette sélection.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Code / Date</th>
                                    <th>Titre & Lieu</th>
                                    <th>Président</th>
                                    <th>Membres</th>
                                    <th>Élèves Traduits</th>
                                    <th>Statut</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($councils as $c): ?>
                                    <tr>
                                        <td>
                                            <strong class="d-block text-dark"><?= htmlspecialchars($c['code']) ?></strong>
                                            <small class="text-muted"><i class="ph-duotone ph-calendar me-1"></i><?= date('d/m/Y', strtotime($c['date_conseil'])) ?></small>
                                        </td>
                                        <td>
                                            <strong class="d-block text-dark"><?= htmlspecialchars($c['titre']) ?></strong>
                                            <small class="text-muted"><i class="ph-duotone ph-map-pin me-1"></i><?= htmlspecialchars($c['lieu'] ?? 'Non précisé') ?></small>
                                        </td>
                                        <td>
                                            <small class="text-muted"><i class="ph-duotone ph-user-circle me-1"></i><?= htmlspecialchars($c['president_nom'] ?? 'N/A') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light-info text-info fw-bold"><?= $c['total_membres'] ?> membre(s)</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light-danger text-danger fw-bold"><?= $c['total_eleves'] ?> élève(s)</span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $councilStatutBadges[$c['statut']] ?? 'bg-secondary' ?>">
                                                <?= $councilStatutLabels[$c['statut']] ?? ucfirst($c['statut']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="/discipline/councils/show?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="ph-duotone ph-eye me-1"></i>Voir
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
