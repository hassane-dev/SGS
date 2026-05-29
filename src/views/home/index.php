<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Tableau de Bord') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <!-- Welcome card -->
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?= _('Bienvenue,') ?> <?= htmlspecialchars(Auth::get('prenom') ?? Auth::get('email')) ?> !</h5>
                        <p class="card-text"><?= _('Ceci est votre tableau de bord.') ?></p>
                    </div>
                </div>
            </div>

            <?php if (!empty($data['stats'])): ?>
                <!-- Admin Statistics -->
                <div class="col-md-6 col-xl-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h3 class="text-white"><?= $data['stats']['total_eleves'] ?></h3>
                                    <p class="text-white mb-0"><?= _('Élèves Actifs') ?></p>
                                </div>
                                <div class="col-4 text-end">
                                    <i class="ph-duotone ph-student f-36"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h3 class="text-white"><?= $data['stats']['total_personnel'] ?></h3>
                                    <p class="text-white mb-0"><?= _('Personnel Actif') ?></p>
                                </div>
                                <div class="col-4 text-end">
                                    <i class="ph-duotone ph-users f-36"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h3 class="text-white"><?= $data['stats']['total_classes'] ?></h3>
                                    <p class="text-white mb-0"><?= _('Classes') ?></p>
                                </div>
                                <div class="col-4 text-end">
                                    <i class="ph-duotone ph-chalkboard-teacher f-36"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h3 class="text-white"><?= number_format($data['stats']['total_recettes'], 0, ',', ' ') ?></h3>
                                    <p class="text-white mb-0"><?= _('Recettes (F CFA)') ?></p>
                                </div>
                                <div class="col-4 text-end">
                                    <i class="ph-duotone ph-money f-36"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($data['stats']['en_attente_paiement'] > 0): ?>
                <div class="col-12">
                    <div class="alert alert-warning border-0 shadow-sm">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="ph-duotone ph-warning-circle f-24"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <strong><?= _('Attention !') ?></strong> <?= sprintf(_('Il y a %d élève(s) en attente de paiement initial.'), $data['stats']['en_attente_paiement']) ?>
                                <a href="/paiements/pending" class="alert-link ms-2"><?= _('Voir la liste') ?></a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (Auth::get('role_name') === 'enseignant' && !empty($data['teacherSubjects'])) : ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><?= _('Mes Classes et Matières') ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th><?= _('Classe') ?></th>
                                            <th><?= _('Matière') ?></th>
                                            <th><?= _('Actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data['teacherSubjects'] as $subject) : ?>
                                            <tr>
                                                <td><?= htmlspecialchars(Classe::getFormattedName($subject)) ?></td>
                                                <td><?= htmlspecialchars($subject['nom_matiere']) ?></td>
                                                <td>
                                                    <a href="/notes/saisir/<?= $subject['classe_id'] ?>/<?= $subject['matiere_id'] ?>" class="btn btn-sm btn-primary"><?= _('Saisir Notes') ?></a>
                                                    <a href="/cahier-texte/<?= $subject['classe_id'] ?>/<?= $subject['matiere_id'] ?>" class="btn btn-sm btn-secondary"><?= _('Cahier de Texte') ?></a>
                                                    <a href="/presences/gerer/<?= $subject['classe_id'] ?>" class="btn btn-sm btn-info"><?= _('Gérer Présences') ?></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Quick Access Menu -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Accès Rapide') ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if (!empty($data['navLinks'])): ?>
                                <?php foreach ($data['navLinks'] as $link): ?>
                                    <div class="col-md-4 col-lg-3 mb-3">
                                        <a href="<?= $link['url'] ?>" class="btn btn-outline-primary w-100">
                                            <?= htmlspecialchars($link['text']) ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p><?= _('Aucune action disponible.') ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
