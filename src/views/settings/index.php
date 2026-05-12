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
                            <h2 class="mb-0"><?= _('Paramètres') ?></h2>
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
                    <div class="card-header">
                        <h5><?= _('Configuration de l\'établissement') ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-success" role="alert">
                                <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>

                        <form action="/settings" method="POST" enctype="multipart/form-data">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="nom_lycee" class="form-label"><?= _('Nom du Lycée') ?></label>
                                    <input type="text" name="nom_lycee" id="nom_lycee" value="<?= htmlspecialchars($settings['nom_lycee'] ?? '') ?>" class="form-control">
                                </div>

                                <div class="col-md-6">
                                    <label for="logo" class="form-label"><?= _('Logo du Lycée') ?></label>
                                    <input type="file" name="logo" id="logo" class="form-control">
                                    <?php if (!empty($settings['logo'])): ?>
                                        <div class="mt-2">
                                            <img src="<?= htmlspecialchars($settings['logo']) ?>" alt="Logo" class="img-thumbnail" style="max-height: 50px;">
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label d-block"><?= _('Type de Lycée') ?></label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="type_lycee" id="type_public" value="public" <?= ($settings['type_lycee'] ?? '') === 'public' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="type_public"><?= _('Public') ?></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="type_lycee" id="type_prive" value="prive" <?= ($settings['type_lycee'] ?? '') === 'prive' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="type_prive"><?= _('Privé') ?></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="type_lycee" id="type_semi" value="semi-public" <?= ($settings['type_lycee'] ?? '') === 'semi-public' || ($settings['type_lycee'] ?? '') === 'parapublic' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="type_semi"><?= _('Semi-public') ?></label>
                                    </div>
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

                                <div class="col-md-6">
                                    <label for="header_primary" class="form-label"><?= _('En-tête Administrative (Principale)') ?></label>
                                    <textarea name="header_primary" id="header_primary" class="form-control" rows="3" placeholder="République du Tchad&#10;Unité - Travail - Progrès&#10;**********&#10;Ministère de l’Éducation Nationale"><?= htmlspecialchars($settings['header_primary'] ?? '') ?></textarea>
                                    <small class="text-muted"><?= _('Utilisez des retours à la ligne pour structurer l\'en-tête.') ?></small>
                                </div>

                                <div class="col-md-6">
                                    <label for="header_secondary" class="form-label"><?= _('En-tête Administrative (Secondaire / Arabe)') ?></label>
                                    <textarea name="header_secondary" id="header_secondary" class="form-control" rows="3" dir="auto"><?= htmlspecialchars($settings['header_secondary'] ?? '') ?></textarea>
                                    <small class="text-muted"><?= _('Optionnel. Utile pour les lycées bilingues.') ?></small>
                                </div>

                                <div class="col-12">
                                    <label class="form-label d-block"><?= _('Modalité de Paiement des Frais de Scolarité') ?></label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="modalite_paiement" id="mod_avant" value="avant_inscription" <?= ($settings['modalite_paiement'] ?? '') === 'avant_inscription' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="mod_avant"><?= _('Avant l\'inscription') ?></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="modalite_paiement" id="mod_apres" value="apres_test" <?= ($settings['modalite_paiement'] ?? '') === 'apres_test' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="mod_apres"><?= _('Après le test d\'entrée') ?></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="modalite_paiement" id="mod_frac" value="fractionne" <?= ($settings['modalite_paiement'] ?? '') === 'fractionne' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="mod_frac"><?= _('Paiement fractionné') ?></label>
                                    </div>
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
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
