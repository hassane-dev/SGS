<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold"><?= $is_admin ? _('Consultation du Cahier de Texte') : _('Mon Cahier de Texte') ?></h2>
        <?php if (Auth::get('role_name') === 'enseignant'): ?>
            <a href="/cahier-texte/create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-plus mr-2"></i> <?= _('Nouvelle Entrée') ?>
            </a>
        <?php endif; ?>
    </div>

    <?php if ($is_admin): ?>
    <div class="bg-white p-4 rounded-lg shadow-md mb-6">
        <form action="/cahier-texte" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="personnel_id" class="block text-sm font-medium text-gray-700"><?= _('Filtrer par enseignant') ?></label>
                <select name="personnel_id" id="personnel_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <option value=""><?= _('Tous les enseignants') ?></option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= $teacher['id_user'] ?>" <?= ($filters['personnel_id_filter'] == $teacher['id_user']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($teacher['prenom'] . ' ' . $teacher['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="classe_id" class="block text-sm font-medium text-gray-700"><?= _('Filtrer par classe') ?></label>
                <select name="classe_id" id="classe_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <option value=""><?= _('Toutes les classes') ?></option>
                    <?php foreach ($classes as $classe): ?>
                         <option value="<?= $classe['id_classe'] ?>" <?= ($filters['classe_id_filter'] == $classe['id_classe']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($classe['nom_classe'] . ' ' . $classe['serie']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="date" class="block text-sm font-medium text-gray-700"><?= _('Filtrer par date') ?></label>
                <input type="date" name="date" id="date" value="<?= htmlspecialchars($filters['date_filter'] ?? '') ?>" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
            <div class="flex space-x-2">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded"><?= _('Filtrer') ?></button>
                <a href="/cahier-texte" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded"><?= _('Effacer') ?></a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"><?= _('Date') ?></th>
                    <?php if ($is_admin): ?>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"><?= _('Enseignant') ?></th>
                    <?php endif; ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"><?= _('Classe') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"><?= _('Matière') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"><?= _('Contenu') ?></th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider"><?= _('Actions') ?></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($entries)): ?>
                    <tr>
                        <td colspan="<?= $is_admin ? '6' : '5' ?>" class="px-6 py-10 text-center text-gray-500">
                            <?= _('Aucune entrée trouvée.') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars(date('d/m/Y', strtotime($entry['date_cours']))) ?></td>
                            <?php if ($is_admin): ?>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($entry['prenom_personnel'] . ' ' . $entry['nom_personnel']) ?></td>
                            <?php endif; ?>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($entry['nom_classe'] . ' ' . $entry['serie']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($entry['nom_matiere']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap truncate max-w-md"><?= htmlspecialchars($entry['contenu_cours']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <?php if ($is_admin || Auth::get('id') == $entry['personnel_id']): ?>
                                    <a href="/cahier-texte/edit?id=<?= $entry['cahier_id'] ?>" class="text-indigo-600 hover:text-indigo-900 ml-4"><?= _('Modifier') ?></a>
                                    <form action="/cahier-texte/destroy" method="POST" class="inline-block ml-4" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir supprimer cette entrée ?') ?>');">
                                        <input type="hidden" name="id" value="<?= $entry['cahier_id'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900"><?= _('Supprimer') ?></button>
                                    </form>
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