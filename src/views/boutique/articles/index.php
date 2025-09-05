<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Gestion de la Boutique</h2>
        <a href="/boutique/articles/create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Ajouter un Article
        </a>
    </div>

    <div class="bg-white shadow-md rounded">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Article</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($articles as $article): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($article['nom_article']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($article['prix']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($article['stock']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="/boutique/articles/edit?id=<?= $article['id_article'] ?>" class="text-indigo-600 hover:text-indigo-900">Modifier</a>
                            <form action="/boutique/articles/destroy" method="POST" class="inline-block ml-4" onsubmit="return confirm('Êtes-vous sûr ?');">
                                <input type="hidden" name="id" value="<?= $article['id_article'] ?>">
                                <button type="submit" class="text-red-600 hover:text-red-900">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
