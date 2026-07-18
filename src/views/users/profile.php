<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Mon Profil') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body text-center">
                        <img src="<?= htmlspecialchars($user['photo'] ?? '/assets/img/default-avatar.png') ?>" alt="Photo de profil" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                        <h4 class="mb-1"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h4>
                        <p class="text-muted mb-3"><?= htmlspecialchars($user['fonction'] ?? 'N/A') ?></p>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['success_message']) ?>
                        <?php unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['error_message']) ?>
                        <?php unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <!-- Preferences and Signatures -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="ph-duotone ph-gear me-2 text-primary"></i><?= _('Préférences & Signatures Numériques') ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="/profile/update-settings" method="POST" enctype="multipart/form-data">
                            <div class="row g-3">
                                <!-- Language selection -->
                                <div class="col-md-6 mb-3">
                                    <label for="langue_preferee" class="form-label font-weight-bold"><?= _('Langue préférée') ?></label>
                                    <select class="form-select" id="langue_preferee" name="langue_preferee">
                                        <option value="fr_FR" <?= (($parametres->langue_preferee ?? '') === 'fr_FR') ? 'selected' : '' ?>>Français (fr_FR)</option>
                                        <option value="en_US" <?= (($parametres->langue_preferee ?? '') === 'en_US') ? 'selected' : '' ?>>English (en_US)</option>
                                        <option value="ar" <?= (($parametres->langue_preferee ?? '') === 'ar') ? 'selected' : '' ?>>العربية (ar)</option>
                                    </select>
                                </div>

                                <!-- Theme selection -->
                                <div class="col-md-6 mb-3">
                                    <label for="theme_prefere" class="form-label font-weight-bold"><?= _('Thème d\'affichage') ?></label>
                                    <select class="form-select" id="theme_prefere" name="theme_prefere">
                                        <option value="light" <?= (($parametres->theme_prefere ?? '') === 'light') ? 'selected' : '' ?>><?= _('Clair') ?></option>
                                        <option value="dark" <?= (($parametres->theme_prefere ?? '') === 'dark') ? 'selected' : '' ?>><?= _('Sombre') ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <!-- User Signature upload & preview -->
                                <div class="col-md-6 mb-4">
                                    <label class="form-label font-weight-bold d-block"><?= _('Signature Numérique (PNG transparent)') ?></label>
                                    <?php if (!empty($parametres->signature)): ?>
                                        <div class="mb-2 p-2 border rounded bg-light text-center">
                                            <img src="<?= htmlspecialchars($parametres->signature) ?>" alt="Signature Actuelle" style="max-height: 80px; object-fit: contain;">
                                            <p class="small text-muted m-0 mt-1"><?= _('Signature active') ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" class="form-control mb-2" name="signature_file" accept="image/png, image/jpeg, image/jpg">
                                    <span class="text-muted small"><?= _('Ou dessinez directement ci-dessous :') ?></span>

                                    <!-- Canvas Signature Pad -->
                                    <div class="mt-2">
                                        <canvas id="signature-pad" class="border rounded bg-white w-100" style="height: 150px; cursor: crosshair; touch-action: none;"></canvas>
                                        <div class="d-flex justify-content-between mt-1">
                                            <button type="button" id="clear-signature" class="btn btn-sm btn-outline-danger"><?= _('Effacer le dessin') ?></button>
                                            <span class="small text-primary"><i class="ph-duotone ph-hand-pointing"></i> <?= _('Signez ici') ?></span>
                                        </div>
                                        <input type="hidden" id="signature-base64" name="signature_base64">
                                    </div>
                                </div>

                                <!-- User Cachet upload & preview -->
                                <div class="col-md-6 mb-4">
                                    <label class="form-label font-weight-bold d-block"><?= _('Cachet Nominatif / Tampon officiel (PNG transparent)') ?></label>
                                    <?php if (!empty($parametres->cachet)): ?>
                                        <div class="mb-2 p-2 border rounded bg-light text-center">
                                            <img src="<?= htmlspecialchars($parametres->cachet) ?>" alt="Cachet Actuel" style="max-height: 80px; object-fit: contain;">
                                            <p class="small text-muted m-0 mt-1"><?= _('Cachet actif') ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" class="form-control mb-2" name="cachet_file" accept="image/png, image/jpeg, image/jpg">
                                    <div class="form-text text-muted small"><?= _('Téléversez une image détourée (.png) de votre cachet officiel.') ?></div>
                                </div>
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="notifications_actives" name="notifications_actives" value="1" <?= ($parametres->notifications_actives) ? 'checked' : '' ?>>
                                <label class="form-check-label font-weight-bold" for="notifications_actives">
                                    <?= _('Activer les notifications système par email/dashboard') ?>
                                </label>
                            </div>

                            <button type="submit" class="btn btn-success"><i class="ph-duotone ph-floppy-disk me-2"></i><?= _('Enregistrer les paramètres') ?></button>
                        </form>
                    </div>
                </div>

                <!-- Password change -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="ph-duotone ph-lock me-2 text-primary"></i><?= _('Changer mon mot de passe') ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="/profile/update-password" method="POST">
                            <div class="mb-3">
                                <label for="current_password" class="form-label"><?= _('Mot de passe actuel') ?></label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label"><?= _('Nouveau mot de passe') ?></label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label"><?= _('Confirmer le nouveau mot de passe') ?></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary"><?= _('Mettre à jour le mot de passe') ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>
<!-- [ Main Content ] end -->

<!-- Signature Pad Script -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const canvas = document.getElementById('signature-pad');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let drawing = false;

    // Correct the canvas scale based on layout size
    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;

    window.addEventListener('resize', () => {
        // Save drawing
        const temp = canvas.toDataURL();
        canvas.width = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;
        const img = new Image();
        img.onload = function() {
            ctx.drawImage(img, 0, 0);
        };
        img.src = temp;
    });

    // Event listeners for drawing
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseout', stopDrawing);

    // Touch support
    canvas.addEventListener('touchstart', (e) => {
        const t = e.touches[0];
        startDrawing(t);
        e.preventDefault();
    });
    canvas.addEventListener('touchmove', (e) => {
        const t = e.touches[0];
        draw(t);
        e.preventDefault();
    });
    canvas.addEventListener('touchend', () => stopDrawing());

    function startDrawing(e) {
        drawing = true;
        ctx.beginPath();
        ctx.moveTo(getX(e), getY(e));
    }

    function draw(e) {
        if (!drawing) return;
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#2c3e50';
        ctx.lineTo(getX(e), getY(e));
        ctx.stroke();
    }

    function stopDrawing() {
        if (!drawing) return;
        drawing = false;
        document.getElementById('signature-base64').value = canvas.toDataURL('image/png');
    }

    function getX(e) {
        const rect = canvas.getBoundingClientRect();
        return e.clientX - rect.left;
    }

    function getY(e) {
        const rect = canvas.getBoundingClientRect();
        return e.clientY - rect.top;
    }

    // Clear Canvas
    document.getElementById('clear-signature').addEventListener('click', () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        document.getElementById('signature-base64').value = '';
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
