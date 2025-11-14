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
                            <h2 class="mb-0"><?= _('Ajouter un nouveau rôle') ?></h2>
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
                        <form action="/roles/store" method="POST">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="nom_role" class="form-label"><?= _('Nom du Rôle') ?></label>
                                    <input type="text" name="nom_role" id="nom_role" class="form-control" required>
                                </div>

                                <?php if (Auth::can('view_all_lycees', 'lycee')): ?>
                                <div class="col-12">
                                    <label for="lycee_id" class="form-label"><?= _('Portée') ?></label>
                                    <select name="lycee_id" id="lycee_id" class="form-select">
                                        <option value=""><?= _('Rôle Global') ?></option>
                                        <?php foreach ($lycees as $lycee): ?>
                                            <option value="<?= $lycee['id_lycee'] ?>"><?= _('Spécifique à') ?>: <?= htmlspecialchars($lycee['nom_lycee']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text"><?= _('Laissez sur "Global" pour les rôles généraux (ex: Enseignant), ou assignez à une école pour un rôle local (ex: Administrateur local).') ?></div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="mt-4 d-flex justify-content-end">
                                <a href="/roles" class="btn btn-secondary me-2"><?= _('Annuler') ?></a>
                                <button type="submit" class="btn btn-primary">
                                    <?= _('Enregistrer') ?>
                                </button>
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
