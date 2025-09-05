<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold">Historique d'Achats</h2>
            <p class="text-lg text-gray-600">Élève: <span class="font-semibold"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></span></p>
        </div>
        <a href="/boutique/achats/create?eleve_id=<?= $eleve['id_eleve'] ?>" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Nouvel Achat
        </a>
    </div>

    <div class="bg-white shadow-md rounded">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Article</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantité</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix Unitaire</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($achats as $achat): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars(date('d/m/Y', strtotime($achat['date_achat']))) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($achat['nom_article']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($achat['quantite']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($achat['prix']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($achat['prix'] * $achat['quantite']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        <a href="/eleves/details?id=<?= $eleve['id_eleve'] ?>" class="text-blue-500 hover:underline">&larr; Retour à la fiche de l'élève</a>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
