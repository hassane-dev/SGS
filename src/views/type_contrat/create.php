<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6"><?= _('Add Contract Type') ?></h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/contrats/store" method="POST">
            <div class="grid grid-cols-1 gap-6">

                <div>
                    <label for="libelle" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Label') ?></label>
                    <input type="text" name="libelle" id="libelle" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" placeholder="Ex: Fonctionnaire" required>
                </div>

                <div>
                    <label for="description" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Description') ?></label>
                    <textarea name="description" id="description" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"></textarea>
                </div>

                <?php if (Auth::can('manage_all_lycees')): ?>
                <div>
                    <label for="lycee_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Scope') ?></label>
                    <select name="lycee_id" id="lycee_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        <option value=""><?= _('Global Contract') ?></option>
                        <?php // In a real app, you'd fetch lycees here ?>
                    </select>
                </div>
                <?php endif; ?>

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/contrats" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Cancel') ?></a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?= _('Save') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
