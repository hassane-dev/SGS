<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Gestion des Élèves</h2>
        <a href="/eleves/create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Ajouter un Élève
        </a>
    </div>

    <div class="bg-white shadow-md rounded">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom Complet</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($eleves)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                            Aucun élève trouvé.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($eleves as $eleve): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($eleve['photo'])): ?>
                                    <img src="<?= htmlspecialchars($eleve['photo']) ?>" alt="Photo de l'élève" class="h-10 w-10 rounded-full object-cover">
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($eleve['email']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="/paiements?eleve_id=<?= $eleve['id_eleve'] ?>" class="text-yellow-600 hover:text-yellow-900">Paiements</a>
                                <a href="/inscriptions/show?eleve_id=<?= $eleve['id_eleve'] ?>" class="text-green-600 hover:text-green-900 ml-4">Inscrire</a>
                                <a href="/eleves/edit?id=<?= $eleve['id_eleve'] ?>" class="text-indigo-600 hover:text-indigo-900 ml-4">Modifier</a>
                                <form action="/eleves/destroy" method="POST" class="inline-block ml-4" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet élève ?');">
                                    <input type="hidden" name="id" value="<?= $eleve['id_eleve'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
