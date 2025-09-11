<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6"><?= _('Add Course to Timetable') ?></h2>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'conflict'): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold"><?= _('Error!') ?></strong>
            <span class="block sm:inline"><?= _('A conflict was detected. The teacher or class is already booked at this time.') ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/emploi-du-temps/store" method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="md:col-span-2">
                    <label for="classe_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Class') ?></label>
                    <select name="classe_id" id="classe_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <?php foreach ($data['classes'] as $classe): ?>
                            <option value="<?= $classe['id_classe'] ?>"><?= htmlspecialchars($classe['nom_classe']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="matiere_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Subject') ?></label>
                    <select name="matiere_id" id="matiere_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <?php foreach ($data['matieres'] as $matiere): ?>
                            <option value="<?= $matiere['id_matiere'] ?>"><?= htmlspecialchars($matiere['nom_matiere']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="professeur_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Teacher') ?></label>
                    <select name="professeur_id" id="professeur_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <?php foreach ($data['professeurs'] as $prof): ?>
                            <option value="<?= $prof['id_user'] ?>"><?= htmlspecialchars($prof['prenom'] . ' ' . $prof['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="jour" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Day') ?></label>
                    <select name="jour" id="jour" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <option value="Lundi"><?= _('Monday') ?></option>
                        <option value="Mardi"><?= _('Tuesday') ?></option>
                        <option value="Mercredi"><?= _('Wednesday') ?></option>
                        <option value="Jeudi"><?= _('Thursday') ?></option>
                        <option value="Vendredi"><?= _('Friday') ?></option>
                        <option value="Samedi"><?= _('Saturday') ?></option>
                    </select>
                </div>

                <div>
                    <label for="salle_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Room') ?></label>
                    <select name="salle_id" id="salle_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <?php foreach ($data['salles'] as $salle): ?>
                            <option value="<?= $salle['id_salle'] ?>"><?= htmlspecialchars($salle['nom_salle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="heure_debut" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Start Time') ?></label>
                    <input type="time" name="heure_debut" id="heure_debut" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                </div>

                <div>
                    <label for="heure_fin" class="block text-gray-700 text-sm font-bold mb-2"><?= _('End Time') ?></label>
                    <input type="time" name="heure_fin" id="heure_fin" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                </div>

                <input type="hidden" name="annee_academique" value="2024-2025"> <!-- Should be dynamic -->

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/emploi-du-temps" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Cancel') ?></a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?= _('Save') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
