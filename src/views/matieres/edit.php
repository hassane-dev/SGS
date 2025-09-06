<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-lg mx-auto">
    <h2 class="text-2xl font-bold mb-6"><?= _('Edit Subject') ?></h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/matieres/update" method="POST">
            <input type="hidden" name="id_matiere" value="<?= htmlspecialchars($matiere['id_matiere']) ?>">
            <div class="grid grid-cols-1 gap-6">

                <div>
                    <label for="nom_matiere" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Subject Name') ?></label>
                    <input type="text" name="nom_matiere" id="nom_matiere" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($matiere['nom_matiere']) ?>" required>
                </div>

                <div>
                    <label for="coef" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Coefficient') ?></label>
                    <input type="number" step="0.01" name="coef" id="coef" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($matiere['coef']) ?>">
                </div>

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/matieres" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Cancel') ?></a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?= _('Update') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
