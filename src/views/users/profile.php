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
                            <h2 class="mb-0"><?= _('Mon Profil') ?></h2>
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
                        <img src="<?= htmlspecialchars($user['photo'] ?? '/assets/img/default-avatar.png') ?>" alt="Photo de profil" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                        <h4 class="mb-1"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h4>
                        <p class="text-muted mb-3"><?= htmlspecialchars($user['fonction'] ?? 'N/A') ?></p>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Changer mon mot de passe') ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="/profile/update-password" method="POST">
                            <div class="mb-3">
                                <label for="current_password" class="form-label"><?= _('Mot de passe actuel') ?></label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label"><?= _('Nouveau mot de passe') ?></label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label"><?= _('Confirmer le nouveau mot de passe') ?></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary"><?= _('Mettre à jour le mot de passe') ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
