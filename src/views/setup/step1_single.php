<?php require_once __DIR__ . '/../layouts/header_centered.php'; ?>
<div class="col-md-8 col-lg-6">
    <div class="card shadow-sm">
        <div class="card-body p-5">
            <h1 class="h2 fw-bold mb-4"><?= _('Configuration de l\'école') ?></h1>
            <form action="/setup/finish" method="POST">
                <input type="hidden" name="install_mode" value="single">
                <fieldset class="mb-4">
                    <legend class="h5 fw-bold border-bottom pb-2 mb-3"><?= _('Informations sur l\'école') ?></legend>
                    <div class="row g-3">
                        <div class="col-md-6"><label for="nom_lycee" class="form-label"><?= _('Nom du Lycée') ?></label><input type="text" name="nom_lycee" id="nom_lycee" class="form-control" required></div>
                        <div class="col-md-6"><label for="type_lycee" class="form-label"><?= _('Type de Lycée') ?></label><select name="type_lycee" id="type_lycee" class="form-select" required><option value="prive"><?= _('Privé') ?></option><option value="parapublic"><?= _('Parapublic') ?></option><option value="public"><?= _('Public') ?></option></select></div>
                        <div class="col-12"><label for="annee_academique" class="form-label"><?= _('Année Académique Actuelle') ?></label><input type="text" name="annee_academique" id="annee_academique" class="form-control" placeholder="Ex: 2024-2025" required></div>
                    </div>
                </fieldset>
                <fieldset>
                    <legend class="h5 fw-bold border-bottom pb-2 mb-3"><?= _('Compte de l\'Administrateur') ?></legend>
                    <div class="row g-3">
                        <div class="col-md-6"><label for="admin_prenom" class="form-label"><?= _('Prénom') ?></label><input type="text" name="admin_prenom" id="admin_prenom" class="form-control" required></div>
                        <div class="col-md-6"><label for="admin_nom" class="form-label"><?= _('Nom') ?></label><input type="text" name="admin_nom" id="admin_nom" class="form-control" required></div>
                        <div class="col-12"><label for="admin_email" class="form-label"><?= _('Email') ?></label><input type="email" name="admin_email" id="admin_email" class="form-control" required></div>
                        <div class="col-12"><label for="admin_pass" class="form-label"><?= _('Mot de passe') ?></label><input type="password" name="admin_pass" id="admin_pass" class="form-control" required></div>
                    </div>
                </fieldset>
                <div class="d-grid mt-4"><button type="submit" class="btn btn-primary btn-lg"><?= _('Terminer la configuration') ?></button></div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>