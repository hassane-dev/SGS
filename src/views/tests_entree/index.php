<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold">Gestion des Tests d'Entrée</h2>
            <p class="text-lg text-gray-600">Élève: <span class="font-semibold"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></span></p>
        </div>
        <a href="/tests_entree/create?eleve_id=<?= $eleve['id_eleve'] ?>" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Nouveau Test
        </a>
    </div>

    <div class="bg-white shadow-md rounded">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date du Test</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Classe Visée</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($tests)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                            Aucun test d'entrée trouvé pour cet élève.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tests as $test): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars(date('d/m/Y', strtotime($test['date_test']))) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($test['nom_classe']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($test['score']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <form action="/tests_entree/destroy" method="POST" class="inline-block" onsubmit="return confirm('Êtes-vous sûr ?');">
                                    <input type="hidden" name="id" value="<?= $test['id_test'] ?>">
                                    <input type="hidden" name="eleve_id" value="<?= $eleve['id_eleve'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        <a href="/eleves/details?id=<?= $eleve['id_eleve'] ?>" class="text-blue-500 hover:underline">&larr; Retour à la fiche de l'élève</a>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
