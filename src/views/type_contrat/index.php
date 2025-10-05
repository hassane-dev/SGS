<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold"><?= _('Gestion des Types de Contrat') ?></h2>
        <a href="/contrats/create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <?= _('Ajouter un type de contrat') ?>
        </a>
    </div>

    <div class="bg-white shadow-md rounded">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Libellé') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Type de Paiement') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Prise en Charge') ?></th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Actions') ?></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($contrats as $contrat): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($contrat['libelle']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars(_(ucfirst(str_replace('_', ' ', $contrat['type_paiement'])))) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars(_($contrat['prise_en_charge'])) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="/contrats/edit?id=<?= $contrat['id_contrat'] ?>" class="text-indigo-600 hover:text-indigo-900"><?= _('Modifier') ?></a>
                            <form action="/contrats/destroy" method="POST" class="inline-block ml-4" onsubmit="return confirm('<?= _('Êtes-vous sûr ?') ?>');">
                                <input type="hidden" name="id" value="<?= $contrat['id_contrat'] ?>">
                                <button type="submit" class="text-red-600 hover:text-red-900"><?= _('Supprimer') ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
