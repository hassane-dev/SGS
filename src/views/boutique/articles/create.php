<?php require_once __DIR__ . '/../../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/boutique/articles"><?= _('Boutique') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Ajouter un Article') ?></li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Ajouter un Article') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body">
                        <form action="/boutique/articles/store" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label" for="nom_article"><?= _('Nom de l\'Article') ?></label>
                                    <input type="text" name="nom_article" id="nom_article" class="form-control" placeholder="<?= _('Ex: Uniforme scolaire') ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="prix"><?= _('Prix') ?></label>
                                    <input type="number" step="0.01" name="prix" id="prix" class="form-control" placeholder="<?= _('0.00') ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="stock"><?= _('Stock Initial') ?></label>
                                    <input type="number" name="stock" id="stock" class="form-control" placeholder="<?= _('0') ?>">
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label" for="image"><?= _('Image de l\'Article') ?></label>
                                    <input type="file" name="image" id="image" class="form-control" accept="image/*">
                                </div>

                                <?php if (Auth::get('role_name') === 'super_admin_national' || Auth::get('role_name') === 'super_admin_createur'): ?>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label" for="lycee_id"><?= _('Lycée') ?></label>
                                    <select name="lycee_id" id="lycee_id" class="form-select" required>
                                        <?php foreach ($lycees as $lycee): ?>
                                            <option value="<?= $lycee['id'] ?>"><?= htmlspecialchars($lycee['nom_lycee']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary"><?= _('Enregistrer') ?></button>
                                <a href="/boutique/articles" class="btn btn-link-secondary"><?= _('Annuler') ?></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
