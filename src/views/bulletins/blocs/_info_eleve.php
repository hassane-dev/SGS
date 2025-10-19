<div class="student-info mb-4">
    <p><strong>Nom & Prénom :</strong> <?= htmlspecialchars($bulletin['eleve']['prenom'] . ' ' . $bulletin['eleve']['nom']) ?></p>
    <p><strong>Date de Naissance :</strong> <?= htmlspecialchars($bulletin['eleve']['date_naissance']) ?></p>
    <p><strong>Classe :</strong> <?= htmlspecialchars($bulletin['eleve']['nom_classe']) ?></p>
</div>