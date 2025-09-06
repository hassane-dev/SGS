<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6"><?= _('Add New Class') ?></h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/classes/store" method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Nom Classe -->
                <div class="md:col-span-2">
                    <label for="nom_classe" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Class Name') ?></label>
                    <input type="text" name="nom_classe" id="nom_classe" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" placeholder="<?= _('Ex: Tenth Grade') ?>" required>
                </div>

                <!-- Niveau -->
                <div>
                    <label for="niveau" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Level') ?></label>
                    <input type="text" name="niveau" id="niveau" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" placeholder="<?= _('Ex: 10') ?>">
                </div>

                <!-- Serie -->
                <div>
                    <label for="serie" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Series') ?></label>
                    <input type="text" name="serie" id="serie" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" placeholder="<?= _('Ex: A4') ?>">
                </div>

                <!-- Numero Classe -->
                <div>
                    <label for="numero_classe" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Class Number') ?></label>
                    <input type="number" name="numero_classe" id="numero_classe" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" placeholder="<?= _('Ex: 1') ?>">
                </div>

                <!-- Cycle -->
                <div>
                    <label for="cycle_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Cycle') ?></label>
                    <select name="cycle_id" id="cycle_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <option value=""><?= _('-- Choose a cycle --') ?></option>
                        <?php foreach ($cycles as $cycle): ?>
                            <option value="<?= $cycle['id_cycle'] ?>"><?= htmlspecialchars($cycle['nom_cycle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Lycee -->
                <?php if (Auth::get('role') === 'super_admin_national'): ?>
                <div class="md:col-span-2">
                    <label for="lycee_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('High School') ?></label>
                    <select name="lycee_id" id="lycee_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <option value=""><?= _('-- Choose a high school --') ?></option>
                        <?php foreach ($lycees as $lycee): ?>
                            <option value="<?= $lycee['id_lycee'] ?>"><?= htmlspecialchars($lycee['nom_lycee']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/classes" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Cancel') ?></a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?= _('Save') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
