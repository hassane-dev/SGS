<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Gestion des Classes</h2>
        <a href="/classes/create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Ajouter une Classe
        </a>
    </div>

    <div class="bg-white shadow-md rounded">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom de la Classe</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cycle</th>
                    <?php if (Auth::get('role') === 'super_admin_national'): ?>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lycée</th>
                    <?php endif; ?>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($classes)): ?>
                    <tr>
                        <td colspan="<?= Auth::get('role') === 'super_admin_national' ? '4' : '3' ?>" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                            Aucune classe trouvée.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($classes as $classe): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($classe['nom_classe']) ?> (<?= htmlspecialchars($classe['serie']) ?>)</td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($classe['nom_cycle']) ?></td>
                            <?php if (Auth::get('role') === 'super_admin_national'): ?>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($classe['nom_lycee']) ?></td>
                            <?php endif; ?>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="/matieres/assign?class_id=<?= $classe['id_classe'] ?>" class="text-green-600 hover:text-green-900">Assigner Matières</a>
                                <a href="/classes/edit?id=<?= $classe['id_classe'] ?>" class="text-indigo-600 hover:text-indigo-900 ml-4">Modifier</a>
                                <form action="/classes/destroy" method="POST" class="inline-block ml-4" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette classe ?');">
                                    <input type="hidden" name="id" value="<?= $classe['id_classe'] ?>">
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
