<div class="student-info mb-4">
    <?php if (!empty($bulletin['eleve']['identifiant_public'])): ?>
        <p><strong><?= _('Matricule') ?> :</strong> <?= htmlspecialchars($bulletin['eleve']['identifiant_public'] ?? '') ?></p>
    <?php endif; ?>
    <p><strong><?= _('Nom & Prénom') ?> :</strong> <?= htmlspecialchars($bulletin['eleve']['prenom'] . ' ' . $bulletin['eleve']['nom']) ?></p>
    <p><strong><?= _('Date de Naissance') ?> :</strong> <?= htmlspecialchars($bulletin['eleve']['date_naissance']) ?></p>
    <p><strong><?= _('Classe') ?> :</strong> <?= htmlspecialchars($bulletin['eleve']['nom_classe']) ?></p>
</div>