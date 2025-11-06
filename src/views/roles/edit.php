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

                    <?php if (Auth::can('view_all_lycees', 'lycee')): ?>
                    <div class="mt-4">
                        <label for="lycee_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Scope') ?></label>
                        <select name="lycee_id" id="lycee_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="" <?= !$role['lycee_id'] ? 'selected' : '' ?>><?= _('Global Role') ?></option>
                            <?php foreach ($lycees as $lycee): ?>
                                <option value="<?= $lycee['id'] ?>" <?= $role['lycee_id'] == $lycee['id'] ? 'selected' : '' ?>><?= _('Specific to') ?>: <?= htmlspecialchars($lycee['nom_lycee']) ?></option>
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
                    <div class="space-y-4">
                        <?php
                        // Group permissions by resource
                        $grouped_permissions = [];
                        foreach ($permissions as $permission) {
                            $grouped_permissions[$permission['resource']][] = $permission;
                        }
                        ?>

                        <?php foreach ($grouped_permissions as $resource => $perms): ?>
                            <fieldset class="border rounded-lg p-4">
                                <legend class="px-2 font-semibold text-gray-800"><?= _(ucfirst(str_replace('_', ' ', $resource))) ?></legend>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
                                    <?php foreach ($perms as $permission): ?>
                                        <label class="flex items-center">
                                            <input type="checkbox" name="permissions[]" value="<?= $permission['id_permission'] ?>"
                                                class="form-checkbox h-4 w-4 text-blue-600"
                                                <?= in_array($permission['id_permission'], $role_permission_ids) ? 'checked' : '' ?>>
                                            <span class="ml-2 text-gray-700"><?= _(ucfirst($permission['action'])) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
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
