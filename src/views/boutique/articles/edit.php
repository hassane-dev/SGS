<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6">Modifier un Article</h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/boutique/articles/update" method="POST">
            <input type="hidden" name="id_article" value="<?= htmlspecialchars($article['id_article']) ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="md:col-span-2">
                    <label for="nom_article" class="block text-gray-700 text-sm font-bold mb-2">Nom de l'Article</label>
                    <input type="text" name="nom_article" id="nom_article" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($article['nom_article']) ?>" required>
                </div>

                <div>
                    <label for="prix" class="block text-gray-700 text-sm font-bold mb-2">Prix</label>
                    <input type="number" step="0.01" name="prix" id="prix" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($article['prix']) ?>" required>
                </div>

                <div>
                    <label for="stock" class="block text-gray-700 text-sm font-bold mb-2">Stock</label>
                    <input type="number" name="stock" id="stock" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($article['stock']) ?>">
                </div>

                <?php if (Auth::get('role') === 'super_admin_national'): ?>
                <div class="md:col-span-2">
                    <label for="lycee_id" class="block text-gray-700 text-sm font-bold mb-2">Lycée</label>
                    <select name="lycee_id" id="lycee_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <?php foreach ($lycees as $lycee): ?>
                            <option value="<?= $lycee['id_lycee'] ?>" <?= $article['lycee_id'] == $lycee['id_lycee'] ? 'selected' : '' ?>><?= htmlspecialchars($lycee['nom_lycee']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                    <input type="hidden" name="lycee_id" value="<?= $article['lycee_id'] ?>">
                <?php endif; ?>

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/boutique/articles" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Annuler</a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
