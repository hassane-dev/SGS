<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Saisie des Notes (Étape 2/2)</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Choisir une période d'évaluation pour : <?= htmlspecialchars($classe['nom_classe']) ?> - <?= htmlspecialchars($matiere['nom_matiere']) ?>
            </h6>
        </div>
        <div class="card-body">
            <?php if (empty($evaluations)): ?>
                <div class="alert alert-warning">
                    Aucune période de saisie n'est actuellement ouverte pour cette matière. Veuillez contacter l'administration pour configurer les paramètres d'évaluation.
                </div>
                <a href="/evaluations/select_class" class="btn btn-secondary">Retour</a>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($evaluations as $eval): ?>
                        <a href="/evaluations/form?classe_id=<?= $classe['id_classe'] ?>&matiere_id=<?= $matiere['id_matiere'] ?>&sequence_id=<?= $eval['sequence_id'] ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?= htmlspecialchars($eval['sequence_nom']) ?></h5>
                                <small>Période: <?= (new DateTime($eval['date_ouverture_saisie']))->format('d/m/Y') ?> au <?= (new DateTime($eval['date_fermeture_saisie']))->format('d/m/Y') ?></small>
                            </div>
                            <p class="mb-1"><?= htmlspecialchars($eval['commentaire'] ?? "Saisir les notes pour cette période.") ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>