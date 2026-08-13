<?php
require_once __DIR__ . '/../../layouts/header_able.php';
require_once __DIR__ . '/../../layouts/sidebar_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Gestion d'Achats & Fournisseurs") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Demandes d'achats") ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="ph-duotone ph-warning-circle text-warning fs-1 d-block mb-3"></i>
                <h4 class="mb-2"><?= _("Édition non autorisée") ?></h4>
                <p class="text-muted"><?= _("Les demandes d'achat soumises ne peuvent pas être modifiées directement pour garantir la traçabilité des engagements budgétaires.") ?></p>
                <a href="/achats/demandes" class="btn btn-primary mt-3"><?= _("Retour aux demandes") ?></a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
