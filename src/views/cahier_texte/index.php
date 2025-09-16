<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold"><?= _('Digital Logbook') ?></h2>
        <a href="/cahier-texte/create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <?= _('Add New Entry') ?>
        </a>
    </div>

    <div class="bg-white shadow-md rounded">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Date') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Class') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Subject') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Course Content') ?></th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Actions') ?></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($entries)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                            <?= _('No entries found.') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars(date('d/m/Y', strtotime($entry['date_cours']))) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($entry['nom_classe']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($entry['nom_matiere']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap truncate max-w-sm"><?= htmlspecialchars($entry['contenu_cours']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <form action="/cahier-texte/destroy" method="POST" onsubmit="return confirm('<?= _('Are you sure?') ?>');">
                                    <input type="hidden" name="id" value="<?= $entry['id_cahier'] ?>">
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
