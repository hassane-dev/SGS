<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Gestion des Licences</h2>
        <a href="/licences/create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Générer une Licence
        </a>
    </div>

    <div class="bg-white shadow-md rounded">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lycée</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Début</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Fin</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durée</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($licences as $licence): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($licence['nom_lycee']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars(date('d/m/Y', strtotime($licence['date_debut']))) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars(date('d/m/Y', strtotime($licence['date_fin']))) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($licence['duree_mois']) ?> mois</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($licence['actif']): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Actif</span>
                            <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactif</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="/licences/edit?id=<?= $licence['id_licence'] ?>" class="text-indigo-600 hover:text-indigo-900">Modifier</a>
                            <form action="/licences/destroy" method="POST" class="inline-block ml-4" onsubmit="return confirm('Êtes-vous sûr ?');">
                                <input type="hidden" name="id" value="<?= $licence['id_licence'] ?>">
                                <button type="submit" class="text-red-600 hover:text-red-900">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
