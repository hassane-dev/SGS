<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6">Modifier la Classe</h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/classes/update" method="POST">
            <input type="hidden" name="id_classe" value="<?= htmlspecialchars($classe['id_classe']) ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Nom Classe -->
                <div class="md:col-span-2">
                    <label for="nom_classe" class="block text-gray-700 text-sm font-bold mb-2">Nom de la Classe</label>
                    <input type="text" name="nom_classe" id="nom_classe" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($classe['nom_classe']) ?>" required>
                </div>

                <!-- Niveau -->
                <div>
                    <label for="niveau" class="block text-gray-700 text-sm font-bold mb-2">Niveau</label>
                    <input type="text" name="niveau" id="niveau" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($classe['niveau']) ?>">
                </div>

                <!-- Serie -->
                <div>
                    <label for="serie" class="block text-gray-700 text-sm font-bold mb-2">Série</label>
                    <input type="text" name="serie" id="serie" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($classe['serie']) ?>">
                </div>

                <!-- Numero Classe -->
                <div>
                    <label for="numero_classe" class="block text-gray-700 text-sm font-bold mb-2">Numéro de la classe</label>
                    <input type="number" name="numero_classe" id="numero_classe" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($classe['numero_classe']) ?>">
                </div>

                <!-- Cycle -->
                <div>
                    <label for="cycle_id" class="block text-gray-700 text-sm font-bold mb-2">Cycle</label>
                    <select name="cycle_id" id="cycle_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <?php foreach ($cycles as $cycle): ?>
                            <option value="<?= $cycle['id_cycle'] ?>" <?= $classe['cycle_id'] == $cycle['id_cycle'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cycle['nom_cycle']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Lycee -->
                <?php if (Auth::get('role') === 'super_admin_national'): ?>
                <div class="md:col-span-2">
                    <label for="lycee_id" class="block text-gray-700 text-sm font-bold mb-2">Lycée</label>
                    <select name="lycee_id" id="lycee_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <?php foreach ($lycees as $lycee): ?>
                             <option value="<?= $lycee['id_lycee'] ?>" <?= $classe['lycee_id'] == $lycee['id_lycee'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lycee['nom_lycee']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                    <input type="hidden" name="lycee_id" value="<?= $classe['lycee_id'] ?>">
                <?php endif; ?>

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/classes" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Annuler</a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
