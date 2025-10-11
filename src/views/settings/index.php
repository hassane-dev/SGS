<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= _('Paramètres Généraux') ?></h1>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary"><?= _('Configuration de l\'établissement') ?></h6>
        </div>
        <div class="card-body">
            <form action="/settings" method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nom_lycee" class="form-label"><?= _('Nom du Lycée') ?></label>
                        <input type="text" name="nom_lycee" id="nom_lycee" value="<?= htmlspecialchars($settings['nom_lycee'] ?? '') ?>" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label for="type_lycee" class="form-label"><?= _('Type de Lycée') ?></label>
                        <select name="type_lycee" id="type_lycee" class="form-select">
                            <option value="public" <?= ($settings['type_lycee'] ?? '') === 'public' ? 'selected' : '' ?>><?= _('Public') ?></option>
                            <option value="prive" <?= ($settings['type_lycee'] ?? '') === 'prive' ? 'selected' : '' ?>><?= _('Privé') ?></option>
                            <option value="parapublic" <?= ($settings['type_lycee'] ?? '') === 'parapublic' ? 'selected' : '' ?>><?= _('Parapublic') ?></option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="annee_academique_id" class="form-label"><?= _('Année Académique Active') ?></label>
                        <select name="annee_academique_id" id="annee_academique_id" class="form-select">
                            <option value=""><?= _('-- Sélectionner une année --') ?></option>
                            <?php foreach ($annees_academiques as $annee): ?>
                                <option value="<?= $annee['id'] ?>" <?= ($settings['annee_academique_id'] ?? '') == $annee['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($annee['libelle']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="nombre_devoirs_par_trimestre" class="form-label"><?= _('Nombre de devoirs par trimestre') ?></label>
                        <input type="number" name="nombre_devoirs_par_trimestre" id="nombre_devoirs_par_trimestre" value="<?= htmlspecialchars($settings['nombre_devoirs_par_trimestre'] ?? '2') ?>" class="form-control">
                    </div>

                    <div class="col-12">
                        <label for="modalite_paiement" class="form-label"><?= _('Modalité de Paiement des Frais de Scolarité') ?></label>
                        <select name="modalite_paiement" id="modalite_paiement" class="form-select">
                            <option value="avant_inscription" <?= ($settings['modalite_paiement'] ?? '') === 'avant_inscription' ? 'selected' : '' ?>><?= _('Avant l\'inscription') ?></option>
                            <option value="apres_test" <?= ($settings['modalite_paiement'] ?? '') === 'apres_test' ? 'selected' : '' ?>><?= _('Après le test d\'entrée') ?></option>
                            <option value="fractionne" <?= ($settings['modalite_paiement'] ?? '') === 'fractionne' ? 'selected' : '' ?>><?= _('Paiement fractionné') ?></option>
                        </select>
                    </div>

                    <div class="col-12 mt-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="multilingue_actif" value="1" id="multilingue_actif" <?= !empty($settings['multilingue_actif']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="multilingue_actif"><?= _('Activer le mode multilingue') ?></label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="biometrie_actif" value="1" id="biometrie_actif" <?= !empty($settings['biometrie_actif']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="biometrie_actif"><?= _('Activer la biométrie') ?></label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="confidentialite_nationale" value="1" id="confidentialite_nationale" <?= !empty($settings['confidentialite_nationale']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="confidentialite_nationale"><?= _('Appliquer la confidentialité nationale') ?></label>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary"><?= _('Enregistrer les Paramètres') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>