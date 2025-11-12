<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6"><?= _('Add New Class') ?></h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/classes/store" method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

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

                <!-- Categorie -->
                <div>
                    <label for="categorie" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Category') ?></label>
                    <select name="categorie" id="categorie" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        <option value=""><?= _('-- Choose a category --') ?></option>
                        <option value="Scientifique"><?= _('Scientific') ?></option>
                        <option value="Littéraire"><?= _('Literary') ?></option>
                    </select>
                </div>

                <!-- Numero -->
                <div>
                    <label for="numero" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Number') ?></label>
                    <input type="number" name="numero" id="numero" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" placeholder="<?= _('Ex: 1') ?>">
                </div>

                <!-- Le champ Cycle a été supprimé car il est maintenant assigné automatiquement par le contrôleur -->

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
