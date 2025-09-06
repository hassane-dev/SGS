<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-lg mx-auto">
    <h2 class="text-2xl font-bold mb-6"><?= _('Add New Cycle') ?></h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/cycles/store" method="POST">
            <div class="grid grid-cols-1 gap-6">

                <div>
                    <label for="nom_cycle" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Cycle Name') ?></label>
                    <input type="text" name="nom_cycle" id="nom_cycle" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" placeholder="<?= _('Ex: High School') ?>" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="niveau_debut" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Start Level') ?></label>
                        <input type="number" name="niveau_debut" id="niveau_debut" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" placeholder="<?= _('Ex: 1') ?>">
                    </div>
                    <div>
                        <label for="niveau_fin" class="block text-gray-700 text-sm font-bold mb-2"><?= _('End Level') ?></label>
                        <input type="number" name="niveau_fin" id="niveau_fin" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" placeholder="<?= _('Ex: 3') ?>">
                    </div>
                </div>

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/cycles" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Cancel') ?></a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?= _('Save') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
