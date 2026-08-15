<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- 1. En-tête et contexte -->
        <div class="page-header d-print-none mb-3">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <div class="page-header-title">
                            <h2 class="mb-1"><?= htmlspecialchars($title) ?></h2>
                            <p class="text-muted mb-0 small">
                                <i class="ph-duotone ph-shield-check me-1 text-success"></i>
                                <?= _('Cockpit de pilotage RH & Périmètre d\'habilitation') ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-5 text-end d-flex justify-content-end gap-2">
                        <?php if (Auth::can('create', 'drh')): ?>
                        <a href="/drh/create" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                            <i class="ph-duotone ph-user-plus fs-5"></i>
                            <span><?= _('Nouveau Personnel') ?></span>
                        </a>
                        <?php endif; ?>
                        <?php if (Auth::can('export', 'drh')): ?>
                        <a href="/drh/export" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            <i class="ph-duotone ph-download-simple fs-5"></i>
                            <span><?= _('Exporter (CSV)') ?></span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php $activeTab = 'dashboard'; require_once __DIR__ . '/_tabs.php'; ?>

        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <i class="ph-duotone ph-check-circle me-1 fs-5 align-middle"></i>
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="ph-duotone ph-warning-circle me-1 fs-5 align-middle"></i>
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- 2. KPIs RH principaux -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted fw-semibold mb-1 small text-uppercase"><?= _('Personnel Total') ?></p>
                                <h3 class="mb-0 fw-bold"><?= number_format($total) ?></h3>
                            </div>
                            <div class="avtar avtar-lg bg-light-primary text-primary rounded-3">
                                <i class="ph-duotone ph-users fs-2"></i>
                            </div>
                        </div>
                        <div class="mt-3 pt-2 border-top">
                            <span class="text-muted small"><i class="ph-duotone ph-buildings me-1"></i><?= _('Périmètre autorisé') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted fw-semibold mb-1 small text-uppercase"><?= _('En Activité') ?></p>
                                <h3 class="mb-0 fw-bold text-success"><?= number_format($actifs) ?></h3>
                            </div>
                            <div class="avtar avtar-lg bg-light-success text-success rounded-3">
                                <i class="ph-duotone ph-check-circle fs-2"></i>
                            </div>
                        </div>
                        <div class="mt-3 pt-2 border-top">
                            <span class="text-success small fw-semibold">
                                <?= $total > 0 ? round(($actifs / $total) * 100, 1) : 0 ?>% <?= _('du personnel total') ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted fw-semibold mb-1 small text-uppercase"><?= _('En Congé') ?></p>
                                <h3 class="mb-0 fw-bold text-warning"><?= number_format($conges) ?></h3>
                            </div>
                            <div class="avtar avtar-lg bg-light-warning text-warning rounded-3">
                                <i class="ph-duotone ph-calendar fs-2"></i>
                            </div>
                        </div>
                        <div class="mt-3 pt-2 border-top">
                            <span class="text-warning small fw-semibold">
                                <?= $total > 0 ? round(($conges / $total) * 100, 1) : 0 ?>% <?= _('du personnel total') ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted fw-semibold mb-1 small text-uppercase"><?= _('Suspendus') ?></p>
                                <h3 class="mb-0 fw-bold text-danger"><?= number_format($suspendus) ?></h3>
                            </div>
                            <div class="avtar avtar-lg bg-light-danger text-danger rounded-3">
                                <i class="ph-duotone ph-warning-circle fs-2"></i>
                            </div>
                        </div>
                        <div class="mt-3 pt-2 border-top">
                            <span class="text-danger small fw-semibold">
                                <?= $suspendus > 0 ? _('Action requise DRH') : _('Aucune suspension') ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <!-- 3. Synthèse du personnel -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                        <h5 class="mb-0 d-flex align-items-center gap-2">
                            <i class="ph-duotone ph-users-three text-primary"></i>
                            <span><?= _('Synthèse du Personnel Récent') ?></span>
                        </h5>
                        <a href="/drh" class="btn btn-sm btn-light-primary d-inline-flex align-items-center gap-1">
                            <span><?= _('Voir tout l\'annuaire') ?></span>
                            <i class="ph-duotone ph-arrow-right"></i>
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _('Matricule') ?></th>
                                        <th><?= _('Nom & Prénom') ?></th>
                                        <th><?= _('Fonction RH') ?></th>
                                        <th><?= _('Rôle Applicatif') ?></th>
                                        <th><?= _('Statut RH') ?></th>
                                        <th class="text-end"><?= _('Action') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($personnelList as $p): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-light-secondary text-dark font-monospace fw-bold">
                                                <?= htmlspecialchars($p['identifiant_public'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="/drh/show?id=<?= $p['id_user'] ?>" class="fw-bold text-dark text-decoration-none">
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
                                                'en_activite' => 'bg-light-success text-success',
                                                'en_conge' => 'bg-light-warning text-warning',
                                                'suspendu' => 'bg-light-danger text-danger',
                                                default => 'bg-light-secondary text-secondary'
                                            };
                                            $stLabel = match($st) {
                                                'en_activite' => _('En activité'),
                                                'en_conge' => _('En congé'),
                                                'suspendu' => _('Suspendu'),
                                                'demissionne' => _('Démissionné'),
                                                'licencie' => _('Licencié'),
                                                'retraite' => _('Retraité'),
                                                default => strtoupper($st)
                                            };
                                            ?>
                                            <span class="badge <?= $badge ?> fw-bold"><?= htmlspecialchars($stLabel) ?></span>
                                        </td>
                                        <td class="text-end">
                                            <a href="/drh/show?id=<?= $p['id_user'] ?>" class="btn btn-sm btn-icon btn-light-primary" title="<?= _('Consulter Dossier 360°') ?>">
                                                <i class="ph-duotone ph-eye fs-6"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($personnelList)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="ph-duotone ph-user-minus fs-1 d-block mb-2 text-muted"></i>
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

            <!-- 4. Répartition par statut/fonction/cycle & 5. Alertes RH -->
            <div class="col-lg-4 d-flex flex-column gap-3">
                <!-- 4. Répartition -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent py-3">
                        <h5 class="mb-0 d-flex align-items-center gap-2">
                            <i class="ph-duotone ph-chart-donut text-info"></i>
                            <span><?= _('Répartition par Statut RH') ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stCounts = [
                            'en_activite' => $actifs,
                            'en_conge' => $conges,
                            'suspendu' => $suspendus,
                            'autres' => max(0, $total - ($actifs + $conges + $suspendus))
                        ];
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-semibold text-success"><i class="ph-duotone ph-circle-fill fs-6 me-1"></i><?= _('En activité') ?></span>
                                <span class="small font-monospace fw-bold"><?= $actifs ?> (<?= $total > 0 ? round(($actifs/$total)*100) : 0 ?>%)</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: <?= $total > 0 ? ($actifs/$total)*100 : 0 ?>%"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-semibold text-warning"><i class="ph-duotone ph-circle-fill fs-6 me-1"></i><?= _('En congé') ?></span>
                                <span class="small font-monospace fw-bold"><?= $conges ?> (<?= $total > 0 ? round(($conges/$total)*100) : 0 ?>%)</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-warning" style="width: <?= $total > 0 ? ($conges/$total)*100 : 0 ?>%"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-semibold text-danger"><i class="ph-duotone ph-circle-fill fs-6 me-1"></i><?= _('Suspendus') ?></span>
                                <span class="small font-monospace fw-bold"><?= $suspendus ?> (<?= $total > 0 ? round(($suspendus/$total)*100) : 0 ?>%)</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-danger" style="width: <?= $total > 0 ? ($suspendus/$total)*100 : 0 ?>%"></div>
                            </div>
                        </div>

                        <?php if ($stCounts['autres'] > 0): ?>
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-semibold text-secondary"><i class="ph-duotone ph-circle-fill fs-6 me-1"></i><?= _('Départs / Inactifs') ?></span>
                                <span class="small font-monospace fw-bold"><?= $stCounts['autres'] ?> (<?= $total > 0 ? round(($stCounts['autres']/$total)*100) : 0 ?>%)</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-secondary" style="width: <?= $total > 0 ? ($stCounts['autres']/$total)*100 : 0 ?>%"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 5. Alertes RH -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent py-3">
                        <h5 class="mb-0 d-flex align-items-center gap-2">
                            <i class="ph-duotone ph-bell-ringing text-warning"></i>
                            <span><?= _('Alertes & Suivi RH') ?></span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php if ($suspendus > 0): ?>
                            <li class="list-group-item d-flex align-items-center justify-content-between py-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avtar avtar-xs bg-light-danger text-danger rounded-circle">
                                        <i class="ph-duotone ph-warning-circle"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold small"><?= _('Personnel Suspendu') ?></div>
                                        <div class="text-muted extra-small"><?= sprintf(_('%d agent(s) actuellement suspendu(s)'), $suspendus) ?></div>
                                    </div>
                                </div>
                                <a href="/drh?statut_rh=suspendu" class="btn btn-xs btn-light-danger"><?= _('Voir') ?></a>
                            </li>
                            <?php endif; ?>

                            <?php if ($conges > 0): ?>
                            <li class="list-group-item d-flex align-items-center justify-content-between py-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avtar avtar-xs bg-light-warning text-warning rounded-circle">
                                        <i class="ph-duotone ph-calendar-blank"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold small"><?= _('Congés en cours') ?></div>
                                        <div class="text-muted extra-small"><?= sprintf(_('%d agent(s) en absence autorisée'), $conges) ?></div>
                                    </div>
                                </div>
                                <a href="/drh?statut_rh=en_conge" class="btn btn-xs btn-light-warning"><?= _('Voir') ?></a>
                            </li>
                            <?php endif; ?>

                            <li class="list-group-item d-flex align-items-center justify-content-between py-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avtar avtar-xs bg-light-success text-success rounded-circle">
                                        <i class="ph-duotone ph-shield-check"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold small"><?= _('Conformité des Accès') ?></div>
                                        <div class="text-muted extra-small"><?= _('Portée Phase 10 active') ?></div>
                                    </div>
                                </div>
                                <span class="badge bg-light-success text-success"><i class="ph-duotone ph-check"></i> OK</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- 6. Actions rapides -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent py-3">
                        <h5 class="mb-0 d-flex align-items-center gap-2">
                            <i class="ph-duotone ph-lightning text-primary"></i>
                            <span><?= _('Actions Rapides') ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if (Auth::can('create', 'drh')): ?>
                            <a href="/drh/create" class="btn btn-light-primary text-start d-flex align-items-center justify-content-between">
                                <span><i class="ph-duotone ph-user-plus me-2"></i><?= _('Créer une nouvelle fiche') ?></span>
                                <i class="ph-duotone ph-caret-right"></i>
                            </a>
                            <?php endif; ?>
                            <a href="/drh" class="btn btn-light-secondary text-start d-flex align-items-center justify-content-between">
                                <span><i class="ph-duotone ph-users me-2"></i><?= _('Rechercher dans l\'annuaire') ?></span>
                                <i class="ph-duotone ph-caret-right"></i>
                            </a>
                            <?php if (Auth::can('export', 'drh')): ?>
                            <a href="/drh/export" class="btn btn-light-secondary text-start d-flex align-items-center justify-content-between">
                                <span><i class="ph-duotone ph-file-csv me-2"></i><?= _('Télécharger le registre CSV') ?></span>
                                <i class="ph-duotone ph-caret-right"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
