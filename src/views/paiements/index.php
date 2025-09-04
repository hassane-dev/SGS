<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold">Suivi des Paiements</h2>
            <p class="text-lg text-gray-600">Élève: <span class="font-semibold"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></span></p>
        </div>
        <a href="/paiements/create?eleve_id=<?= $eleve['id_eleve'] ?>" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Ajouter un Paiement
        </a>
    </div>

    <div class="bg-white shadow-md rounded">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($paiements)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                            Aucun paiement trouvé pour cet élève.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($paiements as $paiement): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($paiement['date_paiement']))) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars(ucfirst($paiement['type_paiement'])) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($paiement['montant']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                    $color = 'gray';
                                    if ($paiement['statut'] === 'paye') $color = 'green';
                                    if ($paiement['statut'] === 'partiel') $color = 'yellow';
                                    if ($paiement['statut'] === 'non_paye') $color = 'red';
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?= $color ?>-100 text-<?= $color ?>-800">
                                    <?= htmlspecialchars(ucfirst($paiement['statut'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        <a href="/eleves" class="text-blue-500 hover:underline">&larr; Retour à la liste des élèves</a>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
