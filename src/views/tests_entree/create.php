<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-2"><?= _('Record Entrance Test') ?></h2>
    <p class="text-lg text-gray-600 mb-6"><?= _('For student') ?>: <span class="font-semibold"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></span></p>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/tests_entree/store" method="POST">
            <input type="hidden" name="eleve_id" value="<?= htmlspecialchars($eleve['id_eleve']) ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="md:col-span-2">
                    <label for="classe_visee_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Target Class') ?></label>
                    <select name="classe_visee_id" id="classe_visee_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <option value=""><?= _('-- Choose a class --') ?></option>
                        <?php foreach ($classes as $classe): ?>
                            <option value="<?= $classe['id_classe'] ?>"><?= htmlspecialchars($classe['nom_classe']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="score" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Score') ?></label>
                    <input type="number" step="0.01" name="score" id="score" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>

                <div>
                    <label for="date_test" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Test Date') ?></label>
                    <input type="date" name="date_test" id="date_test" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= date('Y-m-d') ?>">
                </div>

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/tests_entree?eleve_id=<?= $eleve['id_eleve'] ?>" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Cancel') ?></a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?= _('Record Result') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
