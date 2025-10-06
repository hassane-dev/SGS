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

    <div class="row g-3">
        <div class="col-md-6">
            <label for="date_cours" class="form-label fw-bold"><?= _('Date du cours') ?></label>
            <input type="date" name="date_cours" id="date_cours" class="form-control" value="<?= htmlspecialchars($entry['date_cours'] ?? date('Y-m-d')) ?>" required>
        </div>

        <div class="col-md-6">
            <label for="class_subject" class="form-label fw-bold"><?= _('Classe et Matière') ?></label>
            <select name="class_subject" id="class_subject" class="form-select" required>
                <option value=""><?= _('-- Sélectionner --') ?></option>
                <?php foreach ($assignments as $assignment): ?>
                    <?php
                        // Reconstruct the value to match how it's saved/edited
                        $value = $assignment['classe_id'] . '-' . $assignment['matiere_id'];
                        $current_value = ($entry['classe_id'] ?? '') . '-' . ($entry['matiere_id'] ?? '');
                        $selected = ($is_edit && $value === $current_value) ? 'selected' : '';
                    ?>
                    <option value="<?= $value ?>" <?= $selected ?>>
                        <?= htmlspecialchars($assignment['nom_classe'] . ' ' . $assignment['serie'] . ' - ' . $assignment['nom_matiere']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label for="heure_debut" class="form-label fw-bold"><?= _('Heure de début') ?></label>
            <input type="time" name="heure_debut" id="heure_debut" class="form-control" value="<?= htmlspecialchars($entry['heure_debut'] ?? '') ?>">
        </div>

        <div class="col-md-6">
            <label for="heure_fin" class="form-label fw-bold"><?= _('Heure de fin') ?></label>
            <input type="time" name="heure_fin" id="heure_fin" class="form-control" value="<?= htmlspecialchars($entry['heure_fin'] ?? '') ?>">
        </div>

        <div class="col-12">
            <label for="contenu_cours" class="form-label fw-bold"><?= _('Contenu du cours') ?></label>
            <textarea name="contenu_cours" id="contenu_cours" rows="6" class="form-control"><?= htmlspecialchars($entry['contenu_cours'] ?? '') ?></textarea>
        </div>

        <div class="col-12">
            <label for="travail_donne" class="form-label fw-bold"><?= _('Travail à faire') ?></label>
            <textarea name="travail_donne" id="travail_donne" rows="4" class="form-control"><?= htmlspecialchars($entry['travail_donne'] ?? '') ?></textarea>
        </div>

        <div class="col-12">
            <label for="observation" class="form-label fw-bold"><?= _('Observations (élèves absents, incidents, etc.)') ?></label>
            <textarea name="observation" id="observation" rows="3" class="form-control"><?= htmlspecialchars($entry['observation'] ?? '') ?></textarea>
        </div>

    </div>

    <div class="mt-4 d-flex justify-content-end">
        <a href="/cahier-texte" class="btn btn-secondary me-2"><?= _('Annuler') ?></a>
        <button type="submit" class="btn btn-primary">
            <?= _('Enregistrer') ?>
        </button>
    </div>
</form>