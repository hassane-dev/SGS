<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <h2 class="text-2xl font-bold mb-6"><?= _('Edit Role') ?>: <?= htmlspecialchars($role['nom_role']) ?></h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/roles/update" method="POST">
            <input type="hidden" name="id_role" value="<?= htmlspecialchars($role['id_role']) ?>">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Role Details -->
                <div class="md:col-span-1">
                    <h3 class="text-lg font-semibold mb-4 border-b pb-2"><?= _('Role Details') ?></h3>
                    <div>
                        <label for="nom_role" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Role Name') ?></label>
                        <input type="text" name="nom_role" id="nom_role" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($role['nom_role']) ?>" required>
                    </div>

                    <?php if (Auth::can('manage_all_lycees')): ?>
                    <div class="mt-4">
                        <label for="lycee_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Scope') ?></label>
                        <select name="lycee_id" id="lycee_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="" <?= !$role['lycee_id'] ? 'selected' : '' ?>><?= _('Global Role') ?></option>
                            <?php foreach ($lycees as $lycee): ?>
                                <option value="<?= $lycee['id_lycee'] ?>" <?= $role['lycee_id'] == $lycee['id_lycee'] ? 'selected' : '' ?>><?= _('Specific to') ?>: <?= htmlspecialchars($lycee['nom_lycee']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                        <input type="hidden" name="lycee_id" value="<?= htmlspecialchars($role['lycee_id']) ?>">
                    <?php endif; ?>
                </div>

                <!-- Permissions -->
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold mb-4 border-b pb-2"><?= _('Permissions') ?></h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php foreach ($permissions as $permission): ?>
                            <label class="flex items-center p-2 rounded-lg border hover:bg-gray-100">
                                <input type="checkbox" name="permissions[]" value="<?= $permission['id_permission'] ?>"
                                    class="form-checkbox h-5 w-5 text-blue-600"
                                    <?= in_array($permission['nom_permission'], $role_permissions) ? 'checked' : '' ?>>
                                <span class="ml-3 text-gray-700"><?= htmlspecialchars($permission['nom_permission']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>


            <div class="mt-8 flex justify-end gap-4">
                <a href="/roles" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Cancel') ?></a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?= _('Update') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
