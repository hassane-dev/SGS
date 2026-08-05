<?php
$title = _("Page non trouvée");
include __DIR__ . '/../layouts/header_able.php';
include __DIR__ . '/../layouts/sidebar_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Page Non Trouvée") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Accueil") ?></a></li>
                            <li class="breadcrumb-item"><?= _("Erreur 404") ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <h1 class="display-1 text-warning">404</h1>
                        <h2><?= _("Oups! Page introuvable.") ?></h2>
                        <p class="lead text-muted">
                            <?= _("Désolé, la page que vous recherchez n'existe pas ou a été déplacée.") ?>
                        </p>
                        <div class="mt-4">
                            <a href="/" class="btn btn-primary me-2">
                                <i class="ph-duotone ph-house me-1"></i> <?= _("Retour au Tableau de Bord") ?>
                            </a>
                            <button onclick="history.back()" class="btn btn-outline-secondary">
                                <i class="ph-duotone ph-arrow-left me-1"></i> <?= _("Retour en arrière") ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../layouts/footer_able.php';
?>