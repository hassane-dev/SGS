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
                            <h2 class="mb-0"><?= _('Modifier la Classe') ?></h2>
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
                        <form action="/classes/update" method="POST">
                            <input type="hidden" name="id_classe" value="<?= htmlspecialchars($classe['id_classe']) ?>">
                            <div class="row g-3">

                                <!-- Niveau -->
                                <div class="col-md-6">
                                    <label for="niveau" class="form-label"><?= _('Niveau') ?></label>
                                    <input type="text" name="niveau" id="niveau" class="form-control" value="<?= htmlspecialchars($classe['niveau']) ?>">
                                </div>

                                <!-- Serie -->
                                <div class="col-md-6">
                                    <label for="serie" class="form-label"><?= _('Série') ?></label>
                                    <input type="text" name="serie" id="serie" class="form-control" value="<?= htmlspecialchars($classe['serie']) ?>">
                                </div>

                                <!-- Categorie -->
                                <div class="col-md-6">
                                    <label for="categorie" class="form-label"><?= _('Catégorie') ?></label>
                                    <select name="categorie" id="categorie" class="form-select">
                                        <option value=""><?= _('-- Choisir une catégorie --') ?></option>
                                        <option value="Scientifique" <?= ($classe['categorie'] ?? '') == 'Scientifique' ? 'selected' : '' ?>><?= _('Scientifique') ?></option>
                                        <option value="Littéraire" <?= ($classe['categorie'] ?? '') == 'Littéraire' ? 'selected' : '' ?>><?= _('Littéraire') ?></option>
                                    </select>
                                </div>

                                <!-- Numero -->
                                <div class="col-md-6">
                                    <label for="numero" class="form-label"><?= _('Numéro') ?></label>
                                    <input type="number" name="numero" id="numero" class="form-control" value="<?= htmlspecialchars($classe['numero']) ?>">
                                </div>

                                <!-- Cycle -->
                                <div class="col-md-6">
                                    <label for="cycle_id" class="form-label"><?= _('Cycle') ?></label>
                                    <select name="cycle_id" id="cycle_id" class="form-select" required>
                                        <?php foreach ($cycles as $cycle): ?>
                                            <option value="<?= $cycle['id_cycle'] ?>" <?= $classe['cycle_id'] == $cycle['id_cycle'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cycle['nom_cycle']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Lycee -->
                                <?php if (Auth::can('view_all_lycees', 'lycee')): ?>
                                <div class="col-md-6">
                                    <label for="lycee_id" class="form-label"><?= _('Lycée') ?></label>
                                    <select name="lycee_id" id="lycee_id" class="form-select" required>
                                        <?php foreach ($lycees as $lycee): ?>
                                             <option value="<?= $lycee['id_lycee'] ?>" <?= $classe['lycee_id'] == $lycee['id_lycee'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($lycee['nom_lycee']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php else: ?>
                                    <input type="hidden" name="lycee_id" value="<?= $classe['lycee_id'] ?>">
                                <?php endif; ?>
                            </div>

                            <div class="mt-4 d-flex justify-content-end">
                                <a href="/classes" class="btn btn-secondary me-2"><?= _('Annuler') ?></a>
                                <button type="submit" class="btn btn-primary">
                                    <?= _('Mettre à jour') ?>
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
