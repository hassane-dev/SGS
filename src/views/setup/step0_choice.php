<?php require_once __DIR__ . '/../layouts/header_centered.php'; ?>

<div class="col-md-8 col-lg-6">
    <div class="card shadow-sm">
        <div class="card-body p-5 text-center">
            <h1 class="h2 fw-bold mb-3"><?= _('Bienvenue dans l\'installation') ?></h1>
            <p class="text-muted mb-4"><?= _('Veuillez choisir le mode d\'installation pour votre application.') ?></p>

            <form action="/setup/choice" method="POST">
                <div class="list-group">
                    <label class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">
                                <input type="radio" name="install_mode" value="single" class="form-check-input me-2" checked>
                                <?= _('Installation Mono-école') ?>
                            </h5>
                        </div>
                        <p class="mb-1 text-muted"><?= _('Pour une école privée ou parapublique gérant uniquement ses propres données.') ?></p>
                    </label>
                    <label class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">
                                <input type="radio" name="install_mode" value="multi" class="form-check-input me-2">
                                <?= _('Installation Multi-écoles') ?>
                            </h5>
                        </div>
                        <p class="mb-1 text-muted"><?= _('Pour une administration nationale ou régionale gérant plusieurs écoles.') ?></p>
                    </label>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <?= _('Continuer') ?> &rarr;
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>