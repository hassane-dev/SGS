<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6"><?= _('Record a Salary Payment') ?></h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/salaires/store" method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="md:col-span-2">
                    <label for="personnel_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Staff Member') ?></label>
                    <select name="personnel_id" id="personnel_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <?php foreach ($personnels as $personnel): ?>
                            <option value="<?= $personnel['id_user'] ?>"><?= htmlspecialchars($personnel['prenom'] . ' ' . $personnel['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex gap-4">
                    <div>
                        <label for="mois" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Month') ?></label>
                        <input type="number" name="mois" id="mois" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= date('m') ?>" required>
                    </div>
                    <div>
                        <label for="annee" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Year') ?></label>
                        <input type="number" name="annee" id="annee" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= date('Y') ?>" required>
                    </div>
                </div>

                <div>
                    <label for="date_paiement" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Payment Date') ?></label>
                    <input type="date" name="date_paiement" id="date_paiement" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= date('Y-m-d') ?>">
                </div>

                <div>
                    <label for="montant_brut" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Gross Amount') ?></label>
                    <input type="number" step="0.01" name="montant_brut" id="montant_brut" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>

                <div>
                    <label for="montant_net" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Net Amount') ?></label>
                    <input type="number" step="0.01" name="montant_net" id="montant_net" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                </div>

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/salaires" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Cancel') ?></a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?= _('Save') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
