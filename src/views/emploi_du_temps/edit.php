<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto">
    <h2 class="text-2xl font-bold mb-6"><?= isset($entry['id']) ? _('Edit Entry') : _('Add Entry') ?></h2>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold"><?= _('Error!') ?></strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= isset($entry['id']) && $entry['id'] ? '/emploi-du-temps/update' : '/emploi-du-temps/store' ?>" method="POST" class="bg-white p-6 rounded-lg shadow-md">
        <input type="hidden" name="id" value="<?= htmlspecialchars($entry['id'] ?? '') ?>">
        <input type="hidden" name="classe_id" value="<?= htmlspecialchars($entry['classe_id'] ?? $_GET['classe_id']) ?>">
        <input type="hidden" name="annee_academique" value="<?= htmlspecialchars($entry['annee_academique'] ?? $annee_academique) ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Jour -->
            <div>
                <label for="jour" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Day') ?></label>
                <select name="jour" id="jour" class="shadow border rounded w-full py-2 px-3 text-gray-700" required>
                    <?php $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi']; ?>
                    <?php foreach ($jours as $jour): ?>
                        <option value="<?= $jour ?>" <?= ($entry['jour'] ?? '') == $jour ? 'selected' : '' ?>><?= _($jour) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Heure Début & Fin -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="heure_debut" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Start Time') ?></label>
                    <input type="time" name="heure_debut" id="heure_debut" value="<?= htmlspecialchars($entry['heure_debut'] ?? '') ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                </div>
                <div>
                    <label for="heure_fin" class="block text-gray-700 text-sm font-bold mb-2"><?= _('End Time') ?></label>
                    <input type="time" name="heure_fin" id="heure_fin" value="<?= htmlspecialchars($entry['heure_fin'] ?? '') ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                </div>
            </div>

            <!-- Matière -->
            <div>
                <label for="matiere_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Subject') ?></label>
                <select name="matiere_id" id="matiere_id" class="shadow border rounded w-full py-2 px-3 text-gray-700" required>
                    <?php foreach ($matieres as $matiere): ?>
                        <option value="<?= $matiere['id_matiere'] ?>" <?= ($entry['matiere_id'] ?? '') == $matiere['id_matiere'] ? 'selected' : '' ?>><?= htmlspecialchars($matiere['nom_matiere']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Professeur -->
            <div>
                <label for="professeur_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Teacher') ?></label>
                <select name="professeur_id" id="professeur_id" class="shadow border rounded w-full py-2 px-3 text-gray-700" required>
                    <?php foreach ($professeurs as $prof): ?>
                        <option value="<?= $prof['id_user'] ?>" <?= ($entry['professeur_id'] ?? '') == $prof['id_user'] ? 'selected' : '' ?>><?= htmlspecialchars($prof['prenom'] . ' ' . $prof['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Salle -->
            <div>
                <label for="salle_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Room') ?></label>
                <select name="salle_id" id="salle_id" class="shadow border rounded w-full py-2 px-3 text-gray-700" required>
                    <?php foreach ($salles as $salle): ?>
                        <option value="<?= $salle['id_salle'] ?>" <?= ($entry['salle_id'] ?? '') == $salle['id_salle'] ? 'selected' : '' ?>><?= htmlspecialchars($salle['nom_salle']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Modifiable -->
            <div>
                <label for="modifiable" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Modifiable') ?></label>
                <input type="checkbox" name="modifiable" value="1" <?= !empty($entry['modifiable']) ? 'checked' : '' ?> class="form-checkbox h-5 w-5 text-blue-600">
            </div>
        </div>

        <div class="mt-8 flex justify-end">
            <a href="/emploi-du-temps?classe_id=<?= htmlspecialchars($entry['classe_id'] ?? $_GET['classe_id']) ?>" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-4"><?= _('Cancel') ?></a>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <?= _('Save') ?>
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
