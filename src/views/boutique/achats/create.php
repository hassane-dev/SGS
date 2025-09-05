<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-2">Enregistrer un Achat</h2>
    <p class="text-lg text-gray-600 mb-6">Pour l'élève: <span class="font-semibold"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></span></p>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/boutique/achats/store" method="POST">
            <input type="hidden" name="eleve_id" value="<?= htmlspecialchars($eleve['id_eleve']) ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="md:col-span-2">
                    <label for="article_id" class="block text-gray-700 text-sm font-bold mb-2">Article</label>
                    <select name="article_id" id="article_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <option value="">-- Choisir un article --</option>
                        <?php foreach ($articles as $article): ?>
                            <option value="<?= $article['id_article'] ?>"><?= htmlspecialchars($article['nom_article']) ?> (<?= htmlspecialchars($article['prix']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="quantite" class="block text-gray-700 text-sm font-bold mb-2">Quantité</label>
                    <input type="number" name="quantite" id="quantite" value="1" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                </div>

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/boutique/achats?eleve_id=<?= $eleve['id_eleve'] ?>" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Annuler</a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Enregistrer l'Achat
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
