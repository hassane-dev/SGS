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
                            <h2 class="mb-0"><?= _('Élèves Archivés') ?></h2>
                        </div>
                        <p class="mb-0"><?= _('Cette page liste les élèves qui ne sont plus actifs dans l\'établissement (transférés, radiés, diplômés, etc.).') ?></p>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-end">
                            <a href="/eleves" class="btn btn-primary">
                                <?= _('Retour aux élèves actifs') ?>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?= _('Nom') ?></th>
                                        <th><?= _('Prénom') ?></th>
                                        <th><?= _('Sexe') ?></th>
                                        <th><?= _('Date de Naissance') ?></th>
                                        <th><?= _('Statut') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($eleves)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center"><?= _('Aucun élève archivé trouvé.') ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($eleves as $eleve): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($eleve['nom']) ?></td>
                                                <td><?= htmlspecialchars($eleve['prenom']) ?></td>
                                                <td><?= htmlspecialchars($eleve['sexe']) ?></td>
                                                <td><?= htmlspecialchars($eleve['date_naissance']) ?></td>
                                                <td>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($eleve['statut']) ?></span>
                                                </td>
                                                <td class="text-end">
                                                    <a href="/eleves/details?id=<?= $eleve['id_eleve'] ?>" class="btn btn-info btn-sm">
                                                        <?= _('Consulter le dossier') ?>
                                                    </a>
                                                    <!-- Option to restore student could be added here -->
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
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
