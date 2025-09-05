<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6">Modifier une Licence</h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/licences/update" method="POST">
            <input type="hidden" name="id_licence" value="<?= htmlspecialchars($licence['id_licence']) ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="md:col-span-2">
                    <label for="lycee_id" class="block text-gray-700 text-sm font-bold mb-2">Lycée</label>
                    <select name="lycee_id" id="lycee_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <?php foreach ($lycees as $lycee): ?>
                            <option value="<?= $lycee['id_lycee'] ?>" <?= $licence['lycee_id'] == $lycee['id_lycee'] ? 'selected' : '' ?>><?= htmlspecialchars($lycee['nom_lycee']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="duree_mois" class="block text-gray-700 text-sm font-bold mb-2">Durée (en mois)</label>
                    <select name="duree_mois" id="duree_mois" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <option value="3" <?= $licence['duree_mois'] == 3 ? 'selected' : '' ?>>3 mois</option>
                        <option value="6" <?= $licence['duree_mois'] == 6 ? 'selected' : '' ?>>6 mois</option>
                        <option value="12" <?= $licence['duree_mois'] == 12 ? 'selected' : '' ?>>1 an</option>
                    </select>
                </div>

                <div>
                    <label for="date_debut" class="block text-gray-700 text-sm font-bold mb-2">Date de Début</label>
                    <input type="date" name="date_debut" id="date_debut" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($licence['date_debut']) ?>" required>
                </div>

                <div class="md:col-span-2">
                     <label class="flex items-center">
                        <input type="checkbox" name="actif" value="1" <?= !empty($licence['actif']) ? 'checked' : '' ?> class="form-checkbox h-5 w-5 text-blue-600">
                        <span class="ml-2 text-gray-700">Licence Active</span>
                    </label>
                </div>

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/licences" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Annuler</a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
