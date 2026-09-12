<?php
/**
 * Shared Official Administrative Header Component
 * Reused across ID Cards, Bulletins, Receipts, and Official School Documents.
 *
 * Exact administrative header identity from school ID Card settings:
 * - Left column: Primary administrative header (header_primary)
 * - Center column: Centered Lycée logo, school name, and motto (devise)
 * - Right column: Secondary administrative header (header_secondary)
 * - Details footer: Address, contacts, and ministerial authorization decree (arrêté)
 */

$lyceeData = $currentLycee ?? $lycee ?? [];

$logoUrl = $lyceeData['logo'] ?? '';
if ($logoUrl && strpos($logoUrl, 'http') !== 0 && strpos($logoUrl, '/') !== 0) {
    $logoUrl = '/' . $logoUrl;
}

$addressParts = array_filter([
    !empty($lyceeData['quartier']) ? $lyceeData['quartier'] : null,
    !empty($lyceeData['ruelle']) ? 'Ruelle ' . $lyceeData['ruelle'] : null,
    !empty($lyceeData['arrondissement']) ? 'Arrond. ' . $lyceeData['arrondissement'] : null,
    !empty($lyceeData['ville']) ? $lyceeData['ville'] : null,
    !empty($lyceeData['boite_postale']) ? 'BP: ' . $lyceeData['boite_postale'] : null,
]);

$contactParts = array_filter([
    !empty($lyceeData['tel']) ? 'Tél: ' . $lyceeData['tel'] : (!empty($lyceeData['telephone']) ? 'Tél: ' . $lyceeData['telephone'] : null),
    !empty($lyceeData['email']) ? 'Email: ' . $lyceeData['email'] : null,
]);
?>

<div class="admin-header-wrapper">
    <div class="admin-header-grid">
        <div class="admin-header-col admin-header-left">
            <?php if (!empty($lyceeData['header_primary'])): ?>
                <?= nl2br(htmlspecialchars($lyceeData['header_primary'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false)) ?>
            <?php else: ?>
                RÉPUBLIQUE DU TCHAD<br>
                Unité - Travail - Progrès<br>
                **********<br>
                MINISTÈRE DE L'ÉDUCATION NATIONALE
            <?php endif; ?>
        </div>

        <div class="admin-header-col admin-header-center">
            <?php if (!empty($logoUrl)): ?>
                <img src="<?= htmlspecialchars($logoUrl) ?>" class="admin-school-logo" alt="Logo Lycée">
            <?php endif; ?>
            <div class="admin-school-name">
                <?= htmlspecialchars($lyceeData['nom_lycee'] ?? 'ÉTABLISSEMENT SCOLAIRE') ?>
                <?php if (!empty($lyceeData['sigle'])): ?>
                    (<?= htmlspecialchars($lyceeData['sigle']) ?>)
                <?php endif; ?>
            </div>
            <?php if (!empty($lyceeData['devise'])): ?>
                <div class="admin-school-devise">« <?= htmlspecialchars($lyceeData['devise']) ?> »</div>
            <?php endif; ?>
        </div>

        <div class="admin-header-col admin-header-right" dir="auto">
            <?php if (!empty($lyceeData['header_secondary'])): ?>
                <?= nl2br(htmlspecialchars($lyceeData['header_secondary'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false)) ?>
            <?php else: ?>
                <?= htmlspecialchars($lyceeData['nom_lycee'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false) ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($addressParts) || !empty($contactParts) || !empty($lyceeData['arrete'])): ?>
        <div class="admin-header-details">
            <?php if (!empty($addressParts) || !empty($contactParts)): ?>
                <div>
                    <?php if (!empty($addressParts)): ?>
                        <span><?= htmlspecialchars(implode(' - ', $addressParts)) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($contactParts)): ?>
                        <?php if (!empty($addressParts)): ?> | <?php endif; ?>
                        <span><?= htmlspecialchars(implode(' | ', $contactParts)) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($lyceeData['arrete'])): ?>
                <div class="admin-header-arrete"><?= htmlspecialchars($lyceeData['arrete']) ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
