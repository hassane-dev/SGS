<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold"><?= _('My Classes and Subjects') ?></h2>
    </div>

    <div class="bg-white shadow-md rounded">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Class') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Subject') ?></th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Actions') ?></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($assignments)): ?>
                    <tr>
                        <td colspan="3" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                            <?= _('You are not assigned to any classes at the moment.') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($assignment['nom_classe'] . ' (' . $assignment['serie'] . ')') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($assignment['nom_matiere']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="/notes/enter?class_id=<?= $assignment['id_classe'] ?>&matiere_id=<?= $assignment['id_matiere'] ?>&type=devoir" class="text-blue-600 hover:text-blue-900"><?= _('Enter Homework Grades') ?></a>
                                <a href="/notes/enter?class_id=<?= $assignment['id_classe'] ?>&matiere_id=<?= $assignment['id_matiere'] ?>&type=composition" class="text-green-600 hover:text-green-900 ml-4"><?= _('Enter Exam Grades') ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
