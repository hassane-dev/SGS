<?php require_once __DIR__ . '/../layouts/header_centered.php'; ?>

<div class="col-md-6 col-lg-4">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h2 class="card-title text-center fw-bold h3 mb-4"><?= _('Connexion') ?></h2>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= _('Email ou mot de passe incorrect.') ?>
                </div>
            <?php endif; ?>

            <form action="/login" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label"><?= _('Email') ?></label>
                    <input type="email" name="email" id="email" class="form-control" required value="HasMixiOne@mine.io">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label"><?= _('Mot de passe') ?></label>
                    <input type="password" name="password" id="password" class="form-control" required value="H@s7511mat9611">
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <?= _('Se connecter') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>