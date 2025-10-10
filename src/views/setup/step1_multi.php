<?php require_once __DIR__ . '/../layouts/header_centered.php'; ?>
<div class="col-md-8 col-lg-6">
    <div class="card shadow-sm">
        <div class="card-body p-5">
            <h1 class="h2 fw-bold mb-4"><?= _('Configuration Multi-écoles') ?></h1>
            <form action="/setup/finish" method="POST">
                <input type="hidden" name="install_mode" value="multi">
                <fieldset>
                    <legend class="h5 fw-bold border-bottom pb-2 mb-3"><?= _('Compte Administrateur National') ?></legend>
                    <p class="text-muted mb-3"><?= _('Ce compte gérera toutes les écoles du système.') ?></p>
                    <div class="row g-3">
                        <div class="col-md-6"><label for="prenom" class="form-label"><?= _('Prénom') ?></label><input type="text" name="prenom" id="prenom" class="form-control" required></div>
                        <div class="col-md-6"><label for="nom" class="form-label"><?= _('Nom') ?></label><input type="text" name="nom" id="nom" class="form-control" required></div>
                        <div class="col-12"><label for="email" class="form-label"><?= _('Email') ?></label><input type="email" name="email" id="email" class="form-control" required></div>
                        <div class="col-12"><label for="mot_de_passe" class="form-label"><?= _('Mot de passe') ?></label><input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control" required></div>
                    </div>
                </fieldset>
                <div class="d-grid mt-4"><button type="submit" class="btn btn-primary btn-lg"><?= _('Terminer la configuration') ?></button></div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>