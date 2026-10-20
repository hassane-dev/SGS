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
                            <h2 class="mb-0"><?= _('Ajouter un nouveau Cycle') ?></h2>
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
                    <div class="card-body">
                        <form action="/cycles/store" method="POST">
    <?= csrf_field() ?>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="nom_cycle" class="form-label"><?= _('Nom du Cycle') ?></label>
                                    <input type="text" name="nom_cycle" id="nom_cycle" class="form-control" placeholder="<?= _('Ex: Lycée') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="niveau_debut" class="form-label"><?= _('Niveau de Début') ?></label>
                                    <input type="text" name="niveau_debut" id="niveau_debut" class="form-control" placeholder="<?= _('Ex: 6e ou Seconde') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="niveau_fin" class="form-label"><?= _('Niveau de Fin') ?></label>
                                    <input type="text" name="niveau_fin" id="niveau_fin" class="form-control" placeholder="<?= _('Ex: 3e ou Terminale') ?>">
                                </div>
                                <div class="col-md-12">
                                    <label for="utilise_series" class="form-label fw-bold"><?= _('Organisation par Séries') ?></label>
                                    <select name="utilise_series" id="utilise_series" class="form-select">
                                        <option value=""><?= _('-- Non configuré (Sélection obligatoire pour créer des classes) --') ?></option>
                                        <option value="1"><?= _('Oui — Ce cycle autorise l\'organisation par séries (ex: Lycée)') ?></option>
                                        <option value="0"><?= _('Non — Ce cycle n\'utilise pas de séries (ex: CEG / Collège)') ?></option>
                                    </select>
                                    <small class="form-text text-muted"><?= _('Détermine si les formulaires de classe proposeront un sélecteur de séries pour ce cycle.') ?></small>
                                </div>
                            </div>
                            <div class="mt-4 d-flex justify-content-end">
                                <a href="/cycles" class="btn btn-secondary me-2"><?= _('Annuler') ?></a>
                                <button type="submit" class="btn btn-primary"><?= _('Enregistrer') ?></button>
                            </div>
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
