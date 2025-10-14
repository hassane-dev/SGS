<?php
$title = "Inscriptions en Attente";
ob_start();
?>

<div class="container">
    <h1>Inscriptions en Attente de Validation</h1>
    <p>Liste des élèves qui ont été pré-inscrits et attendent la validation du paiement.</p>

    <table class="table table-striped mt-4">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Date de Naissance</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($eleves_en_attente)): ?>
                <tr>
                    <td colspan="4" class="text-center">Aucune inscription en attente.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($eleves_en_attente as $eleve): ?>
                    <tr>
                        <td><?= htmlspecialchars($eleve['nom']) ?></td>
                        <td><?= htmlspecialchars($eleve['prenom']) ?></td>
                        <td><?= htmlspecialchars($eleve['date_naissance']) ?></td>
                        <td>
                            <a href="/comptable/validate-form?eleve_id=<?= $eleve['id_eleve'] ?>" class="btn btn-primary btn-sm">Valider le Paiement</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/header.php';
?>