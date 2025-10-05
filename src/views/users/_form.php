<?php
// We expect to receive the following variables:
// $user (array, can be empty for creation)
// $roles (array of roles)
// $contrats (array of contracts)
// $lycees (array of lycees, for super admin)
// $form_action (string, e.g., '/users/store' or '/users/update')
// $is_edit (boolean)
?>
<form action="<?= $form_action ?>" method="POST" enctype="multipart/form-data">
    <?php if ($is_edit): ?>
        <input type="hidden" name="id_user" value="<?= htmlspecialchars($user['id_user'] ?? '') ?>">
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Personal Information -->
        <div class="md:col-span-2 font-bold text-lg text-gray-700 border-b pb-2 mb-2"><?= _('Informations Personnelles') ?></div>

        <div>
            <label for="prenom" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Prénom') ?></label>
            <input type="text" name="prenom" id="prenom" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" required>
        </div>
        <div>
            <label for="nom" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Nom') ?></label>
            <input type="text" name="nom" id="nom" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($user['nom'] ?? '') ?>" required>
        </div>
        <div>
            <label for="sexe" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Sexe') ?></label>
            <select name="sexe" id="sexe" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                <option value="Homme" <?= (isset($user['sexe']) && $user['sexe'] == 'Homme') ? 'selected' : '' ?>><?= _('Homme') ?></option>
                <option value="Femme" <?= (isset($user['sexe']) && $user['sexe'] == 'Femme') ? 'selected' : '' ?>><?= _('Femme') ?></option>
            </select>
        </div>
        <div>
            <label for="date_naissance" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Date de Naissance') ?></label>
            <input type="date" name="date_naissance" id="date_naissance" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($user['date_naissance'] ?? '') ?>">
        </div>
        <div>
            <label for="lieu_naissance" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Lieu de Naissance') ?></label>
            <input type="text" name="lieu_naissance" id="lieu_naissance" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($user['lieu_naissance'] ?? '') ?>">
        </div>
        <div>
            <label for="adresse" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Adresse') ?></label>
            <input name="adresse" id="adresse" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($user['adresse'] ?? '') ?>">
        </div>
        <div>
            <label for="telephone" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Téléphone') ?></label>
            <input type="tel" name="telephone" id="telephone" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>">
        </div>
        <div>
            <label for="photo" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Photo') ?></label>
            <input type="file" name="photo" id="photo" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
        </div>

        <!-- Account Information -->
        <div class="md:col-span-2 font-bold text-lg text-gray-700 border-b pb-2 mt-4 mb-2"><?= _('Informations du Compte') ?></div>

        <div class="md:col-span-2">
            <label for="email" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Email') ?></label>
            <input type="email" name="email" id="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
        </div>
        <div class="md:col-span-2">
            <label for="mot_de_passe" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Mot de passe') ?></label>
            <input type="password" name="mot_de_passe" id="mot_de_passe" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" <?= !$is_edit ? 'required' : '' ?>>
            <?php if ($is_edit): ?>
                <p class="text-xs text-gray-500 mt-1"><?= _('Laissez vide pour ne pas changer.') ?></p>
            <?php endif; ?>
        </div>

        <!-- Professional Information -->
        <div class="md:col-span-2 font-bold text-lg text-gray-700 border-b pb-2 mt-4 mb-2"><?= _('Informations Professionnelles') ?></div>

        <div>
            <label for="fonction" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Fonction') ?></label>
            <input type="text" name="fonction" id="fonction" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($user['fonction'] ?? '') ?>">
        </div>
        <div>
            <label for="date_embauche" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Date d\'embauche') ?></label>
            <input type="date" name="date_embauche" id="date_embauche" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($user['date_embauche'] ?? '') ?>">
        </div>

        <div>
            <label for="role_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Rôle') ?></label>
            <select name="role_id" id="role_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= $role['id_role'] ?>" <?= (isset($user['role_id']) && $user['role_id'] == $role['id_role']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($role['nom_role']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="contrat_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Type de Contrat') ?></label>
            <select name="contrat_id" id="contrat_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                <option value=""><?= _('-- Aucun --') ?></option>
                <?php foreach ($contrats as $contrat): ?>
                    <option value="<?= $contrat['id_contrat'] ?>" <?= (isset($user['contrat_id']) && $user['contrat_id'] == $contrat['id_contrat']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($contrat['libelle']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (Auth::can('manage_all_lycees')): ?>
        <div>
            <label for="lycee_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Lycée d\'affectation') ?></label>
            <select name="lycee_id" id="lycee_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                <option value=""><?= _('-- Aucun --') ?></option>
                <?php foreach ($lycees as $lycee): ?>
                    <option value="<?= $lycee['id_lycee'] ?>" <?= (isset($user['lycee_id']) && $user['lycee_id'] == $lycee['id_lycee']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($lycee['nom_lycee']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="md:col-span-2">
             <label class="flex items-center">
                <input type="hidden" name="actif" value="0">
                <input type="checkbox" name="actif" value="1" class="form-checkbox h-5 w-5 text-blue-600" <?= (isset($user['actif']) && $user['actif']) || !isset($user['actif']) ? 'checked' : '' ?>>
                <span class="ml-2 text-gray-700"><?= _('Compte Actif') ?></span>
            </label>
        </div>
    </div>

    <div class="mt-8 flex justify-end gap-4">
        <a href="/users" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Annuler') ?></a>
        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <?= _('Enregistrer') ?>
        </button>
    </div>
</form>