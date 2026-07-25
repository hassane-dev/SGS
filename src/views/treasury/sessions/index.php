<?php include __DIR__ . '/../../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- Breadcrumbs -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Accueil") ?></a></li>
                            <li class="breadcrumb-item"><a href="javascript: void(0)"><?= _("Trésorerie") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Sessions de Caisse") ?></li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title d-flex justify-content-between align-items-center">
                            <h2 class="mb-0"><?= _("Sessions de Caisse") ?></h2>
                            <?php if (!$activeSession && Auth::can('create', 'sessions_caisse')): ?>
                                <a href="/treasury/sessions/open" class="btn btn-primary d-inline-flex align-items-center">
                                    <i class="ph-duotone ph-plus-circle me-2"></i><?= _("Ouvrir une caisse") ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Active Session Card -->
        <?php if ($activeSession): ?>
            <div class="card border-primary border-2 mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 text-white d-inline-flex align-items-center">
                        <i class="ph-duotone ph-clock me-2 fs-4"></i><?= _("Votre Session Active") ?>
                    </h5>
                    <span class="badge bg-light text-primary"><?= _("OUVERTE") ?></span>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="text-muted mb-1"><?= _("Caisse de report") ?> : <strong class="text-dark"><?= htmlspecialchars($activeSession['compte_id']) ?></strong></h6>
                            <p class="mb-0 text-muted"><?= _("Date d'ouverture") ?> : <strong><?= htmlspecialchars($activeSession['date_ouverture']) ?></strong></p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <a href="/treasury/sessions/show/<?= $activeSession['id'] ?>" class="btn btn-light-primary btn-sm d-inline-flex align-items-center">
                                <i class="ph-duotone ph-eye me-1"></i><?= _("Consulter & Fermer") ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Sessions List Table -->
        <div class="card">
            <div class="card-header">
                <h5><?= _("Historique des Sessions") ?></h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th><?= _("ID") ?></th>
                                <th><?= _("Caisse") ?></th>
                                <th><?= _("Caissier") ?></th>
                                <th><?= _("Date Ouverture") ?></th>
                                <th><?= _("Date Fermeture") ?></th>
                                <th><?= _("Report d'ouverture") ?></th>
                                <th><?= _("Solde Théorique") ?></th>
                                <th><?= _("Statut") ?></th>
                                <th class="text-end"><?= _("Action") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sessions)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted"><?= _("Aucune session trouvée.") ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sessions as $s): ?>
                                    <tr>
                                        <td><?= $s['id'] ?></td>
                                        <td><strong><?= htmlspecialchars($s['nom_compte']) ?></strong></td>
                                        <td><?= htmlspecialchars($s['user_prenom'] . ' ' . $s['user_nom']) ?></td>
                                        <td><?= htmlspecialchars($s['date_ouverture']) ?></td>
                                        <td><?= $s['date_fermeture'] ? htmlspecialchars($s['date_fermeture']) : '-' ?></td>
                                        <td><?= number_format($s['solde_ouverture'], 2, ',', ' ') ?> FCFA</td>
                                        <td><?= number_format($s['solde_theorique'], 2, ',', ' ') ?> FCFA</td>
                                        <td>
                                            <?php if ($s['statut'] === 'ouverte'): ?>
                                                <span class="badge bg-light-primary text-primary"><?= _("Active") ?></span>
                                            <?php elseif ($s['statut'] === 'fermee_a_valider'): ?>
                                                <span class="badge bg-light-warning text-warning"><?= _("À valider") ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-light-success text-success"><?= _("Clôturée") ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="/treasury/sessions/show/<?= $s['id'] ?>" class="btn btn-icon btn-light-secondary btn-sm">
                                                <i class="ph-duotone ph-eye fs-5"></i>
                                            </a>
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

<?php include __DIR__ . '/../../layouts/footer_able.php'; ?>