<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold"><?= _('Subject Management') ?></h2>
        <a href="/matieres/create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <?= _('Add Subject') ?>
        </a>
    </div>

    <?php if (isset($error) && $error === 'delete_failed'): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold"><?= _('Error!') ?></strong>
            <span class="block sm:inline"><?= _('Cannot delete this subject because it is in use.') ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow-md rounded">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Subject Name') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Coefficient') ?></th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Actions') ?></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($matieres)): ?>
                    <tr>
                        <td colspan="3" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                            <?= _('No subjects found.') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($matieres as $matiere): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($matiere['nom_matiere']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($matiere['coef']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="/matieres/edit?id=<?= $matiere['id_matiere'] ?>" class="text-indigo-600 hover:text-indigo-900"><?= _('Edit') ?></a>
                                <form action="/matieres/destroy" method="POST" class="inline-block ml-4" onsubmit="return confirm('<?= _('Are you sure you want to delete this subject?') ?>');">
                                    <input type="hidden" name="id" value="<?= $matiere['id_matiere'] ?>">
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
