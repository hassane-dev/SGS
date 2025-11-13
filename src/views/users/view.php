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
                            <h2 class="mb-0"><?= _('Détail du membre du personnel') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body text-center">
                        <img src="<?= htmlspecialchars($user['photo'] ?? '/img/default-avatar.png') ?>" alt="Photo de profil" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                        <h4 class="mb-1"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h4>
                        <p class="text-muted mb-3"><?= htmlspecialchars($user['fonction'] ?? 'Fonction non spécifiée') ?></p>
                        <?php if ($user['actif']): ?>
                            <span class="badge bg-light-success"><?= _('Actif') ?></span>
                        <?php else: ?>
                            <span class="badge bg-light-danger"><?= _('Inactif') ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="border-bottom pb-2 mb-3"><?= _('Informations Personnelles') ?></h5>
                        <div class="row">
                            <div class="col-sm-6 mb-2"><strong><?= _('Sexe') ?>:</strong> <?= htmlspecialchars($user['sexe'] ?? 'N/A') ?></div>
                            <div class="col-sm-6 mb-2"><strong><?= _('Date de Naissance') ?>:</strong> <?= htmlspecialchars($user['date_naissance'] ?? 'N/A') ?></div>
                            <div class="col-sm-6 mb-2"><strong><?= _('Lieu de Naissance') ?>:</strong> <?= htmlspecialchars($user['lieu_naissance'] ?? 'N/A') ?></div>
                            <div class="col-sm-6 mb-2"><strong><?= _('Téléphone') ?>:</strong> <?= htmlspecialchars($user['telephone'] ?? 'N/A') ?></div>
                            <div class="col-12 mb-2"><strong><?= _('Adresse') ?>:</strong> <?= htmlspecialchars($user['adresse'] ?? 'N/A') ?></div>
                        </div>

                        <h5 class="border-bottom pb-2 mt-4 mb-3"><?= _('Informations Professionnelles') ?></h5>
                        <div class="row">
                            <div class="col-sm-6 mb-2"><strong><?= _('Rôle') ?>:</strong> <?= htmlspecialchars($role['nom_role'] ?? 'N/A') ?></div>
                            <div class="col-sm-6 mb-2"><strong><?= _('Type de Contrat') ?>:</strong> <?= htmlspecialchars($contrat['libelle'] ?? 'N/A') ?></div>
                            <div class="col-sm-6 mb-2"><strong><?= _('Date d\'embauche') ?>:</strong> <?= htmlspecialchars($user['date_embauche'] ?? 'N/A') ?></div>
                        </div>

                        <h5 class="border-bottom pb-2 mt-4 mb-3"><?= _('Informations du Compte') ?></h5>
                        <div class="row">
                            <div class="col-12 mb-2"><strong><?= _('Email') ?>:</strong> <?= htmlspecialchars($user['email']) ?></div>
                        </div>
                    </div>
                </div>
                <div class="mt-3 d-flex justify-content-end">
                    <a href="/users" class="btn btn-secondary me-2"><?= _('Retour à la liste') ?></a>
                    <?php if (Auth::get('id_user') != $user['id_user']): ?>
                        <a href="/users/edit?id=<?= $user['id_user'] ?>" class="btn btn-primary me-2"><?= _('Modifier') ?></a>
                        <form action="/users/destroy" method="POST" class="d-inline" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir supprimer ce membre ?') ?>');">
                            <input type="hidden" name="id" value="<?= $user['id_user'] ?>">
                            <button type="submit" class="btn btn-danger"><?= _('Supprimer') ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
