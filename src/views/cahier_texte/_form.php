<?php
// We expect to receive the following variables:
// $entry (array, can be empty for creation)
// $assignments (array of teacher's class/subject assignments)
// $form_action (string)
// $is_edit (boolean)
?>
<form action="<?= $form_action ?>" method="POST">
    <?php if ($is_edit): ?>
        <input type="hidden" name="cahier_id" value="<?= htmlspecialchars($entry['cahier_id'] ?? '') ?>">
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <div>
            <label for="date_cours" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Date du cours') ?></label>
            <input type="date" name="date_cours" id="date_cours" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($entry['date_cours'] ?? date('Y-m-d')) ?>" required>
        </div>

        <div>
            <label for="class_subject" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Classe et Matière') ?></label>
            <select name="class_subject" id="class_subject" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                <option value=""><?= _('-- Sélectionner --') ?></option>
                <?php foreach ($assignments as $assignment): ?>
                    <?php $value = $assignment['id_classe'] . '-' . $assignment['id_matiere']; ?>
                    <?php $selected = (isset($entry['classe_id']) && $entry['classe_id'] == $assignment['id_classe'] && $entry['matiere_id'] == $assignment['id_matiere']) ? 'selected' : ''; ?>
                    <option value="<?= $value ?>" <?= $selected ?>>
                        <?= htmlspecialchars($assignment['nom_classe'] . ' ' . $assignment['serie'] . ' - ' . $assignment['nom_matiere']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="heure_debut" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Heure de début') ?></label>
            <input type="time" name="heure_debut" id="heure_debut" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($entry['heure_debut'] ?? '') ?>">
        </div>

        <div>
            <label for="heure_fin" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Heure de fin') ?></label>
            <input type="time" name="heure_fin" id="heure_fin" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($entry['heure_fin'] ?? '') ?>">
        </div>

        <div class="md:col-span-2">
            <label for="contenu_cours" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Contenu du cours') ?></label>
            <textarea name="contenu_cours" id="contenu_cours" rows="6" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"><?= htmlspecialchars($entry['contenu_cours'] ?? '') ?></textarea>
        </div>

        <div class="md:col-span-2">
            <label for="travail_donne" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Travail à faire') ?></label>
            <textarea name="travail_donne" id="travail_donne" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"><?= htmlspecialchars($entry['travail_donne'] ?? '') ?></textarea>
        </div>

        <div class="md:col-span-2">
            <label for="observation" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Observations (élèves absents, incidents, etc.)') ?></label>
            <textarea name="observation" id="observation" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"><?= htmlspecialchars($entry['observation'] ?? '') ?></textarea>
        </div>

    </div>

    <div class="mt-8 flex justify-end gap-4">
        <a href="/cahier-texte" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Annuler') ?></a>
        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <?= _('Enregistrer') ?>
        </button>
    </div>
</form>