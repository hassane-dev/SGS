<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-2"><?= _('Add Payment') ?></h2>
    <p class="text-lg text-gray-600 mb-6"><?= _('For student') ?>: <span class="font-semibold"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></span></p>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/paiements/store" method="POST">
            <input type="hidden" name="eleve_id" value="<?= htmlspecialchars($eleve['id_eleve']) ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="md:col-span-2">
                    <label for="type_paiement" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Payment Type') ?></label>
                    <select name="type_paiement" id="type_paiement" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <option value="inscription"><?= _('Registration') ?></option>
                        <option value="mensualite"><?= _('Monthly Fee') ?></option>
                        <option value="assurance"><?= _('Insurance') ?></option>
                        <option value="boutique"><?= _('Shop') ?></option>
                    </select>
                </div>

                <div>
                    <label for="montant" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Amount') ?></label>
                    <input type="number" step="0.01" name="montant" id="montant" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                </div>

                <div>
                    <label for="statut" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Status') ?></label>
                    <select name="statut" id="statut" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <option value="paye"><?= _('Paid') ?></option>
                        <option value="partiel"><?= _('Partial') ?></option>
                        <option value="non_paye"><?= _('Unpaid') ?></option>
                    </select>
                </div>

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/paiements?eleve_id=<?= $eleve['id_eleve'] ?>" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Cancel') ?></a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?= _('Record Payment') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
