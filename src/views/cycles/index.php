<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold"><?= _('Cycle Management') ?></h2>
        <a href="/cycles/create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <?= _('Add Cycle') ?>
        </a>
    </div>

    <?php if (isset($error) && $error === 'delete_failed'): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold"><?= _('Error!') ?></strong>
            <span class="block sm:inline"><?= _('Cannot delete this cycle because it is used by one or more classes.') ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow-md rounded">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Cycle Name') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Start Level') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('End Level') ?></th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Actions') ?></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($cycles)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                            <?= _('No cycles found.') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($cycles as $cycle): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($cycle['nom_cycle']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($cycle['niveau_debut']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($cycle['niveau_fin']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="/cycles/edit?id=<?= $cycle['id_cycle'] ?>" class="text-indigo-600 hover:text-indigo-900"><?= _('Edit') ?></a>
                                <form action="/cycles/destroy" method="POST" class="inline-block ml-4" onsubmit="return confirm('<?= _('Are you sure you want to delete this cycle?') ?>');">
                                    <input type="hidden" name="id" value="<?= $cycle['id_cycle'] ?>">
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
