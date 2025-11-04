<?php
// We expect to receive the following variables:
// $contrat (array, can be empty for creation)
// $lycees (array of lycees)
// $form_action (string, e.g., '/contrats/store')
// $is_edit (boolean)
?>
<form action="<?= $form_action ?>" method="POST">
    <?php if ($is_edit): ?>
        <input type="hidden" name="id_contrat" value="<?= htmlspecialchars($contrat['id_contrat'] ?? '') ?>">
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-12">
            <label for="libelle" class="form-label fw-bold"><?= _('Libellé du contrat') ?></label>
            <input type="text" name="libelle" id="libelle" class="form-control" value="<?= htmlspecialchars($contrat['libelle'] ?? '') ?>" required>
        </div>

        <div class="col-md-6">
            <label for="type_paiement" class="form-label fw-bold"><?= _('Type de Paiement') ?></label>
            <select name="type_paiement" id="type_paiement" class="form-select" required>
                <option value="fixe" <?= (isset($contrat['type_paiement']) && $contrat['type_paiement'] == 'fixe') ? 'selected' : '' ?>><?= _('Fixe') ?></option>
                <option value="a_l_heure" <?= (isset($contrat['type_paiement']) && $contrat['type_paiement'] == 'a_l_heure') ? 'selected' : '' ?>><?= _('À l\'heure') ?></option>
                <option value="aucun" <?= (isset($contrat['type_paiement']) && $contrat['type_paiement'] == 'aucun') ? 'selected' : '' ?>><?= _('Aucun') ?></option>
            </select>
        </div>

        <div class="col-md-6">
            <label for="prise_en_charge" class="form-label fw-bold"><?= _('Prise en Charge par') ?></label>
            <select name="prise_en_charge" id="prise_en_charge" class="form-select" required>
                <option value="Etat" <?= (isset($contrat['prise_en_charge']) && $contrat['prise_en_charge'] == 'Etat') ? 'selected' : '' ?>><?= _('État') ?></option>
                <option value="Ecole" <?= (isset($contrat['prise_en_charge']) && $contrat['prise_en_charge'] == 'Ecole') ? 'selected' : '' ?>><?= _('École') ?></option>
                <option value="Mixte" <?= (isset($contrat['prise_en_charge']) && $contrat['prise_en_charge'] == 'Mixte') ? 'selected' : '' ?>><?= _('Mixte') ?></option>
            </select>
        </div>

        <div class="col-12">
            <label for="description" class="form-label fw-bold"><?= _('Description') ?></label>
            <textarea name="description" id="description" rows="4" class="form-control"><?= htmlspecialchars($contrat['description'] ?? '') ?></textarea>
        </div>

        <?php if (Auth::can('view_all_lycees', 'system')): ?>
            <div class="col-12">
                <div class="form-check form-switch">
                     <input type="hidden" name="is_global" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_global_switch" onchange="document.getElementById('lycee_id_wrapper').classList.toggle('d-none')" <?= !isset($contrat['lycee_id']) || empty($contrat['lycee_id']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_global_switch"><?= _('Contrat Global (disponible pour tous les lycées)') ?></label>
                </div>
            </div>
            <div id="lycee_id_wrapper" class="col-12 <?= !isset($contrat['lycee_id']) || empty($contrat['lycee_id']) ? 'd-none' : '' ?>">
                <label for="lycee_id" class="form-label fw-bold"><?= _('Lycée Spécifique') ?></label>
                <select name="lycee_id" id="lycee_id" class="form-select">
                    <option value=""><?= _('-- Sélectionner un lycée --') ?></option>
                    <?php foreach($lycees as $lycee): ?>
                        <option value="<?= $lycee['id_lycee'] ?>" <?= (isset($contrat['lycee_id']) && $contrat['lycee_id'] == $lycee['id_lycee']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($lycee['nom_lycee']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
    </div>

    <div class="mt-4 d-flex justify-content-end">
        <a href="/contrats" class="btn btn-secondary me-2"><?= _('Annuler') ?></a>
        <button type="submit" class="btn btn-primary">
            <?= _('Enregistrer') ?>
        </button>
    </div>
</form>