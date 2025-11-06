<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6"><?= _('Add New Role') ?></h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/roles/store" method="POST">
            <div class="grid grid-cols-1 gap-6">

                <div>
                    <label for="nom_role" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Role Name') ?></label>
                    <input type="text" name="nom_role" id="nom_role" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                </div>

                <?php if (Auth::can('view_all_lycees', 'lycee')): ?>
                <div>
                    <label for="lycee_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Scope') ?></label>
                    <select name="lycee_id" id="lycee_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        <option value=""><?= _('Global Role') ?></option>
                        <?php foreach ($lycees as $lycee): ?>
                            <option value="<?= $lycee['id'] ?>"><?= _('Specific to') ?>: <?= htmlspecialchars($lycee['nom_lycee']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-600 mt-1"><?= _('Leave as "Global" for roles like Teacher, or assign to a school for a specific local role.') ?></p>
                </div>
                <?php endif; ?>

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/roles" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Cancel') ?></a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?= _('Save') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
