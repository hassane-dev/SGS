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
                            <li class="breadcrumb-item" aria-current="page"><?= _('Paramètres du Lycée') ?></li>
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
                        <h5>Informations Administratives</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success">Les paramètres ont été mis à jour avec succès.</div>
                        <?php elseif (isset($_GET['error'])): ?>
                            <div class="alert alert-danger">Une erreur est survenue lors de la mise à jour.</div>
                        <?php endif; ?>

                        <form action="/param-lycee/update" method="POST" enctype="multipart/form-data">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="nom_lycee" class="form-label">Nom officiel du lycée</label>
                                    <input type="text" id="nom_lycee" name="nom_lycee" class="form-control" value="<?= htmlspecialchars($params['nom_lycee'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="sigle" class="form-label">Sigle ou abréviation</label>
                                    <input type="text" id="sigle" name="sigle" class="form-control" value="<?= htmlspecialchars($params['sigle'] ?? '') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label for="tel" class="form-label">Téléphone principal</label>
                                    <input type="text" id="tel" name="tel" class="form-control" value="<?= htmlspecialchars($params['tel'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Adresse e-mail officielle</label>
                                    <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($params['email'] ?? '') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label for="ville" class="form-label">Ville</label>
                                    <input type="text" id="ville" name="ville" class="form-control" value="<?= htmlspecialchars($params['ville'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="quartier" class="form-label">Quartier</label>
                                    <input type="text" id="quartier" name="quartier" class="form-control" value="<?= htmlspecialchars($params['quartier'] ?? '') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label for="ruelle" class="form-label">Ruelle</label>
                                    <input type="text" id="ruelle" name="ruelle" class="form-control" value="<?= htmlspecialchars($params['ruelle'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="boite_postale" class="form-label">Boîte Postale</label>
                                    <input type="text" id="boite_postale" name="boite_postale" class="form-control" value="<?= htmlspecialchars($params['boite_postale'] ?? '') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label for="arrete" class="form-label">Arrêté d’ouverture</label>
                                    <input type="text" id="arrete" name="arrete" class="form-control" value="<?= htmlspecialchars($params['arrete'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="arrondissement" class="form-label">Arrondissement</label>
                                    <input type="text" id="arrondissement" name="arrondissement" class="form-control" value="<?= htmlspecialchars($params['arrondissement'] ?? '') ?>">
                                </div>

                                <div class="col-12">
                                    <label for="devise" class="form-label">Devise ou slogan du lycée</label>
                                    <input type="text" id="devise" name="devise" class="form-control" value="<?= htmlspecialchars($params['devise'] ?? '') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label for="logo" class="form-label">Logo officiel du lycée</label>
                                    <input type="file" id="logo" name="logo" class="form-control">
                                    <input type="hidden" name="current_logo" value="<?= htmlspecialchars($params['logo'] ?? '') ?>">
                                    <?php if (!empty($params['logo'])): ?>
                                        <img src="<?= htmlspecialchars($params['logo']) ?>" alt="Logo actuel" class="img-thumbnail mt-2" style="max-height: 100px;">
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label for="type_lycee" class="form-label">Type de lycée</label>
                                    <select id="type_lycee" name="type_lycee" class="form-select">
                                        <option value="public" <?= ($params['type_lycee'] ?? '') == 'public' ? 'selected' : '' ?>>Public</option>
                                        <option value="prive" <?= ($params['type_lycee'] ?? '') == 'prive' ? 'selected' : '' ?>>Privé</option>
                                        <option value="semi-public" <?= ($params['type_lycee'] ?? '') == 'semi-public' ? 'selected' : '' ?>>Semi-public</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <div class="form-check">
                                        <input type="checkbox" id="boutique" name="boutique" class="form-check-input" value="1" <?= !empty($params['boutique']) ? 'checked' : '' ?>>
                                        <label for="boutique" class="form-check-label">Activer la gestion de la boutique</label>
                                    </div>
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