<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- Page Header -->
        <div class="page-header d-print-none">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= htmlspecialchars($title) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php $activeTab = 'dashboard'; require_once __DIR__ . '/_tabs.php'; ?>

        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- KPI Cards Row -->
        <div class="row">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-white text-opacity-75 mb-1"><?= _('Personnel Total') ?></h6>
                                <h3 class="text-white mb-0"><?= number_format($total) ?></h3>
                            </div>
                            <div class="avtar avtar-lg bg-white bg-opacity-25 text-white">
                                <i class="ph-duotone ph-users text-white fs-2">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-white text-opacity-75 mb-1"><?= _('En Activité') ?></h6>
                                <h3 class="text-white mb-0"><?= number_format($actifs) ?></h3>
                            </div>
                            <div class="avtar avtar-lg bg-white bg-opacity-25 text-white">
                                <i class="ph-duotone ph-check-circle text-white fs-2">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-white text-opacity-75 mb-1"><?= _('En Congé') ?></h6>
                                <h3 class="text-white mb-0"><?= number_format($conges) ?></h3>
                            </div>
                            <div class="avtar avtar-lg bg-white bg-opacity-25 text-white">
                                <i class="ph-duotone ph-calendar text-white fs-2">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-white text-opacity-75 mb-1"><?= _('Suspendus') ?></h6>
                                <h3 class="text-white mb-0"><?= number_format($suspendus) ?></h3>
                            </div>
                            <div class="avtar avtar-lg bg-white bg-opacity-25 text-white">
                                <i class="ph-duotone ph-warning-circle text-white fs-2">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Personnel Table -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?= _('Personnel Récent dans votre Périmètre') ?></h5>
                        <a href="/drh" class="btn btn-sm btn-light-primary"><?= _('Voir tout l\'annuaire') ?> &rarr;</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _('Matricule') ?></th>
                                        <th><?= _('Nom & Prénom') ?></th>
                                        <th><?= _('Fonction RH') ?></th>
                                        <th><?= _('Rôle Applicatif') ?></th>
                                        <th><?= _('Statut RH') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($personnelList as $p): ?>
                                    <tr>
                                        <td><span class="badge bg-light-secondary text-dark fw-bold"><?= htmlspecialchars($p['identifiant_public'] ?? 'N/A') ?></span></td>
                                        <td>
                                            <a href="/drh/show?id=<?= $p['id_user'] ?>" class="fw-bold text-primary">
                                                <?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?>
                                            </a>
                                            <div class="small text-muted"><?= htmlspecialchars($p['email']) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($p['fonction'] ?? '—') ?></td>
                                        <td><span class="badge bg-light-primary text-primary"><?= htmlspecialchars($p['nom_role'] ?? '—') ?></span></td>
                                        <td>
                                            <?php
                                            $st = $p['statut_rh'] ?? 'en_activite';
                                            $badge = match($st) {
                                                'en_activite' => 'bg-success',
                                                'en_conge' => 'bg-warning',
                                                'suspendu' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $badge ?>"><?= strtoupper(htmlspecialchars($st)) ?></span>
                                        </td>
                                        <td class="text-end">
                                            <a href="/drh/show?id=<?= $p['id_user'] ?>" class="btn btn-sm btn-icon btn-light-secondary" title="<?= _('Dossier 360°') ?>">
                                                <i class="ph-duotone ph-eye">
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($personnelList)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <?= _('Aucun membre du personnel trouvé dans votre périmètre autorisé.') ?>
                                        </td>
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

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
