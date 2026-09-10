<div class="bulletin-header text-center mb-4 position-relative">
    <?php if (!empty($bulletin['is_provisoire'])): ?>
        <div class="mb-2">
            <span class="badge bg-warning text-dark px-3 py-2 text-uppercase font-weight-bold" style="font-size: 1.1rem; letter-spacing: 2px;">
                <i class="ph-duotone ph-warning-circle me-1"></i> <?= _('BULLETIN PROVISOIRE') ?>
            </span>
        </div>
    <?php else: ?>
        <div class="mb-2">
            <span class="badge bg-success px-3 py-2 text-uppercase font-weight-bold" style="font-size: 1.1rem; letter-spacing: 2px;">
                <i class="ph-duotone ph-check-circle me-1"></i> <?= _('BULLETIN OFFICIEL') ?>
            </span>
        </div>
    <?php endif; ?>

    <h4 class="mb-1">Lycée <?= htmlspecialchars($bulletin['eleve']['nom_lycee']) ?></h4>
    <h5 class="text-muted mb-1"><?= _('Année Académique') ?> : <?= htmlspecialchars($bulletin['eleve']['annee_academique']) ?></h5>
    <h6 class="mb-0"><?= _('Bulletin de la Séquence :') ?> <?= htmlspecialchars($bulletin['sequence']['nom']) ?></h6>
</div>