<?php require_once __DIR__ . '/../../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Boutique') ?></li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Gestion de la Boutique') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Dashboard Stats ] start -->
        <div class="row">
            <div class="col-md-6 col-xl-3">
                <div class="card bg-blue-500 text-white widget-visitor-card">
                    <div class="card-body text-center">
                        <h2 class="text-white"><?= $stats['total_articles'] ?></h2>
                        <h6 class="text-white"><?= _('Produits en stock') ?></h6>
                        <i class="ph-duotone ph-package"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card bg-red-500 text-white widget-visitor-card">
                    <div class="card-body text-center">
                        <h2 class="text-white"><?= $stats['out_of_stock'] ?></h2>
                        <h6 class="text-white"><?= _('Produits en rupture') ?></h6>
                        <i class="ph-duotone ph-warning-circle"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card bg-green-500 text-white widget-visitor-card">
                    <div class="card-body text-center">
                        <h2 class="text-white"><?= number_format($stats['revenue_today'], 0, ',', ' ') ?></h2>
                        <h6 class="text-white"><?= _('CA du Jour (FCFA)') ?></h6>
                        <i class="ph-duotone ph-currency-circle-dollar"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card bg-purple-500 text-white widget-visitor-card">
                    <div class="card-body text-center">
                        <h2 class="text-white"><?= number_format($stats['revenue_month'], 0, ',', ' ') ?></h2>
                        <h6 class="text-white"><?= _('CA du Mois (FCFA)') ?></h6>
                        <i class="ph-duotone ph-chart-line-up"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Dashboard Stats ] end -->

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><?= _('Liste des Articles') ?></h5>
                        <a href="/boutique/articles/create" class="btn btn-primary d-inline-flex align-items-center">
                            <i class="ph-duotone ph-plus-circle me-2"></i>
                            <?= _('Ajouter un Article') ?>
                        </a>
                    </div>
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?= _('Image') ?></th>
                                        <th><?= _('Article') ?></th>
                                        <th><?= _('Catégorie') ?></th>
                                        <th><?= _('Prix') ?></th>
                                        <th><?= _('Stock') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($articles as $article): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($article['image'])): ?>
                                                    <img src="<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['nom_article']) ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light d-flex align-items-center justify-content-center rounded" style="width: 50px; height: 50px;">
                                                        <i class="ph-duotone ph-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($article['nom_article']) ?></td>
                                            <td><span class="badge bg-light-secondary text-muted"><?= htmlspecialchars($article['categorie'] ?? _('Divers')) ?></span></td>
                                            <td>
                                                <?= number_format($article['prix'], 0, ',', ' ') ?> FCFA
                                                <?php if (!empty($article['ancien_prix'])): ?>
                                                    <br><small class="text-muted text-decoration-line-through"><?= number_format($article['ancien_prix'], 0, ',', ' ') ?> FCFA</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($article['stock'] <= 0): ?>
                                                    <span class="badge bg-light-danger text-danger"><?= _('Rupture') ?></span>
                                                <?php elseif ($article['stock'] <= 5): ?>
                                                    <span class="badge bg-light-warning text-warning"><?= $article['stock'] ?> (<?= _('Faible') ?>)</span>
                                                <?php else: ?>
                                                    <span class="badge bg-light-success text-success"><?= $article['stock'] ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="/boutique/articles/edit?id=<?= $article['id_article'] ?>" class="btn btn-sm btn-light-primary me-1">
                                                    <i class="ph-duotone ph-pencil-simple"></i>
                                                </a>
                                                <form action="/boutique/articles/destroy" method="POST" class="d-inline-block" onsubmit="return confirm('<?= _('Êtes-vous sûr ?') ?>');">
                                                    <input type="hidden" name="id" value="<?= $article['id_article'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-light-danger">
                                                        <i class="ph-duotone ph-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($articles)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center"><?= _('Aucun article trouvé') ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
