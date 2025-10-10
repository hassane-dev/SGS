<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= _('Tableau de Bord') ?></h1>
    </div>

    <div class="alert alert-info">
        <p><?= _('Bienvenue,') ?> <?= htmlspecialchars(Auth::get('prenom') . ' ' . Auth::get('nom')) ?>!</p>
        <p><?= _('Votre rôle est :') ?> <strong><?= htmlspecialchars(Auth::get('role_name')) ?></strong></p>
    </div>

    <!-- Content Row -->
    <div class="row">

        <div class="col-lg-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><?= _('Accès Rapide') ?></h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php if (Auth::can('setting', 'edit')) : ?><a href="/settings" class="list-group-item list-group-item-action"><?= _('Paramètres Généraux') ?></a><?php endif; ?>
                        <?php if (Auth::can('lycee', 'view_all')) : ?><a href="/lycees" class="list-group-item list-group-item-action"><?= _('Gérer les Lycées') ?></a><?php endif; ?>
                        <?php if (Auth::can('user', 'view_all')) : ?><a href="/users" class="list-group-item list-group-item-action"><?= _('Gérer le Personnel') ?></a><?php endif; ?>
                        <?php if (Auth::can('eleve', 'view_all')) : ?><a href="/eleves" class="list-group-item list-group-item-action"><?= _('Gérer les Élèves') ?></a><?php endif; ?>
                        <?php if (Auth::can('class', 'manage')) : ?><a href="/classes" class="list-group-item list-group-item-action"><?= _('Gérer les Classes') ?></a><?php endif; ?>
                        <?php if (Auth::can('matiere', 'manage')) : ?><a href="/matieres" class="list-group-item list-group-item-action"><?= _('Gérer les Matières') ?></a><?php endif; ?>
                        <?php if (Auth::can('role', 'view_all')) : ?><a href="/roles" class="list-group-item list-group-item-action"><?= _('Gérer les Rôles') ?></a><?php endif; ?>
                        <?php if (Auth::can('user', 'edit')) : ?><a href="/contrats" class="list-group-item list-group-item-action"><?= _('Gérer les Types de Contrat') ?></a><?php endif; ?>
                        <?php if (Auth::can('salaire', 'manage')) : ?><a href="/salaires" class="list-group-item list-group-item-action"><?= _('Gérer les Salaires') ?></a><?php endif; ?>
                        <?php if (Auth::can('note', 'manage')) : ?><a href="/notes" class="list-group-item list-group-item-action"><?= _('Saisir les Notes') ?></a><?php endif; ?>
                        <?php if (Auth::can('cahier_texte', 'create_own') || Auth::can('cahier_texte', 'view_all')) : ?><a href="/cahier-texte" class="list-group-item list-group-item-action"><?= _('Cahier de Texte') ?></a><?php endif; ?>
                        <?php if (Auth::can('class', 'manage')) : ?><a href="/emploi-du-temps" class="list-group-item list-group-item-action"><?= _('Emploi du Temps') ?></a><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>