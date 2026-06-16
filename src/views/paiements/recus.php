<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>
<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title"><h2 class="mb-0"><?= $title ?></h2></div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/">Accueil</a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= $title ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body text-center p-5">
                <i class="ph-duotone ph-receipt fs-1 text-muted mb-3"></i>
                <h5>Interface en cours de développement</h5>
                <p class="text-muted">La centralisation et la réimpression des reçus seront bientôt disponibles ici.</p>
                <a href="/paiements" class="btn btn-primary mt-3">Retour au tableau de bord</a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
