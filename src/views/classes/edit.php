<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6"><?= _('Edit Class') ?></h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/classes/update" method="POST">
            <input type="hidden" name="id_classe" value="<?= htmlspecialchars($classe['id_classe']) ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Nom Classe -->
                <div class="md:col-span-2">
                    <label for="nom_classe" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Class Name') ?></label>
                    <input type="text" name="nom_classe" id="nom_classe" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($classe['nom_classe']) ?>" required>
                </div>

                <!-- Niveau -->
                <div>
                    <label for="niveau" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Level') ?></label>
                    <input type="text" name="niveau" id="niveau" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($classe['niveau']) ?>">
                </div>

                <!-- Serie -->
                <div>
                    <label for="serie" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Series') ?></label>
                    <input type="text" name="serie" id="serie" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($classe['serie']) ?>">
                </div>

                <!-- Categorie -->
                <div>
                    <label for="categorie" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Category') ?></label>
                    <select name="categorie" id="categorie" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        <option value=""><?= _('-- Choose a category --') ?></option>
                        <option value="Scientifique" <?= ($classe['categorie'] ?? '') == 'Scientifique' ? 'selected' : '' ?>><?= _('Scientific') ?></option>
                        <option value="Littéraire" <?= ($classe['categorie'] ?? '') == 'Littéraire' ? 'selected' : '' ?>><?= _('Literary') ?></option>
                    </select>
                </div>

                <!-- Numero Classe -->
                <div>
                    <label for="numero_classe" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Class Number') ?></label>
                    <input type="number" name="numero_classe" id="numero_classe" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($classe['numero_classe']) ?>">
                </div>

                <!-- Cycle -->
                <div>
                    <label for="cycle_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Cycle') ?></label>
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
                    <label for="lycee_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('High School') ?></label>
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
                <a href="/classes" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Cancel') ?></a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?= _('Update') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
