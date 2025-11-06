<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold"><?= _('Role Management') ?></h2>
        <a href="/roles/create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <?= _('Add Role') ?>
        </a>
    </div>

    <div class="bg-white shadow-md rounded">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Role Name') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Scope') ?></th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Actions') ?></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($roles)): ?>
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-center"><?= _('No roles found.') ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($roles as $role): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($role['nom_role']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?= $role['lycee_id'] ? htmlspecialchars($role['nom_lycee']) : _('Global') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <?php if ($role['id_role'] > 6): // Basic protection for default roles ?>
                                    <a href="/roles/edit?id=<?= $role['id_role'] ?>" class="text-indigo-600 hover:text-indigo-900"><?= _('Edit') ?></a>
                                    <form action="/roles/destroy" method="POST" class="inline-block ml-4" onsubmit="return confirm('<?= _('Are you sure?') ?>');">
                                        <input type="hidden" name="id" value="<?= $role['id_role'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900"><?= _('Delete') ?></button>
                                    </form>
                                <?php else: ?>
                                    <a href="/roles/edit?id=<?= $role['id_role'] ?>" class="text-indigo-600 hover:text-indigo-900"><?= _('View Permissions') ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
