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
                                        <th><?= _('Article') ?></th>
                                        <th><?= _('Prix') ?></th>
                                        <th><?= _('Stock') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($articles as $article): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($article['nom_article']) ?></td>
                                            <td><?= htmlspecialchars($article['prix']) ?></td>
                                            <td><?= htmlspecialchars($article['stock']) ?></td>
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
                                            <td colspan="4" class="text-center"><?= _('Aucun article trouvé') ?></td>
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
