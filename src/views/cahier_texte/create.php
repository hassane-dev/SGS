<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6"><?= _('Add Logbook Entry') ?></h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/cahier-texte/store" method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="md:col-span-2">
                    <label for="class_subject" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Class & Subject') ?></label>
                    <select name="class_subject" id="class_subject" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <option value=""><?= _('-- Select a class/subject --') ?></option>
                        <?php foreach ($assignments as $assignment): ?>
                            <option value="<?= $assignment['id_classe'] ?>-<?= $assignment['id_matiere'] ?>">
                                <?= htmlspecialchars($assignment['nom_classe']) ?> - <?= htmlspecialchars($assignment['nom_matiere']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="date_cours" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Course Date') ?></label>
                    <input type="date" name="date_cours" id="date_cours" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="flex gap-4">
                    <div>
                        <label for="heure_debut" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Start Time') ?></label>
                        <input type="time" name="heure_debut" id="heure_debut" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div>
                        <label for="heure_fin" class="block text-gray-700 text-sm font-bold mb-2"><?= _('End Time') ?></label>
                        <input type="time" name="heure_fin" id="heure_fin" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label for="contenu_cours" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Course Content') ?></label>
                    <textarea name="contenu_cours" id="contenu_cours" rows="5" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required></textarea>
                </div>

                <div class="md:col-span-2">
                    <label for="exercices" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Homework Given') ?></label>
                    <textarea name="exercices" id="exercices" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"></textarea>
                </div>

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/cahier-texte" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Cancel') ?></a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?= _('Save Entry') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
