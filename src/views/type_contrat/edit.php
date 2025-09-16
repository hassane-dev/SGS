<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6"><?= _('Edit Contract Type') ?></h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/contrats/update" method="POST">
            <input type="hidden" name="id_contrat" value="<?= htmlspecialchars($contrat['id_contrat']) ?>">
            <div class="grid grid-cols-1 gap-6">

                <div>
                    <label for="libelle" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Label') ?></label>
                    <input type="text" name="libelle" id="libelle" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($contrat['libelle']) ?>" required>
                </div>

                <div>
                    <label for="description" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Description') ?></label>
                    <textarea name="description" id="description" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"><?= htmlspecialchars($contrat['description']) ?></textarea>
                </div>

                <?php if (Auth::can('manage_all_lycees')): ?>
                <div>
                    <label for="lycee_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Scope') ?></label>
                    <select name="lycee_id" id="lycee_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        <option value="" <?= !$contrat['lycee_id'] ? 'selected' : '' ?>><?= _('Global Contract') ?></option>
                        <?php // In a real app, you'd fetch lycees here and select the correct one ?>
                    </select>
                </div>
                 <?php else: ?>
                    <input type="hidden" name="lycee_id" value="<?= htmlspecialchars($contrat['lycee_id']) ?>">
                <?php endif; ?>

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/contrats" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Cancel') ?></a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?= _('Update') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
