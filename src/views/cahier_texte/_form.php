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

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="date_cours" class="form-label"><?= _('Date du cours') ?></label>
            <input type="date" name="date_cours" id="date_cours" class="form-control" value="<?= htmlspecialchars($entry['date_cours'] ?? date('Y-m-d')) ?>" required>
        </div>

        <div class="col-md-6 mb-3">
            <label for="class_subject" class="form-label"><?= _('Classe et Matière') ?></label>
            <select name="class_subject" id="class_subject" class="form-select" required>
                <option value=""><?= _('-- Sélectionner --') ?></option>
                <?php foreach ($assignments as $assignment): ?>
                    <?php
                        // Reconstruct the value to match how it's saved/edited
                        $value = (string)($assignment['id_classe'] . '-' . $assignment['id_matiere']);
                        $current_value = (isset($entry['classe_id']) && isset($entry['matiere_id'])) ? (string)($entry['classe_id'] . '-' . $entry['matiere_id']) : '';
                        // Check if selected either because of edit or directCreate
                        $selected = (!empty($current_value) && $value === $current_value) ? 'selected' : '';
                    ?>
                    <option value="<?= $value ?>" <?= $selected ?>>
                        <?= htmlspecialchars(Classe::getFormattedName($assignment) . ' - ' . $assignment['nom_matiere']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label for="heure_debut" class="form-label"><?= _('Heure de début') ?></label>
            <input type="time" name="heure_debut" id="heure_debut" class="form-control" value="<?= htmlspecialchars($entry['heure_debut'] ?? '') ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label for="heure_fin" class="form-label"><?= _('Heure de fin') ?></label>
            <input type="time" name="heure_fin" id="heure_fin" class="form-control" value="<?= htmlspecialchars($entry['heure_fin'] ?? '') ?>">
        </div>

        <div class="col-12 mb-3">
            <label for="contenu_cours" class="form-label"><?= _('Contenu du cours') ?></label>
            <textarea name="contenu_cours" id="contenu_cours" rows="6" class="form-control" placeholder="<?= _('Qu\'avez-vous enseigné aujourd\'hui ?') ?>"><?= htmlspecialchars($entry['contenu_cours'] ?? '') ?></textarea>
        </div>

        <div class="col-12 mb-3">
            <label for="travail_donne" class="form-label"><?= _('Travail à faire') ?></label>
            <textarea name="travail_donne" id="travail_donne" rows="4" class="form-control" placeholder="<?= _('Devoirs, exercices, etc.') ?>"><?= htmlspecialchars($entry['travail_donne'] ?? '') ?></textarea>
        </div>

        <div class="col-12 mb-3">
            <label for="observation" class="form-label"><?= _('Observations') ?></label>
            <textarea name="observation" id="observation" rows="3" class="form-control" placeholder="<?= _('Élèves absents, incidents, etc.') ?>"><?= htmlspecialchars($entry['observation'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="mt-4 text-end">
        <a href="/cahier-texte" class="btn btn-link-secondary me-2"><?= _('Annuler') ?></a>
        <button type="submit" class="btn btn-primary">
            <i class="ph-duotone ph-floppy-disk me-2"></i><?= _('Enregistrer') ?>
        </button>
    </div>
</form>
