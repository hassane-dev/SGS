<?php include __DIR__ . '/../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _('Tableau de bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/evaluations/select_class"><?= _('Saisie des Notes') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Accès Refusé') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card border-danger">
                    <div class="card-header bg-light-danger text-danger">
                        <h5 class="mb-0 text-danger">
                            <i class="ph-duotone ph-warning-circle me-2 fs-4 align-middle"></i>
                            <?= htmlspecialchars($title ?? _('Accès Refusé')) ?>
                        </h5>
                    </div>
                    <div class="card-body text-center p-4">
                        <i class="ph-duotone ph-lock-key-open text-danger display-4 mb-3 d-block"></i>
                        <h4 class="text-dark mb-3"><?= htmlspecialchars($message ?? _('La période de saisie est fermée.')) ?></h4>
                        <p class="text-muted mb-4"><?= _('Veuillez contacter l\'administration de l\'établissement si vous pensez qu\'il s\'agit d\'une erreur ou pour solliciter un déblocage exceptionnel.') ?></p>
                        <a href="/evaluations/select_class" class="btn btn-primary d-inline-flex align-items-center">
                            <i class="ph-duotone ph-arrow-left me-2"></i>
                            <?= _('Retour à la sélection des classes') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer_able.php'; ?>
