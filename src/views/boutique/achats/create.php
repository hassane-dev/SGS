<?php require_once __DIR__ . '/../../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/eleves"><?= _('Élèves') ?></a></li>
                            <li class="breadcrumb-item"><a href="/boutique/achats?eleve_id=<?= $eleve['id_eleve'] ?>"><?= _('Historique des Achats') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Enregistrer un Achat') ?></li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Enregistrer un Achat') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-4"><?= _('Élève') ?>: <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h5>
                        <form action="/boutique/achats/store" method="POST">
                            <input type="hidden" name="eleve_id" value="<?= htmlspecialchars($eleve['id_eleve']) ?>">

                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label" for="article_id"><?= _('Article') ?></label>
                                    <select name="article_id" id="article_id" class="form-select" required>
                                        <option value=""><?= _('-- Choisir un article --') ?></option>
                                        <?php foreach ($articles as $article): ?>
                                            <option value="<?= $article['id_article'] ?>"><?= htmlspecialchars($article['nom_article']) ?> (<?= htmlspecialchars($article['prix']) ?> FCFA)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="quantite"><?= _('Quantité') ?></label>
                                    <input type="number" name="quantite" id="quantite" value="1" class="form-control" required>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary"><?= _('Enregistrer l\'Achat') ?></button>
                                <a href="/boutique/achats?eleve_id=<?= $eleve['id_eleve'] ?>" class="btn btn-link-secondary"><?= _('Annuler') ?></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
