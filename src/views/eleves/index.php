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
                            <h2 class="mb-0"><?= _('Gestion des Élèves Actifs') ?></h2>
                        </div>
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
                            <a href="/eleves/archives" class="btn btn-secondary me-2">
                                <?= _('Archives') ?>
                            </a>
                            <a href="/eleves/create" class="btn btn-primary">
                                <?= _('Ajouter un Élève') ?>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?= _('Photo') ?></th>
                                        <th><?= _('Nom Complet') ?></th>
                                        <th><?= _('Email') ?></th>
                                        <th><?= _('Classes') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($eleves)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center"><?= _('Aucun élève actif ou en attente trouvé.') ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($eleves as $eleve): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($eleve['photo'])): ?>
                                                        <img src="<?= htmlspecialchars($eleve['photo']) ?>" alt="Photo de l'élève" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <img src="/assets/img/default-avatar.png" alt="Avatar par défaut" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></td>
                                                <td><?= htmlspecialchars($eleve['email']) ?></td>
                                                <td><?= htmlspecialchars($eleve['classes']) ?></td>
                                                <td class="text-end">
                                                    <?php if (Auth::can('paiement', 'view')): ?>
                                                        <a href="/paiements/show/<?= $eleve['id_eleve'] ?>" class="btn btn-success btn-sm" title="<?= _('Gérer les paiements') ?>"><?= _('Payer') ?></a>
                                                    <?php endif; ?>
                                                    <a href="/eleves/details?id=<?= $eleve['id_eleve'] ?>" class="btn btn-info btn-sm ms-2" title="<?= _('Dossier complet') ?>"><?= _('Détails') ?></a>
                                                    <a href="/eleves/edit?id=<?= $eleve['id_eleve'] ?>" class="btn btn-primary btn-sm ms-2" title="<?= _('Modifier') ?>"><?= _('Modifier') ?></a>
                                                    <form action="/eleves/destroy" method="POST" class="d-inline ms-2" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir radier cet élève ? Cette action est réversible.') ?>');">
                                                        <input type="hidden" name="id" value="<?= $eleve['id_eleve'] ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" title="<?= _('Radier l\'élève') ?>"><?= _('Radier') ?></button>
                                                    </form>
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
