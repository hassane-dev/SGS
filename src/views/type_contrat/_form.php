<?php
// We expect to receive the following variables:
// $contrat (array, can be empty for creation)
// $form_action (string, e.g., '/contrats/store')
// $is_edit (boolean)
?>
<form action="<?= $form_action ?>" method="POST">
    <?php if ($is_edit): ?>
        <input type="hidden" name="id_contrat" value="<?= htmlspecialchars($contrat['id_contrat'] ?? '') ?>">
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="md:col-span-2">
            <label for="libelle" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Libellé du contrat') ?></label>
            <input type="text" name="libelle" id="libelle" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($contrat['libelle'] ?? '') ?>" required>
        </div>

        <div>
            <label for="type_paiement" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Type de Paiement') ?></label>
            <select name="type_paiement" id="type_paiement" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                <option value="fixe" <?= (isset($contrat['type_paiement']) && $contrat['type_paiement'] == 'fixe') ? 'selected' : '' ?>><?= _('Fixe') ?></option>
                <option value="a_l_heure" <?= (isset($contrat['type_paiement']) && $contrat['type_paiement'] == 'a_l_heure') ? 'selected' : '' ?>><?= _('À l\'heure') ?></option>
                <option value="aucun" <?= (isset($contrat['type_paiement']) && $contrat['type_paiement'] == 'aucun') ? 'selected' : '' ?>><?= _('Aucun') ?></option>
            </select>
        </div>

        <div>
            <label for="prise_en_charge" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Prise en Charge par') ?></label>
            <select name="prise_en_charge" id="prise_en_charge" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                <option value="Etat" <?= (isset($contrat['prise_en_charge']) && $contrat['prise_en_charge'] == 'Etat') ? 'selected' : '' ?>><?= _('État') ?></option>
                <option value="Ecole" <?= (isset($contrat['prise_en_charge']) && $contrat['prise_en_charge'] == 'Ecole') ? 'selected' : '' ?>><?= _('École') ?></option>
                <option value="Mixte" <?= (isset($contrat['prise_en_charge']) && $contrat['prise_en_charge'] == 'Mixte') ? 'selected' : '' ?>><?= _('Mixte') ?></option>
            </select>
        </div>

        <div class="md:col-span-2">
            <label for="description" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Description') ?></label>
            <textarea name="description" id="description" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"><?= htmlspecialchars($contrat['description'] ?? '') ?></textarea>
        </div>

        <?php if (Auth::can('manage_all_lycees')): ?>
            <div class="md:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" name="is_global" value="1" onchange="document.getElementById('lycee_id_wrapper').classList.toggle('hidden')" <?= !isset($contrat['lycee_id']) ? 'checked' : '' ?>>
                    <span class="ml-2 text-gray-700"><?= _('Contrat Global (disponible pour tous les lycées)') ?></span>
                </label>
            </div>
            <div id="lycee_id_wrapper" class="<?= !isset($contrat['lycee_id']) ? 'hidden' : '' ?>">
                <label for="lycee_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Lycée Spécifique') ?></label>
                <select name="lycee_id" id="lycee_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    <option value=""><?= _('-- Sélectionner un lycée --') ?></option>
                    <?php /* You would need to pass a $lycees variable to the view */ ?>
                </select>
            </div>
        <?php endif; ?>
    </div>

    <div class="mt-8 flex justify-end gap-4">
        <a href="/contrats" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Annuler') ?></a>
        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <?= _('Enregistrer') ?>
        </button>
    </div>
</form>