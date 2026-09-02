<?php include __DIR__ . '/../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= $isEdit ? _("Modifier le Type d'Évaluation") : _("Nouveau Type d'Évaluation") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/dashboard"><?= _("Accueil") ?></a></li>
                            <li class="breadcrumb-item"><a href="/evaluations/types"><?= _("Types d'Évaluation") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= $isEdit ? _("Modification") : _("Création") ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h5><?= $isEdit ? _("Propriétés du Type d'Évaluation") : _("Créer un Type d'Évaluation") ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= $isEdit ? '/evaluations/types/update?id=' . $type['id'] : '/evaluations/types/store' ?>" method="POST">
                            <?php if ($isEdit): ?>
                                <input type="hidden" name="id" value="<?= $type['id'] ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="code" class="form-label"><?= _("Code Unique") ?> <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="code" class="form-control" value="<?= htmlspecialchars($type['code'] ?? '') ?>" placeholder="ex: devoir, compo, tp, interro" required <?= $isEdit ? 'readonly' : '' ?>>
                                <small class="text-muted"><?= _("Identifiant technique en minuscules sans espaces.") ?></small>
                            </div>

                            <div class="mb-3">
                                <label for="libelle" class="form-label"><?= _("Libellé d'Affichage") ?> <span class="text-danger">*</span></label>
                                <input type="text" name="libelle" id="libelle" class="form-control" value="<?= htmlspecialchars($type['libelle'] ?? '') ?>" placeholder="ex: Devoir de Contrôle, Composition, TP" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="bareme_defaut" class="form-label"><?= _("Barème par Défaut") ?> <span class="text-danger">*</span></label>
                                    <input type="number" step="0.5" min="1" max="100" name="bareme_defaut" id="bareme_defaut" class="form-control" value="<?= htmlspecialchars($type['bareme_defaut'] ?? '20.00') ?>" required>
                                    <small class="text-muted"><?= _("Note maximale par défaut (ex: 20, 40, 100).") ?></small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="ordre_affichage" class="form-label"><?= _("Ordre d'Affichage") ?></label>
                                    <input type="number" name="ordre_affichage" id="ordre_affichage" class="form-control" value="<?= htmlspecialchars($type['ordre_affichage'] ?? '0') ?>">
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" name="actif" value="1" id="actif" class="form-check-input" <?= (!isset($type['actif']) || $type['actif']) ? 'checked' : '' ?>>
                                <label for="actif" class="form-check-label"><?= _("Actif (disponible pour la configuration des fenêtres de saisie)") ?></label>
                            </div>

                            <div class="text-end">
                                <a href="/evaluations/types" class="btn btn-outline-secondary me-2"><?= _("Annuler") ?></a>
                                <button type="submit" class="btn btn-primary"><?= _("Enregistrer") ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer_able.php'; ?>
