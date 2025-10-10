<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold"><?= _('Salary Management') ?></h2>
        <a href="/salaires/create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <?= _('Record a Salary Payment') ?>
        </a>
    </div>

    <div class="bg-white shadow-md rounded">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Period') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Staff Member') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Net Amount') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Payment Date') ?></th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"><?= _('Actions') ?></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($salaires as $salaire): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($salaire['mois'] . '/' . $salaire['annee']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($salaire['prenom'] . ' ' . $salaire['nom']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($salaire['montant_net']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= $salaire['date_paiement'] ? htmlspecialchars(date('d/m/Y', strtotime($salaire['date_paiement']))) : '' ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="/salaires/fiche?id=<?= $salaire['id_salaire'] ?>" class="text-green-600 hover:text-green-900" target="_blank"><?= _('Download Payslip') ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
