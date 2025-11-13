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

    <h5 class="border-bottom pb-2 mb-3"><?= _('Informations Personnelles') ?></h5>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="prenom" class="form-label"><?= _('Prénom') ?></label>
            <input type="text" name="prenom" id="prenom" class="form-control" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label for="nom" class="form-label"><?= _('Nom') ?></label>
            <input type="text" name="nom" id="nom" class="form-control" value="<?= htmlspecialchars($user['nom'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label for="sexe" class="form-label"><?= _('Sexe') ?></label>
            <select name="sexe" id="sexe" class="form-select">
                <option value="Homme" <?= (isset($user['sexe']) && $user['sexe'] == 'Homme') ? 'selected' : '' ?>><?= _('Homme') ?></option>
                <option value="Femme" <?= (isset($user['sexe']) && $user['sexe'] == 'Femme') ? 'selected' : '' ?>><?= _('Femme') ?></option>
            </select>
        </div>
        <div class="col-md-6">
            <label for="date_naissance" class="form-label"><?= _('Date de Naissance') ?></label>
            <input type="date" name="date_naissance" id="date_naissance" class="form-control" value="<?= htmlspecialchars($user['date_naissance'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label for="lieu_naissance" class="form-label"><?= _('Lieu de Naissance') ?></label>
            <input type="text" name="lieu_naissance" id="lieu_naissance" class="form-control" value="<?= htmlspecialchars($user['lieu_naissance'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label for="telephone" class="form-label"><?= _('Téléphone') ?></label>
            <input type="tel" name="telephone" id="telephone" class="form-control" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label for="adresse" class="form-label"><?= _('Adresse') ?></label>
            <input name="adresse" id="adresse" class="form-control" value="<?= htmlspecialchars($user['adresse'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label for="photo" class="form-label"><?= _('Photo') ?></label>
            <input type="file" name="photo" id="photo" class="form-control">
        </div>
    </div>

    <h5 class="border-bottom pb-2 mt-4 mb-3"><?= _('Informations du Compte') ?></h5>
    <div class="row g-3">
        <div class="col-12">
            <label for="email" class="form-label"><?= _('Email') ?></label>
            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
        </div>
        <div class="col-12">
            <label for="mot_de_passe" class="form-label"><?= _('Mot de passe') ?></label>
            <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control" <?= !$is_edit ? 'required' : '' ?>>
            <?php if ($is_edit): ?>
                <div class="form-text"><?= _('Laissez vide pour ne pas changer.') ?></div>
            <?php endif; ?>
        </div>
    </div>

    <h5 class="border-bottom pb-2 mt-4 mb-3"><?= _('Informations Professionnelles') ?></h5>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="fonction" class="form-label"><?= _('Fonction') ?></label>
            <input type="text" name="fonction" id="fonction" class="form-control" value="<?= htmlspecialchars($user['fonction'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label for="date_embauche" class="form-label"><?= _('Date d\'embauche') ?></label>
            <input type="date" name="date_embauche" id="date_embauche" class="form-control" value="<?= htmlspecialchars($user['date_embauche'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label for="role_id" class="form-label"><?= _('Rôle') ?></label>
            <select name="role_id" id="role_id" class="form-select" required>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= $role['id_role'] ?>" <?= (isset($user['role_id']) && $user['role_id'] == $role['id_role']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($role['nom_role']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label for="contrat_id" class="form-label"><?= _('Type de Contrat') ?></label>
            <select name="contrat_id" id="contrat_id" class="form-select">
                <option value=""><?= _('-- Aucun --') ?></option>
                <?php foreach ($contrats as $contrat): ?>
                    <option value="<?= $contrat['id_contrat'] ?>" <?= (isset($user['contrat_id']) && $user['contrat_id'] == $contrat['id_contrat']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($contrat['libelle']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (Auth::can('view_all_lycees', 'lycee')): ?>
        <div class="col-md-6">
            <label for="lycee_id" class="form-label"><?= _('Lycée d\'affectation') ?></label>
            <select name="lycee_id" id="lycee_id" class="form-select">
                <option value=""><?= _('-- Aucun --') ?></option>
                <?php foreach ($lycees as $lycee): ?>
                    <option value="<?= $lycee['id_lycee'] ?>" <?= (isset($user['lycee_id']) && $user['lycee_id'] == $lycee['id_lycee']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($lycee['nom_lycee']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="col-12">
            <div class="form-check">
                <input type="checkbox" name="actif" value="1" class="form-check-input" id="actif" <?= !isset($user['actif']) || $user['actif'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="actif"><?= _('Compte Actif') ?></label>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-end">
        <a href="/users" class="btn btn-secondary me-2"><?= _('Annuler') ?></a>
        <button type="submit" class="btn btn-primary">
            <?= _('Enregistrer') ?>
        </button>
    </div>
</form>
