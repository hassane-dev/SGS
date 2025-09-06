<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-2"><?= _('Enroll Student') ?></h2>
    <p class="text-lg text-gray-600 mb-6"><?= _('Student') ?>: <span class="font-semibold"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></span></p>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/inscriptions/enroll" method="POST">
            <input type="hidden" name="eleve_id" value="<?= htmlspecialchars($eleve['id_eleve']) ?>">

            <div class="grid grid-cols-1 gap-6">

                <div>
                    <label for="annee_academique" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Academic Year') ?></label>
                    <input type="text" name="annee_academique" id="annee_academique" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-200" value="<?= htmlspecialchars($annee_academique) ?>" readonly>
                </div>

                <div>
                    <label for="classe_id" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Class') ?></label>
                    <select name="classe_id" id="classe_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <option value=""><?= _('-- Choose a class --') ?></option>
                        <?php foreach ($classes as $classe): ?>
                            <option value="<?= $classe['id_classe'] ?>">
                                <?= htmlspecialchars($classe['nom_classe'] . ' (' . $classe['serie'] . ')') ?>
                                <?php if(Auth::get('role') === 'super_admin_national') echo ' - ' . htmlspecialchars($classe['nom_lycee']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                     <label class="flex items-center">
                        <input type="checkbox" name="actif" value="1" class="form-checkbox h-5 w-5 text-blue-600">
                        <span class="ml-2 text-gray-700"><?= _('Activate enrollment immediately') ?></span>
                    </label>
                    <p class="text-xs text-gray-600 mt-1"><?= _('The enrollment must be active for the student to appear in class lists for grading, etc.') ?></p>
                </div>

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/eleves" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Cancel') ?></a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?= _('Enroll Student') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
