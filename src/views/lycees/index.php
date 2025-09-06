<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold"><?= _('High School Management') ?></h2>
        <a href="/lycees/create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <?= _('Add High School') ?>
        </a>
    </div>

    <div class="bg-white shadow-md rounded">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('High School Name') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Type') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('City') ?></th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Actions') ?></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($lycees)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                            <?= _('No high schools found.') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($lycees as $lycee): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($lycee['nom_lycee']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($lycee['type_lycee']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($lycee['ville']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="/lycees/edit?id=<?= $lycee['id_lycee'] ?>" class="text-indigo-600 hover:text-indigo-900"><?= _('Edit') ?></a>
                                <form action="/lycees/destroy" method="POST" class="inline-block ml-4" onsubmit="return confirm('<?= _('Are you sure you want to delete this high school?') ?>');">
                                    <input type="hidden" name="id" value="<?= $lycee['id_lycee'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900"><?= _('Delete') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
