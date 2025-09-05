<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto">
    <div class="flex items-center mb-6">
        <?php if (!empty($eleve['photo'])): ?>
            <img src="<?= htmlspecialchars($eleve['photo']) ?>" alt="Photo de l'élève" class="h-20 w-20 rounded-full object-cover mr-4">
        <?php endif; ?>
        <div>
            <h2 class="text-2xl font-bold"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h2>
            <p class="text-gray-600"><?= htmlspecialchars($eleve['email']) ?></p>
        </div>
    </div>

    <div class="bg-white shadow-md rounded p-6">
        <h3 class="text-xl font-bold mb-4">Historique des Inscriptions</h3>
        <div class="mb-4 flex space-x-4">
            <a href="/boutique/achats?eleve_id=<?= $eleve['id_eleve'] ?>" class="text-yellow-600 hover:underline">Historique des Achats &rarr;</a>
            <a href="/tests_entree?eleve_id=<?= $eleve['id_eleve'] ?>" class="text-purple-600 hover:underline">Tests d'Entrée &rarr;</a>
        </div>
        <table class="min-w-full table-auto">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Année Académique</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Classe</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($etudes)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                            Aucune inscription trouvée.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($etudes as $etude): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($etude['annee_academique']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($etude['nom_classe'] . ' (' . $etude['serie'] . ')') ?></td>
                             <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($etude['actif']): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Actif</span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="/bulletin/show?etude_id=<?= $etude['id_etude'] ?>" class="text-blue-600 hover:text-blue-900">Voir Bulletin</a>
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
