<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= htmlspecialchars($title) ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Paramètres Généraux') ?></li>
                        </ul>
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
                        <h5>Configuration Globale</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success">Les paramètres ont été mis à jour avec succès.</div>
                        <?php elseif (isset($_GET['error'])): ?>
                            <div class="alert alert-danger">Une erreur est survenue lors de la mise à jour.</div>
                        <?php endif; ?>

                        <form action="/param-general/update" method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="devise_pays" class="form-label">Nom complet de la devise</label>
                                    <input type="text" id="devise_pays" name="devise_pays" class="form-control" placeholder="Ex: Franc CFA" value="<?= htmlspecialchars($params['devise_pays'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="monnaie" class="form-label">Symbole monétaire</label>
                                    <input type="text" id="monnaie" name="monnaie" class="form-control" placeholder="Ex: FCFA" value="<?= htmlspecialchars($params['monnaie'] ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label for="modalite_paiement" class="form-label">Modalités de paiement autorisées</label>
                                    <input type="text" id="modalite_paiement" name="modalite_paiement" class="form-control" placeholder="Ex: Espèces, Versement, Mobile Money" value="<?= htmlspecialchars($params['modalite_paiement'] ?? '') ?>">
                                    <small class="form-text text-muted">Séparez les différentes modalités par une virgule.</small>
                                </div>

                                <div class="col-md-4">
                                    <label for="nb_langue" class="form-label">Nombre de langues sur les documents</label>
                                    <select id="nb_langue" name="nb_langue" class="form-select">
                                        <option value="1" <?= ($params['nb_langue'] ?? 1) == 1 ? 'selected' : '' ?>>1</option>
                                        <option value="2" <?= ($params['nb_langue'] ?? 1) == 2 ? 'selected' : '' ?>>2</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="langue_1" class="form-label">Première langue</label>
                                    <input type="text" id="langue_1" name="langue_1" class="form-control" placeholder="Ex: Français" value="<?= htmlspecialchars($params['langue_1'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="langue_2" class="form-label">Deuxième langue (si applicable)</label>
                                    <input type="text" id="langue_2" name="langue_2" class="form-control" placeholder="Ex: Anglais" value="<?= htmlspecialchars($params['langue_2'] ?? '') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label for="sequence_annuelle" class="form-label">Type de séquence annuelle</label>
                                     <select id="sequence_annuelle" name="sequence_annuelle" class="form-select">
                                        <option value="Trimestrielle" <?= ($params['sequence_annuelle'] ?? '') == 'Trimestrielle' ? 'selected' : '' ?>>Trimestrielle</option>
                                        <option value="Semestrielle" <?= ($params['sequence_annuelle'] ?? '') == 'Semestrielle' ? 'selected' : '' ?>>Semestrielle</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="mode_cycle" class="form-label">Mode de gestion des cycles</label>
                                    <select id="mode_cycle" name="mode_cycle" class="form-select">
                                        <option value="separe_ceg_lycee" <?= ($params['mode_cycle'] ?? '') == 'separe_ceg_lycee' ? 'selected' : '' ?>>Séparer CEG et Lycée</option>
                                        <option value="lycee_unique" <?= ($params['mode_cycle'] ?? '') == 'lycee_unique' ? 'selected' : '' ?>>Lycée Unifié (tout le secondaire)</option>
                                    </select>
                                    <small class="form-text text-muted">Détermine si les classes sont automatiquement assignées au CEG/Lycée, ou si toutes appartiennent à un cycle unique "Lycée".</small>
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>