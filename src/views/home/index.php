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
                        <p class="card-text"><?= _('Ceci est votre tableau de bord. Des widgets et des statistiques seront bientôt disponibles ici.') ?></p>
                    </div>
                </div>
            </div>

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
